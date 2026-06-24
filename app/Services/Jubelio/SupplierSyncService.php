<?php

namespace App\Services\Jubelio;

use App\Models\Supplier;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class SupplierSyncService
{
    public function __construct(
        private readonly JubelioClient $client
    ) {}

    /**
     * Fetch semua halaman supplier dari Jubelio dan simpan ke DB.
     */
    public function syncAll(int $pageSize = 20): void
    {
        $page  = 1;
        $total = null;
        $saved = 0;

        do {
            Log::info("[Jubelio Supplier] Fetching page {$page}");

            $response = $this->client->get('/contacts/suppliers/', [
                'page'     => $page,
                'pageSize' => $pageSize,
            ]);

            $total ??= $response['totalCount'] ?? 0;
            $rows    = $response['data'] ?? [];

            foreach ($rows as $row) {
                $this->upsertSupplier($row);
                $saved++;
            }

            $page++;

        } while (($page - 1) * $pageSize < $total);

        Log::info("[Jubelio Supplier] Sync selesai. Total: {$saved} supplier disimpan.");
    }

    /**
     * Upsert satu supplier ke DB.
     */
    private function upsertSupplier(array $row): void
    {
        try {
            Supplier::updateOrCreate(
                ['jubelio_contact_id' => $row['contact_id']],
                [
                    'contact_name'     => $row['contact_name']     ?? '',
                    'contact_full'     => $row['contact_full']     ?? null,
                    'contact_type'     => $row['contact_type']     ?? null,
                    'primary_contact'  => $row['primary_contact']  ?? null,
                    'contact_position' => $row['contact_position'] ?? null,
                    'email'            => $row['email']            ?? null,
                    'phone'            => $row['phone']            ?? null,
                    'mobile'           => $row['mobile']           ?? null,
                    'fax'              => $row['fax']              ?? null,
                    'npwp'             => $row['npwp']             ?? null,
                    'payment_term'     => $row['payment_term']     ?? null,
                    'notes'            => $row['notes']            ?? null,

                    // Alamat pengiriman
                    'shipping_address'  => $row['shipping_address']  ?? null,
                    'shipping_area'     => $row['shipping_area']     ?? null,
                    'shipping_city'     => $row['shipping_city']     ?? null,
                    'shipping_province' => $row['shipping_province'] ?? null,
                    'shipping_postcode' => $row['shipping_postcode'] ?? null,

                    // Alamat tagihan
                    'billing_address'   => $row['billing_address']   ?? null,
                    'billing_area'      => $row['billing_area']      ?? null,
                    'billing_city'      => $row['billing_city']      ?? null,
                    'billing_province'  => $row['billing_province']  ?? null,
                    'billing_post_code' => $row['billing_post_code'] ?? null,

                    // Flags
                    'is_dropshipper' => $row['is_dropshipper'] ?? false,
                    'is_reseller'    => $row['is_reseller']    ?? false,

                    // Kategori
                    'category_id'      => $row['category_id']      ?? null,
                    'category_display' => $row['category_display'] ?? null,

                    // Raw & sync
                    'raw_payload'             => $row,
                    'sync_from_jubelio'       => true,
                    'sync_from_jubelio_at'    => now(),
                    'sync_from_jubelio_error' => null,
                ]
            );

        } catch (\Throwable $e) {
            Log::error('[Jubelio Supplier] Gagal upsert supplier', [
                'contact_id' => $row['contact_id'] ?? null,
                'error'      => $e->getMessage(),
            ]);

            SyncLog::record(
                entityType: 'supplier',
                entityId:   0,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['contact_id' => $row['contact_id'] ?? null],
            );
        }
    }
}