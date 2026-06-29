<?php

namespace App\Services\Odoo;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SyncLog;
use App\Services\OdooService;
use Illuminate\Support\Facades\Log;

class PurchaseOrderOdooSyncService
{
    public function __construct(
        private readonly OdooService $odoo
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // Push semua PO yang belum sync ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushAll(): void
    {
        $orders = PurchaseOrder::where('sync_to_odoo', false)
            ->where('detail_fetched', true)
            ->readyToRetry()
            ->with('items')
            ->get();

        Log::info("[Odoo PO] Push {$orders->count()} PO ke Odoo");

        foreach ($orders as $order) {
            $this->pushOne($order);
        }

        Log::info('[Odoo PO] Push selesai.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Push satu PO ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushOne(PurchaseOrder $po): void
    {
        Log::info("[Odoo PO] Push PO #{$po->purchaseorder_no}");

        try {
            // 1. Cari partner_id di Odoo berdasarkan contact_id di supplier table
            $partnerId = $this->getPartnerId($po);
            if (! $partnerId) {
                throw new \Exception("Partner tidak ditemukan di Odoo untuk contact_id={$po->contact_id}");
            }

            // 2. Build order lines dari items
            $orderLines = $this->buildOrderLines($po->items);
            if (empty($orderLines)) {
                throw new \Exception("Tidak ada order line yang valid untuk PO #{$po->purchaseorder_no}");
            }

            // 3. Cek apakah PO sudah ada di Odoo berdasarkan partner_ref
            $existing = $this->findInOdoo($po->ref_no);

            if ($existing) {
                $odooId = $existing['id'];
                $this->updateInOdoo($odooId, $po, $partnerId, $orderLines);
                Log::info("[Odoo PO] Update existing PO odoo_id={$odooId}");
            } else {
                $odooId = $this->createInOdoo($po, $partnerId, $orderLines);
                Log::info("[Odoo PO] Created PO odoo_id={$odooId}");
            }

            // 4. Update DB Laravel
            $po->update([
                'odoo_id'               => $odooId,
                'sync_to_odoo'          => true,
                'sync_to_odoo_at'       => now(),
                'sync_error'            => null,
                'sync_to_odoo_attempts' => $po->sync_to_odoo_attempts + 1,
            ]);

            // 5. Catat log sukses
            SyncLog::record(
                entityType: SyncLog::ENTITY_PURCHASE_ORDER,
                entityId:   $po->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_SUCCESS,
                message:    "PO berhasil push ke Odoo dengan id={$odooId}",
                attempt:    $po->sync_to_odoo_attempts + 1,
            );

        } catch (\Throwable $e) {
            Log::error("[Odoo PO] Gagal push PO #{$po->purchaseorder_no}", [
                'error' => $e->getMessage(),
            ]);

            $attempts  = $po->sync_to_odoo_attempts + 1;
            $nextRetry = match (true) {
                $attempts === 1 => now()->addMinutes(1),
                $attempts === 2 => now()->addMinutes(5),
                default         => now()->addMinutes(15),
            };

            $po->update([
                'sync_error'                 => $e->getMessage(),
                'sync_to_odoo_attempts'      => $attempts,
                'sync_to_odoo_next_retry_at' => $nextRetry,
            ]);

            SyncLog::record(
                entityType: SyncLog::ENTITY_PURCHASE_ORDER,
                entityId:   $po->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['purchaseorder_no' => $po->purchaseorder_no],
                attempt:    $attempts,
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Ambil partner_id Odoo dari tabel suppliers berdasarkan contact_id Jubelio.
     */
    private function getPartnerId(PurchaseOrder $po): ?int
    {
        $supplier = Supplier::where('jubelio_contact_id', $po->contact_id)
                            ->whereNotNull('odoo_id')
                            ->first();

        return $supplier?->odoo_id;
    }

    /**
     * Build order_line format Odoo dari items PO.
     * Format: [0, 0, { product_id, product_qty, price_unit, name }]
     */
    private function buildOrderLines(iterable $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            // Cari product_id di Odoo berdasarkan item_code
            $odooProductId = $this->getOdooProductId($item->item_code);

            if (! $odooProductId) {
                Log::warning("[Odoo PO] Produk tidak ditemukan di Odoo", [
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                ]);
                continue;
            }

            $lines[] = [0, 0, [
                'product_id'  => $odooProductId,
                'name'        => $item->item_name ?? $item->description,
                'product_qty' => (float) $item->qty_in_base,
                'price_unit'  => (float) $item->price,
            ]];
        }

        return $lines;
    }

    /**
     * Cari odoo product.product id berdasarkan item_code (default_code).
     */
    private function getOdooProductId(string $itemCode): ?int
    {
        // Cari dari tabel product_variants dulu
        $variant = ProductVariant::where('item_code', $itemCode)
                                 ->whereNotNull('odoo_id')
                                 ->first();

        if ($variant) {
            return $variant->odoo_id;
        }

        // Fallback: cari langsung ke Odoo
        $result = $this->odoo->execute(
            'product.product',
            'search_read',
            [[['default_code', '=', $itemCode]]],
            ['fields' => ['id'], 'limit' => 1]
        );

        return $result[0]['id'] ?? null;
    }

    /**
     * Cari PO di Odoo berdasarkan partner_ref (no referensi).
     */
    private function findInOdoo(string $refNo): ?array
    {
        $result = $this->odoo->execute(
            'purchase.order',
            'search_read',
            [[['partner_ref', '=', $refNo]]],
            ['fields' => ['id', 'name', 'partner_ref'], 'limit' => 1]
        );

        return $result[0] ?? null;
    }

    /**
     * Create PO baru di Odoo.
     */
    private function createInOdoo(PurchaseOrder $po, int $partnerId, array $orderLines): int
    {
        return $this->odoo->execute(
            'purchase.order',
            'create',
            [$this->mapToOdoo($po, $partnerId, $orderLines)]
        );
    }

    /**
     * Update PO yang sudah ada di Odoo.
     */
    // private function updateInOdoo(int $odooId, PurchaseOrder $po, int $partnerId, array $orderLines): void
    // {
    //     $this->odoo->execute(
    //         'purchase.order',
    //         'write',
    //         [[$odooId], $this->mapToOdoo($po, $partnerId, $orderLines)]
    //     );
    // }

    private function updateInOdoo(int $odooId, PurchaseOrder $po, int $partnerId, array $orderLines): void
        {
            // 1. Ambil semua order line yang ada
            $existingLines = $this->odoo->execute(
                'purchase.order.line',
                'search_read',
                [[['order_id', '=', $odooId]]],
                ['fields' => ['id'], 'limit' => 100]
            );

            // 2. Hapus semua line lama
            if (! empty($existingLines)) {
                $lineIds = array_column($existingLines, 'id');
                $this->odoo->execute('purchase.order.line', 'unlink', [$lineIds]);
            }

            // 3. Update dengan line baru
            $this->odoo->execute(
                'purchase.order',
                'write',
                [[$odooId], $this->mapToOdoo($po, $partnerId, $orderLines)]
            );
        }

    /**
     * Mapping field Jubelio → Odoo purchase.order
     */
    private function mapToOdoo(PurchaseOrder $po, int $partnerId, array $orderLines): array
    {
        return [
            'partner_id'   => $partnerId,
            'date_order'   => $po->transaction_date?->format('Y-m-d H:i:s'),
            'partner_ref'  => $po->ref_no,
            'notes'        => $po->note,
            'order_line'   => $orderLines,
        ];
    }
}