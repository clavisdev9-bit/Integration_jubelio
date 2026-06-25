<?php

namespace App\Services\Jubelio;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSyncServices
{
    public function __construct(
        private readonly JubelioClient $client
    ) {}

    /**
     * Fetch semua halaman produk dari Jubelio dan simpan ke DB.
     * Setiap produk (item_group) beserta variant-nya langsung disimpan
     * dalam satu request karena response sudah include variants.
     */
    public function syncAll(int $pageSize = 20): void
    {
        $page  = 1;
        $total = null;
        $saved = 0;

        do {
            Log::info("[Jubelio Product] Fetching page {$page}");

            $response = $this->client->get('/inventory/items/masters', [
                'page'     => $page,
                'pageSize' => $pageSize,
            ]);

            $total ??= $response['totalCount'] ?? 0;
            $rows    = $response['data'] ?? [];

            foreach ($rows as $row) {
                $this->upsertProduct($row);
                $saved++;
            }

            $page++;

        } while (($page - 1) * $pageSize < $total);

        Log::info("[Jubelio Product] Sync selesai. Total: {$saved} produk disimpan.");
    }

    /**
     * Upsert satu produk (item_group) beserta semua variantnya.
     */
    private function upsertProduct(array $row): void
    {
        try {
            DB::transaction(function () use ($row) {

                // 1. Simpan header produk
                $product = Product::updateOrCreate(
                    ['jubelio_item_group_id' => $row['item_group_id']],
                    [
                        'item_name'            => $row['item_name']         ?? '',
                        'item_category_id'     => $row['item_category_id']  ?? null,
                        'sell_price'           => $row['sell_price']        ?? null,
                        'thumbnail'            => $row['thumbnail']         ?? null,
                        'total_composition'    => $row['total_composition'] ?? 0,
                        'is_consignment'       => $row['is_consignment']    ?? false,
                        'variations'           => $row['variations']        ?? null,
                        'last_modified'        => $row['last_modified']     ?? null,
                        'raw_payload'          => $row,
                        'sync_from_jubelio'    => true,
                        'sync_from_jubelio_at' => now(),
                        'sync_from_jubelio_error' => null,
                    ]
                );

                // 2. Simpan semua variants
                $this->upsertVariants($product, $row['variants'] ?? []);
            });

        } catch (\Throwable $e) {
            Log::error('[Jubelio Product] Gagal upsert produk', [
                'item_group_id' => $row['item_group_id'] ?? null,
                'error'         => $e->getMessage(),
            ]);

            SyncLog::record(
                entityType: 'product',
                entityId:   0,
                direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['item_group_id' => $row['item_group_id'] ?? null],
            );
        }
    }

    /**
     * Upsert semua variant dari satu produk.
     */
    private function upsertVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            try {
                ProductVariant::updateOrCreate(
                    ['jubelio_item_id' => $variant['item_id']],
                    [
                        'product_id'           => $product->id,
                        'jubelio_item_group_id' => $variant['item_group_id']   ?? null,
                        'item_code'            => $variant['item_code']        ?? null,
                        'item_name'            => $variant['item_name']        ?? '',
                        'barcode'              => $variant['barcode']          ?? null,
                        'thumbnail'            => $variant['thumbnail']        ?? null,
                        'is_bundle'            => $variant['is_bundle']        ?? false,
                        'invt_acct_id'         => $variant['invt_acct_id']     ?? null,
                        'tax_rate'             => $variant['tax_rate']         ?? 0,
                        'sell_price'           => $variant['sell_price']       ?? null,
                        'variation_values'     => $variant['variation_values'] ?? null,
                        'end_qty'              => $variant['end_qty']          ?? null,
                        'order_qty'            => $variant['order_qty']        ?? null,
                        'available_qty'        => $variant['available_qty']    ?? null,
                        'raw_payload'          => $variant,
                        'sync_from_jubelio'    => true,
                        'sync_from_jubelio_at' => now(),
                        'sync_from_jubelio_error' => null,
                    ]
                );

            } catch (\Throwable $e) {
                Log::error('[Jubelio Product] Gagal upsert variant', [
                    'item_id' => $variant['item_id'] ?? null,
                    'error'   => $e->getMessage(),
                ]);

                SyncLog::record(
                    entityType: 'product_variant',
                    entityId:   $product->id,
                    direction:  SyncLog::DIRECTION_JUBELIO_TO_LARAVEL,
                    status:     SyncLog::STATUS_FAILED,
                    message:    $e->getMessage(),
                    context:    ['variant' => $variant],
                );
            }
        }
    }
}