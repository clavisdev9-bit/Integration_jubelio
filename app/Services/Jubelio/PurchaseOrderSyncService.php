<?php

namespace App\Services\Jubelio;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderSyncService
{
    public function __construct(
        private readonly JubelioClient $client
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // STEP 1 — Simpan list PO (dari /purchase/orders)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Fetch semua halaman list PO dari Jubelio dan simpan ke DB.
     * Item belum diambil di sini — hanya header PO.
     */
    public function syncList(int $pageSize = 20): void
    {
        $page  = 1;
        $total = null;
        $saved = 0;

        do {
            Log::info("[Jubelio PO] Fetching list page {$page}");

            // $response = $this->client->get('/purchase/orders', [
            //         'page'     => $page,
            //         'pageSize' => $pageSize,
            //     ]);
            $response = $this->client->get('/purchase/orders/', [
                    'page'     => $page,
                    'pageSize' => $pageSize,
                ]);
            $total    ??= $response['totalCount'] ?? 0;
            $rows       = $response['data'] ?? [];

            foreach ($rows as $row) {
                $this->upsertHeader($row);
                $saved++;
            }

            $page++;

        } while (($page - 1) * $pageSize < $total);

        Log::info("[Jubelio PO] List sync selesai. Total: {$saved} PO disimpan.");
    }

    /**
     * Simpan / update satu baris dari response list.
     * Detail dan items BELUM diambil di sini.
     */
    private function upsertHeader(array $row): void
    {
        try {
            PurchaseOrder::updateOrCreate(
                ['jubelio_purchaseorder_id' => $row['purchaseorder_id']],
                [
                    'purchaseorder_no'     => $row['purchaseorder_no']  ?? null,
                    'contact_id'           => $row['contact_id']        ?? null,
                    'supplier_name'        => $row['supplier_name']      ?? null,
                    'transaction_date'     => $row['transaction_date']   ?? null,
                    'grand_total'          => $row['grand_total']        ?? 0,
                    'note'                 => $row['note']               ?? null,
                    'ref_no'               => $row['ref_no']             ?? null,
                    'status'               => $row['status']             ?? null,
                    'bills'                => $row['bills']              ?? null,
                    'raw_payload'          => $row,
                    'sync_from_jubelio'    => true,
                    'sync_from_jubelio_at' => now(),
                    'sync_from_jubelio_error' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Jubelio PO] Gagal upsert header', [
                'purchaseorder_id' => $row['purchaseorder_id'] ?? null,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // STEP 2 — Fetch & simpan detail PO (dari /purchase/orders/{id})
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Fetch detail satu PO dan simpan semua field + items ke DB.
     * Dipanggil oleh FetchPurchaseOrderDetailJob per satu PO.
     */
    public function syncDetail(PurchaseOrder $po): void
    {
        Log::info("[Jubelio PO] Fetch detail PO #{$po->jubelio_purchaseorder_id}");

        try {
        //    $detail = $this->client->get("/purchase/orders/{$po->jubelio_purchaseorder_id}");
           $detail = $this->client->get("/purchase/orders/{$po->jubelio_purchaseorder_id}");



            DB::transaction(function () use ($po, $detail) {
                $this->updateDetail($po, $detail);
                $this->upsertItems($po, $detail['items'] ?? []);
            });

            SyncLog::record(
                entityType: SyncLog::ENTITY_PURCHASE_ORDER,
                entityId:   $po->id,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_SUCCESS,
                message:    'Detail berhasil disimpan',
                attempt:    1,
            );

        } catch (\Throwable $e) {
            Log::error("[Jubelio PO] Gagal fetch detail PO #{$po->jubelio_purchaseorder_id}", [
                'error' => $e->getMessage(),
            ]);

            $po->update([
                'sync_from_jubelio_error' => $e->getMessage(),
            ]);

            SyncLog::record(
                entityType: SyncLog::ENTITY_PURCHASE_ORDER,
                entityId:   $po->id,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['jubelio_id' => $po->jubelio_purchaseorder_id],
            );

            throw $e; // biarkan Job handle retry-nya
        }
    }

    /**
     * Update kolom detail PO dari response /purchase/orders/{id}.
     */
    private function updateDetail(PurchaseOrder $po, array $detail): void
    {
        $po->update([
            // Supplier
            'supplier_email'  => $detail['supplier_email']  ?? null,

            // Pajak
            'is_tax_included' => $detail['is_tax_included'] ?? false,

            // Nilai
            'sub_total'       => $detail['sub_total']       ?? 0,
            'total_disc'      => $detail['total_disc']      ?? 0,
            'total_tax'       => $detail['total_tax']       ?? 0,
            'grand_total'     => $detail['grand_total']     ?? 0,

            // Pembayaran
            'payment_method'  => $detail['payment_method']  ?? null,
            'payment_term'    => $detail['payment_term']    ?? null,

            // Lokasi
            'location_id'     => $detail['location_id']    ?? null,
            'location_name'   => $detail['location_name']  ?? null,
            'location_code'   => $detail['location_code']  ?? null,

            // Sumber & Status
            'source'          => $detail['source']          ?? null,
            'is_closed'       => $detail['is_closed']       ?? false,
            'close_reason'    => $detail['close_reason']    ?? null,

            // Audit
            'created_by'      => $detail['created_by']     ?? null,
            'updated_by'      => $detail['updated_by']     ?? null,
            'last_modified'   => $detail['last_modified']  ?? null,

            // Lampiran
            'attachment'      => $detail['attachment']     ?? [],

            // Raw & flag
            'raw_payload'          => $detail,
            'detail_fetched'       => true,
            'detail_fetched_at'    => now(),
            'sync_from_jubelio'    => true,
            'sync_from_jubelio_at' => now(),
            'sync_from_jubelio_error' => null,
        ]);
    }

    /**
     * Upsert semua item dari response detail.
     */
    private function upsertItems(PurchaseOrder $po, array $items): void
    {
        foreach ($items as $item) {
            try {
                PurchaseOrderItem::updateOrCreate(
                    ['jubelio_purchaseorder_detail_id' => $item['purchaseorder_detail_id']],
                    [
                        'purchase_order_id'  => $po->id,
                        'item_id'            => $item['item_id']           ?? null,
                        'item_group_id'      => $item['item_group_id']     ?? null,
                        'item_code'          => $item['item_code']         ?? null,
                        'item_name'          => $item['item_name']         ?? null,
                        'description'        => $item['description']       ?? null,
                        'qty'                => $item['qty']               ?? 0,
                        'qty_in_base'        => $item['qty_in_base']       ?? 0,
                        'uom_id'             => $item['uom_id']            ?? null,
                        'unit'               => $item['unit']              ?? null,
                        'price'              => $item['price']             ?? 0,
                        'buy_price'          => $item['buy_price']         ?? 0,
                        'last_price_receive' => $item['last_price_receive'] ?? 0,
                        'original_price'     => $item['original_price']    ?? 0,
                        'disc'               => $item['disc']              ?? 0,
                        'disc_amount'        => $item['disc_amount']       ?? 0,
                        'tax_id'             => $item['tax_id']            ?? null,
                        'tax_name'           => $item['tax_name']          ?? null,
                        'tax_amount'         => $item['tax_amount']        ?? 0,
                        'rate'               => $item['rate']              ?? 0,
                        'amount'             => $item['amount']            ?? 0,
                        'variant'            => $item['variant']           ?? null,
                        'thumbnail'          => $item['thumbnail']         ?? null,
                        'raw_payload'        => $item,
                        'sync_from_jubelio'    => true,
                        'sync_from_jubelio_at' => now(),
                        'sync_from_jubelio_error' => null,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('[Jubelio PO] Gagal upsert item', [
                    'purchaseorder_detail_id' => $item['purchaseorder_detail_id'] ?? null,
                    'error'                   => $e->getMessage(),
                ]);

                SyncLog::record(
                    entityType: SyncLog::ENTITY_PURCHASE_ORDER_ITEM,
                    entityId:   $po->id,
                    direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                    status:     SyncLog::STATUS_FAILED,
                    message:    $e->getMessage(),
                    context:    ['item' => $item],
                );
            }
        }
    }
}