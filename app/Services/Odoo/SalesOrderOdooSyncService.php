<?php

namespace App\Services\Odoo;

use App\Models\SalesOrder;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use App\Services\OdooService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesOrderOdooSyncService
{
    public function __construct(
        private readonly OdooService $odoo
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // Push semua SO yang belum sync ke Odoo
    // ────────────────────────────────────────────────────────────────────────
    public function pushAll(): void
    {
        $orders = SalesOrder::where('sync_to_odoo', false)
            ->where('detail_fetched', true)
            ->readyToRetry()
            ->with('items')
            ->get();

        Log::info("[Odoo SO] Push {$orders->count()} SO ke Odoo");

        foreach ($orders as $order) {
            $this->pushOne($order);
        }

        Log::info('[Odoo SO] Push selesai.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Push satu SO ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushOne(SalesOrder $so): void
    {
        Log::info("[Odoo SO] Push SO #{$so->salesorder_no}");

        try {
            // 1. Cari partner_id berdasarkan channel
            $partnerId = $this->getPartnerIdByChannel($so->channel_name);
            if (! $partnerId) {
                throw new \Exception("Partner tidak ditemukan untuk channel={$so->channel_name}");
            }

            // 2. Build order lines
            $orderLines = $this->buildOrderLines($so->items);
            if (empty($orderLines)) {
                throw new \Exception("Tidak ada order line valid untuk SO #{$so->salesorder_no}");
            }

            // 3. Cek apakah SO sudah ada di Odoo
            $existing = $this->findInOdoo($so->salesorder_no);

            if ($existing) {
                $odooId = $existing['id'];
                $this->updateInOdoo($odooId, $so, $partnerId, $orderLines);
                Log::info("[Odoo SO] Update existing SO odoo_id={$odooId}");
            } else {
                $odooId = $this->createInOdoo($so, $partnerId, $orderLines);
                Log::info("[Odoo SO] Created SO odoo_id={$odooId}");
            }

            // 4. Update DB Laravel
            $so->update([
                'odoo_id'               => $odooId,
                'sync_to_odoo'          => true,
                'sync_to_odoo_at'       => now(),
                'sync_error'            => null,
                'sync_to_odoo_attempts' => $so->sync_to_odoo_attempts + 1,
            ]);

            // 5. Catat log sukses
            SyncLog::record(
                entityType: 'sales_order',
                entityId:   $so->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_SUCCESS,
                message:    "SO berhasil push ke Odoo dengan id={$odooId}",
                attempt:    $so->sync_to_odoo_attempts + 1,
            );

        } catch (\Throwable $e) {
            Log::error("[Odoo SO] Gagal push SO #{$so->salesorder_no}", [
                'error' => $e->getMessage(),
            ]);

            $attempts  = $so->sync_to_odoo_attempts + 1;
            $nextRetry = match (true) {
                $attempts === 1 => now()->addMinutes(1),
                $attempts === 2 => now()->addMinutes(5),
                default         => now()->addMinutes(15),
            };

            $so->update([
                'sync_error'                 => $e->getMessage(),
                'sync_to_odoo_attempts'      => $attempts,
                'sync_to_odoo_next_retry_at' => $nextRetry,
            ]);

            SyncLog::record(
                entityType: 'sales_order',
                entityId:   $so->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['salesorder_no' => $so->salesorder_no],
                attempt:    $attempts,
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Ambil partner_id Odoo berdasarkan channel_name.
     * Di-cache supaya tidak hit Odoo API berkali-kali.
     */
    private function getPartnerIdByChannel(string $channelName): ?int
    {
        $cacheKey = 'odoo_partner_channel_' . md5($channelName);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($channelName) {

            // Mapping channel → nama partner di Odoo
            $partnerName = match (strtoupper(trim($channelName))) {
                'SHOPEE'             => 'SHOPEE',
                'SHOP | TOKOPEDIA'   => 'TOKOPEDIA',
                'TOKOPEDIA'          => 'TOKOPEDIA',
                'INTERNAL'           => 'PT Synergy Care Pratama',
                default              => $channelName,
            };

            // Cari di Odoo
            $result = $this->odoo->execute(
                'res.partner',
                'search_read',
                [[['name', '=', $partnerName]]],
                ['fields' => ['id', 'name'], 'limit' => 1]
            );

            if (! empty($result)) {
                return $result[0]['id'];
            }

            // Tidak ditemukan — buat baru sebagai customer
            Log::info("[Odoo SO] Buat partner baru untuk channel: {$channelName}");

            return $this->odoo->execute(
                'res.partner',
                'create',
                [[
                    'name'          => $partnerName,
                    'customer_rank' => 1,
                    'supplier_rank' => 0,
                ]]
            );
        });
    }


    /**
     * Build order_line format Odoo dari items SO.
     */
    private function buildOrderLines(iterable $items): array
        {
            //  dd($items);
            $lines = [];

            foreach ($items as $item) {

                $odooProductId = $this->getOdooProductId($item->item_code);

                if (!$odooProductId) {
                    continue;
                }

                //1   kode ini udah running bagus tapi masih mau saya sempurnakan
                // $qty = max(1, (float) $item->qty_in_base);
                // sementara gunakan amount
                // $netPrice = round((float) $item->amount / $qty, 2);

                // Log::info('Push SO Line', [
                //     'item_code'        => $item->item_code,
                //     'price'            => $item->price,
                //     'sell_price'       => $item->sell_price,
                //     'original_price'   => $item->original_price,
                //     'disc_amount'      => $item->disc_amount,
                //     'amount'           => $item->amount,
                //     'qty'              => $qty,
                //     'price_unit'       => $netPrice,
                // ]);

                // ini untuk menghindari error "The tax_id field is required" di Odoo, karena di Odoo 16, field tax_id tidak boleh kosong. Jadi kita set tax_id ke empty list.
                // $lines[] = [0,0,[
                //     'product_id'      => $odooProductId,
                //     'name'            => $item->item_name,
                //     'product_uom_qty' => $qty,
                //     'price_unit'      => $netPrice,
                //     'tax_id'          => [[6,0,[]]],
                // ]];


                //2 start code baru ini juga bagus tapi masih mau saya sempurnakan lagi 
                // $qty = max(1, (float) $item->qty_in_base);

                //     $price = (float) $item->price;

                //     $discount = $price > 0
                //         ? round(((float)$item->disc_amount / $price) * 100, 6)
                //         : 0;

                //     $lines[] = [0, 0, [
                //         'product_id'      => $odooProductId,
                //         'name'            => $item->item_name,
                //         'product_uom_qty' => $qty,
                //         'price_unit'      => $price,
                //         'discount'        => $discount,
                //         'tax_id'          => [[6, 0, []]],
                //     ]];

                $qty = max(1, (float) $item->qty);

                $price = round((float)$item->price, 2);

                $amount = round((float)$item->amount, 2);

                // $discount = $price > 0  cara rekomendasinkan
                //     ? round((($price - $amount) / $price) * 100, 2)
                //     : 0;

                    $discount = (($price - $amount) / $price) * 100;

                $lines[] = [0,0,[
                    'product_id'      => $odooProductId,
                    'name'            => $item->item_name,
                    'product_uom_qty' => $qty,
                    'price_unit'      => $price,
                    'discount'        => $discount,
                    'tax_id'          => [[6,0,[]]],
                ]];
            }

            return $lines;
        }

    /**
     * Cari odoo product.product id berdasarkan item_code.
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
     * Cari SO di Odoo berdasarkan salesorder_no sebagai client_order_ref.
     */
    private function findInOdoo(string $salesorderNo): ?array
    {
        $result = $this->odoo->execute(
            'sale.order',
            'search_read',
            [[['client_order_ref', '=', $salesorderNo]]],
            ['fields' => ['id', 'name', 'client_order_ref'], 'limit' => 1]
        );

        return $result[0] ?? null;
    }

    /**
     * Create SO baru di Odoo.
     */
    private function createInOdoo(SalesOrder $so, int $partnerId, array $orderLines): int
    {
        return $this->odoo->execute(
            'sale.order',
            'create',
            [$this->mapToOdoo($so, $partnerId, $orderLines)]
        );
    }

    /**
     * Update SO yang sudah ada di Odoo.
     */
    private function updateInOdoo(int $odooId, SalesOrder $so, int $partnerId, array $orderLines): void
        {
            // 1. Ambil semua order line yang ada
            $existingLines = $this->odoo->execute(
                'sale.order.line',
                'search_read',
                [[['order_id', '=', $odooId]]],
                ['fields' => ['id'], 'limit' => 100]
            );

            // 2. Hapus semua line lama
            if (! empty($existingLines)) {
                $lineIds = array_column($existingLines, 'id');
                $this->odoo->execute('sale.order.line', 'unlink', [$lineIds]);
            }

            // 3. Update dengan line baru
            $this->odoo->execute(
                'sale.order',
                'write',
                [[$odooId], $this->mapToOdoo($so, $partnerId, $orderLines)]
            );
        }

    /**
     * Mapping field Jubelio → Odoo sale.order
    */
    private function mapToOdoo(SalesOrder $so, int $partnerId, array $orderLines): array
    {
        return [
            'partner_id'       => $partnerId,
            'date_order'       => $so->transaction_date?->format('Y-m-d H:i:s'),
            'client_order_ref' => $so->salesorder_no,
            'order_line'       => $orderLines,
            'note'             => implode("\n", array_filter([
                "Customer    : {$so->customer_name}",
                "Channel     : {$so->channel_name}",
                "Store       : {$so->store_name}",
                "Tracking No : {$so->tracking_number}",
                "Shipper     : {$so->shipper}",
                $so->note ? "Note        : {$so->note}" : null,
            ])),
        ];
    }
}