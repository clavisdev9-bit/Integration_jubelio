<?php

namespace App\Services\Jubelio;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesOrderSyncService
{
    public function __construct(
        private readonly JubelioClient $client
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // STEP 1 — Simpan list SO (dari /sales/orders/)
    // ────────────────────────────────────────────────────────────────────────

    public function syncList(int $pageSize = 20): void
    {
        $page  = 1;
        $total = null;
        $saved = 0;

        do {
            Log::info("[Jubelio SO] Fetching list page {$page}");

            $response = $this->client->get('/sales/orders/', [
                'page'     => $page,
                'pageSize' => $pageSize,
            ]);

            $total ??= $response['totalCount'] ?? 0;
            $rows    = $response['data'] ?? [];

            foreach ($rows as $row) {
                $this->upsertHeader($row);
                $saved++;
            }

            $page++;

        } while (($page - 1) * $pageSize < $total);

        Log::info("[Jubelio SO] List sync selesai. Total: {$saved} SO disimpan.");
    }

    /**
     * Simpan / update satu baris dari response list.
     */
    private function upsertHeader(array $row): void
    {
        try {
            SalesOrder::updateOrCreate(
                ['jubelio_salesorder_id' => $row['salesorder_id']],
                [
                    'salesorder_no'        => $row['salesorder_no']        ?? null,
                    'invoice_no'           => $row['invoice_no']           ?? null,
                    'contact_id'           => $row['contact_id']           ?? null,
                    'customer_name'        => $row['customer_name']        ?? null,
                    'transaction_date'     => $row['transaction_date']     ?? null,
                    'grand_total'          => $row['grand_total']          ?? 0,
                    'channel_name'         => $row['channel_name']         ?? null,
                    'store_name'           => $row['store_name']           ?? null,
                    'store_id'             => $row['store_id']             ?? null,
                    'channel_id'           => $row['channel_id']           ?? null,
                    'internal_status'      => $row['internal_status']      ?? null,
                    'channel_status'       => $row['channel_status']       ?? null,
                    'wms_status'           => $row['wms_status']           ?? null,
                    'tracking_number'      => $row['tracking_number']      ?? null,
                    'shipper'              => $row['shipper']              ?? null,
                    'shipping_full_name'   => $row['shipping_full_name']   ?? null,
                    'is_paid'              => $row['is_paid']              ?? false,
                    'is_canceled'          => $row['is_canceled']          ?? false,
                    'marked_as_complete'   => $row['marked_as_complete']   ?? false,
                    'ref_no'               => $row['ref_no']               ?? null,
                    'source'               => $row['source']               ?? null,
                    'last_modified'        => $row['last_modified']        ?? null,
                    'note'                 => $row['note']                 ?? null,
                    'raw_payload'          => $row,
                    'sync_from_jubelio'    => true,
                    'sync_from_jubelio_at' => now(),
                    'sync_from_jubelio_error' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Jubelio SO] Gagal upsert header', [
                'salesorder_id' => $row['salesorder_id'] ?? null,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // STEP 2 — Fetch & simpan detail SO (dari /sales/orders/{id})
    // ────────────────────────────────────────────────────────────────────────

    public function syncDetail(SalesOrder $so): void
    {
        Log::info("[Jubelio SO] Fetch detail SO #{$so->jubelio_salesorder_id}");

        try {
            $detail = $this->client->get("/sales/orders/{$so->jubelio_salesorder_id}");

            DB::transaction(function () use ($so, $detail) {
                $this->updateDetail($so, $detail);
                $this->upsertItems($so, $detail['items'] ?? []);
            });

            SyncLog::record(
                entityType: 'sales_order',
                entityId:   $so->id,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_SUCCESS,
                message:    'Detail SO berhasil disimpan',
                attempt:    1,
            );

        } catch (\Throwable $e) {
            Log::error("[Jubelio SO] Gagal fetch detail SO #{$so->jubelio_salesorder_id}", [
                'error' => $e->getMessage(),
            ]);

            $so->update(['sync_from_jubelio_error' => $e->getMessage()]);

            SyncLog::record(
                entityType: 'sales_order',
                entityId:   $so->id,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['jubelio_id' => $so->jubelio_salesorder_id],
            );

            throw $e;
        }
    }

    /**
     * Update kolom detail SO.
     */
    private function updateDetail(SalesOrder $so, array $detail): void
    {
        $so->update([
            'contact_id'           => $detail['contact_id']           ?? null,
            'customer_phone'       => $detail['customer_phone']       ?? null,
            'customer_email'       => $detail['customer_email']       ?? null,
            'is_tax_included'      => $detail['is_tax_included']      ?? false,
            'sub_total'            => $detail['sub_total']            ?? 0,
            'total_disc'           => $detail['total_disc']           ?? 0,
            'total_tax'            => $detail['total_tax']            ?? 0,
            'grand_total'          => $detail['grand_total']          ?? 0,
            'add_disc'             => $detail['add_disc']             ?? 0,
            'add_fee'              => $detail['add_fee']              ?? 0,
            'shipping_cost'        => $detail['shipping_cost']        ?? 0,
            'shipping_address'     => $detail['shipping_address']     ?? null,
            'shipping_city'        => $detail['shipping_city']        ?? null,
            'shipping_province'    => $detail['shipping_province']    ?? null,
            'shipping_post_code'   => $detail['shipping_post_code']   ?? null,
            'shipping_country'     => $detail['shipping_country']     ?? null,
            'payment_method'       => $detail['payment_method']       ?? null,
            'location_id'          => $detail['location_id']          ?? null,
            'location_name'        => $detail['location_name']        ?? null,
            'invoice_id'           => $detail['invoice_id']           ?? null,
            'invoice_created_date' => $detail['invoice_created_date'] ?? null,
            'courier'              => $detail['courier']              ?? null,
            'process_number'       => $detail['process_number']       ?? null,
            'completed_date'       => $detail['completed_date']       ?? null,
            'created_date'         => $detail['created_date']         ?? null,
            'raw_payload'          => $detail,
            'detail_fetched'       => true,
            'detail_fetched_at'    => now(),
            'sync_from_jubelio'    => true,
            'sync_from_jubelio_at' => now(),
            'sync_from_jubelio_error' => null,
        ]);
    }

    /**
     * Upsert semua item dari response detail SO.
     */
    private function upsertItems(SalesOrder $so, array $items): void
    {
        foreach ($items as $item) {
            try {
                SalesOrderItem::updateOrCreate(
                    ['jubelio_salesorder_detail_id' => $item['salesorder_detail_id']],
                    [
                        'sales_order_id'    => $so->id,
                        'item_id'           => $item['item_id']           ?? null,
                        'item_group_id'     => $item['item_group_id']     ?? null,
                        'item_code'         => $item['item_code']         ?? null,
                        'item_name'         => $item['item_name']         ?? null,
                        'description'       => $item['description']       ?? null,
                        'qty'               => $item['qty']               ?? 0,
                        'qty_in_base'       => $item['qty_in_base']       ?? 0,
                        'uom_id'            => $item['uom_id']            ?? null,
                        'unit'              => $item['unit']              ?? null,
                        'price'             => $item['price']             ?? 0,
                        'sell_price'        => $item['sell_price']        ?? 0,
                        'original_price'    => $item['original_price']    ?? 0,
                        'disc'              => $item['disc']              ?? 0,
                        'disc_amount'       => $item['disc_amount']       ?? 0,
                        'disc_marketplace'  => $item['disc_marketplace']  ?? 0,
                        'tax_id'            => $item['tax_id']            ?? null,
                        'tax_name'          => $item['tax_name']          ?? null,
                        'tax_amount'        => $item['tax_amount']        ?? 0,
                        'rate'              => $item['rate']              ?? 0,
                        'amount'            => $item['amount']            ?? 0,
                        'variant'           => $item['variant']           ?? null,
                        'thumbnail'         => $item['thumbnail']         ?? null,
                        'is_bundle'         => $item['is_bundle']         ?? false,
                        'is_free_gift'      => $item['is_free_gift']      ?? false,
                        'weight_in_gram'    => $item['weight_in_gram']    ?? 0,
                        'raw_payload'       => $item,
                        'sync_from_jubelio'    => true,
                        'sync_from_jubelio_at' => now(),
                        'sync_from_jubelio_error' => null,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('[Jubelio SO] Gagal upsert item', [
                    'salesorder_detail_id' => $item['salesorder_detail_id'] ?? null,
                    'error'                => $e->getMessage(),
                ]);

                SyncLog::record(
                    entityType: 'sales_order_item',
                    entityId:   $so->id,
                    direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                    status:     SyncLog::STATUS_FAILED,
                    message:    $e->getMessage(),
                    context:    ['item' => $item],
                );
            }
        }
    }
}