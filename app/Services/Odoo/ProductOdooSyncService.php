<?php

namespace App\Services\Odoo;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\SyncLog;
use App\Services\OdooService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductOdooSyncService
{
    public function __construct(
        private readonly OdooService $odoo
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // Push semua produk yang belum sync ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushAll(): void
    {
        $products = Product::where('sync_to_odoo', false)
            ->readyToRetry()
            ->with('variants')
            ->get();

        Log::info("[Odoo Product] Push {$products->count()} produk ke Odoo");

        foreach ($products as $product) {
            $this->pushOne($product);
        }

        Log::info('[Odoo Product] Push selesai.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Push satu produk ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushOne(Product $product): void
    {
        Log::info("[Odoo Product] Push produk #{$product->jubelio_item_group_id} — {$product->item_name}");

        try {
            DB::transaction(function () use ($product) {

                // 1. Cek apakah produk sudah ada di Odoo berdasarkan nama
                $existing = $this->findTemplateInOdoo($product->item_name);

                if ($existing) {
                    $odooTemplateId = $existing['id'];
                    $this->updateTemplateInOdoo($odooTemplateId, $product);
                    Log::info("[Odoo Product] Update existing product.template id={$odooTemplateId}");
                } else {
                    $odooTemplateId = $this->createTemplateInOdoo($product);
                    Log::info("[Odoo Product] Created product.template id={$odooTemplateId}");
                }

                // 2. Update DB Laravel — simpan odoo_id di products
                $product->update([
                    'odoo_id'               => $odooTemplateId,
                    'sync_to_odoo'          => true,
                    'sync_to_odoo_at'       => now(),
                    'sync_error'            => null,
                    'sync_to_odoo_attempts' => $product->sync_to_odoo_attempts + 1,
                ]);

                // 3. Sync variants — ambil product.product id dari Odoo
                $this->syncVariants($product, $odooTemplateId);

                // 4. Catat log sukses
                SyncLog::record(
                    entityType: 'product',
                    entityId:   $product->id,
                    direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                    status:     SyncLog::STATUS_SUCCESS,
                    message:    "Product berhasil push ke Odoo dengan template_id={$odooTemplateId}",
                    attempt:    $product->sync_to_odoo_attempts,
                );
            });

        } catch (\Throwable $e) {
            Log::error("[Odoo Product] Gagal push produk #{$product->jubelio_item_group_id}", [
                'error' => $e->getMessage(),
            ]);

            $attempts  = $product->sync_to_odoo_attempts + 1;
            $nextRetry = match (true) {
                $attempts === 1 => now()->addMinutes(1),
                $attempts === 2 => now()->addMinutes(5),
                default         => now()->addMinutes(15),
            };

            $product->update([
                'sync_error'                 => $e->getMessage(),
                'sync_to_odoo_attempts'      => $attempts,
                'sync_to_odoo_next_retry_at' => $nextRetry,
            ]);

            SyncLog::record(
                entityType: 'product',
                entityId:   $product->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['jubelio_item_group_id' => $product->jubelio_item_group_id],
                attempt:    $attempts,
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Sync variants — ambil product.product id dari Odoo lalu simpan ke DB
    // ────────────────────────────────────────────────────────────────────────

    private function syncVariants(Product $product, int $odooTemplateId): void
    {
        $odooVariants = $this->odoo->execute(
            'product.product',
            'search_read',
            [[['product_tmpl_id', '=', $odooTemplateId]]],
            ['fields' => ['id', 'default_code', 'barcode', 'product_tmpl_id']]
        );

        $odooVariantMap = collect($odooVariants)->keyBy('default_code');

        foreach ($product->variants as $variant) {
            $odooVariant = $odooVariantMap->get($variant->item_code);

            if ($odooVariant) {
                $this->odoo->execute(
                    'product.product',
                    'write',
                    [
                        [$odooVariant['id']],
                        [
                            'default_code' => $variant->item_code ?: false,
                            'barcode'      => $variant->barcode    ?: false,
                        ]
                    ]
                );

                $variant->update([
                    'odoo_id'              => $odooVariant['id'],
                    'odoo_product_tmpl_id' => $odooTemplateId,
                    'sync_to_odoo'         => true,
                    'sync_to_odoo_at'      => now(),
                    'sync_error'           => null,
                ]);
            } else {
                $firstVariant = $odooVariants[0] ?? null;

                if ($firstVariant) {
                    $this->odoo->execute(
                        'product.product',
                        'write',
                        [
                            [$firstVariant['id']],
                            [
                                'default_code' => $variant->item_code ?: false,
                                'barcode'      => $variant->barcode    ?: false,
                            ]
                        ]
                    );

                    $variant->update([
                        'odoo_id'              => $firstVariant['id'],
                        'odoo_product_tmpl_id' => $odooTemplateId,
                        'sync_to_odoo'         => true,
                        'sync_to_odoo_at'      => now(),
                        'sync_error'           => null,
                    ]);
                }
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function findTemplateInOdoo(string $name): ?array
    {
        $result = $this->odoo->execute(
            'product.template',
            'search_read',
            [[['name', '=', $name]]],
            ['fields' => ['id', 'name'], 'limit' => 1]
        );

        return $result[0] ?? null;
    }

    private function createTemplateInOdoo(Product $product): int
    {
        return $this->odoo->execute(
            'product.template',
            'create',
            [$this->mapToOdoo($product)]
        );
    }

    private function updateTemplateInOdoo(int $odooId, Product $product): void
    {
        $this->odoo->execute(
            'product.template',
            'write',
            [[$odooId], $this->mapToOdoo($product)]
        );
    }

    /**
     * Mapping field Jubelio → Odoo product.template
     */
    private function mapToOdoo(Product $product): array
    {
        $firstVariant = $product->variants->first();

        // Ambil odoo category id dari tabel product_categories
        $categId = null;
        if ($product->item_category_id) {
            $categId = ProductCategory::getOdooId($product->item_category_id);
        }

        $payload = [
            'name'         => $product->item_name,
            'purchase_ok'  => true,
            'sale_ok'      => true,
            'list_price'   => (float) ($product->sell_price ?? 0),
            'default_code' => $firstVariant?->item_code ?: false,
            'barcode'      => $firstVariant?->barcode    ?: false,
        ];

        // Tambahkan categ_id hanya jika ditemukan mapping-nya
        if ($categId) {
            $payload['categ_id'] = $categId;
        }

        return $payload;
    }
}