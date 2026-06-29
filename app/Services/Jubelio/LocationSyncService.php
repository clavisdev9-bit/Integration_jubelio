<?php

namespace App\Services\Jubelio;

use App\Models\Location;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class LocationSyncService
{
    public function __construct(
        private readonly JubelioClient $client
    ) {}

    public function syncAll(int $pageSize = 20): void
    {
        $page  = 1;
        $total = null;
        $saved = 0;

        do {
            Log::info("[Jubelio Location] Fetching page {$page}");

            $response = $this->client->get('/locations/', [
                'page'     => $page,
                'pageSize' => $pageSize,
            ]);

            $total ??= $response['totalCount'] ?? 0;
            $rows    = $response['data'] ?? [];

            foreach ($rows as $row) {
                $this->upsertLocation($row);
                $saved++;
            }

            $page++;

        } while (($page - 1) * $pageSize < $total);

        Log::info("[Jubelio Location] Sync selesai. Total: {$saved} lokasi disimpan.");
    }

    private function upsertLocation(array $row): void
    {
        try {
            Location::updateOrCreate(
                ['jubelio_location_id' => $row['location_id']],
                [
                    'location_code'          => $row['location_code']          ?? null,
                    'location_name'          => $row['location_name']          ?? '',
                    'location_type'          => $row['location_type']          ?? null,
                    'address'                => $row['address']                ?? null,
                    'area'                   => $row['area']                   ?? null,
                    'city'                   => $row['city']                   ?? null,
                    'province'               => $row['province']               ?? null,
                    'post_code'              => $row['post_code']              ?? null,
                    'subdistrict'            => $row['subdistrict']            ?? null,
                    'province_id'            => $row['province_id']            ?? null,
                    'city_id'                => $row['city_id']                ?? null,
                    'district_id'            => $row['district_id']            ?? null,
                    'subdistrict_id'         => $row['subdistrict_id']        ?? null,
                    'phone'                  => $row['phone']                  ?? null,
                    'email'                  => $row['email']                  ?? null,
                    'contact_name'           => $row['contact_name']           ?? null,
                    'is_active'              => $row['is_active']              ?? true,
                    'is_warehouse'           => $row['is_warehouse']           ?? false,
                    'is_pos_outlet'          => $row['is_pos_outlet']          ?? false,
                    'is_fbl'                 => $row['is_fbl']                 ?? false,
                    'is_tcb'                 => $row['is_tcb']                 ?? false,
                    'is_sbs'                 => $row['is_sbs']                 ?? false,
                    'is_o2o'                 => $row['is_o2o']                 ?? false,
                    'is_multi_origin'        => $row['is_multi_origin']        ?? false,
                    'warehouse_id'           => $row['warehouse_id']           ?? null,
                    'warehouse_store_id'     => $row['warehouse_store_id']     ?? null,
                    'location_group_id'      => $row['location_group_id']      ?? null,
                    'default_warehouse_user' => $row['default_warehouse_user'] ?? null,
                    'source_replenishment'   => $row['source_replenishment']   ?? null,
                    'wms_migration_date'     => $row['wms_migration_date']     ?? null,
                    'raw_payload'            => $row,
                    'sync_from_jubelio'      => true,
                    'sync_from_jubelio_at'   => now(),
                    'sync_from_jubelio_error' => null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[Jubelio Location] Gagal upsert location', [
                'location_id' => $row['location_id'] ?? null,
                'error'       => $e->getMessage(),
            ]);

            SyncLog::record(
                entityType: 'location',
                entityId:   0,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['location_id' => $row['location_id'] ?? null],
            );
        }
    }
}