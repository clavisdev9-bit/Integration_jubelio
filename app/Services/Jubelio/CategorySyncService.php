<?php

namespace App\Services\Jubelio;

use App\Models\ProductCategory;
use App\Services\OdooService;
use Illuminate\Support\Facades\Log;

class CategorySyncService
{
    public function __construct(
        private readonly JubelioClient $client,
        private readonly OdooService   $odoo,
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // STEP 1 — Fetch semua kategori dari Jubelio, simpan ke DB
    // ────────────────────────────────────────────────────────────────────────

    public function syncFromJubelio(): void
    {
        Log::info('[Category] Fetching kategori dari Jubelio...');

        $rows = $this->client->get('/inventory/categories/item-categories/');

        // Response endpoint ini langsung array (bukan { data, totalCount })
        if (isset($rows['data'])) {
            $rows = $rows['data'];
        }

        foreach ($rows as $row) {
            ProductCategory::updateOrCreate(
                ['jubelio_category_id' => $row['category_id']],
                [
                    'category_name'        => $row['category_name'],
                    'parent_id'            => $row['parent_id'] ?? null,
                    'last_modified'        => $row['last_modified'] ?? null,
                    'raw_payload'          => $row,
                    'sync_from_jubelio'    => true,
                    'sync_from_jubelio_at' => now(),
                ]
            );
        }

        Log::info('[Category] Fetch selesai. Total: ' . count($rows) . ' kategori disimpan.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // STEP 2 — Push semua kategori ke Odoo (yang belum sync)
    // ────────────────────────────────────────────────────────────────────────

    public function pushToOdoo(): void
    {
        $categories = ProductCategory::where('sync_to_odoo', false)->get();

        Log::info("[Category] Push {$categories->count()} kategori ke Odoo...");

        foreach ($categories as $category) {
            $this->pushOne($category);
        }

        Log::info('[Category] Push selesai.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Push satu kategori ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    private function pushOne(ProductCategory $category): void
    {
        try {
            // Cek apakah sudah ada di Odoo berdasarkan nama
            $existing = $this->odoo->execute(
                'product.category',
                'search_read',
                [[['name', '=', $category->category_name]]],
                ['fields' => ['id', 'name'], 'limit' => 1]
            );

            if (! empty($existing)) {
                $odooId = $existing[0]['id'];
            } else {
                // Cari parent_id di Odoo jika ada
                $odooParentId = null;
                if ($category->parent_id) {
                    $odooParentId = ProductCategory::getOdooId($category->parent_id)
                                   ?? 1; // fallback ke "All" jika parent belum sync
                }

                $payload = ['name' => $category->category_name];
                if ($odooParentId) {
                    $payload['parent_id'] = $odooParentId;
                }

                $odooId = $this->odoo->execute('product.category', 'create', [$payload]);
            }

            $category->update([
                'odoo_id'        => $odooId,
                'sync_to_odoo'   => true,
                'sync_to_odoo_at' => now(),
                'sync_error'     => null,
            ]);

            Log::info("[Category] '{$category->category_name}' → odoo_id={$odooId}");

        } catch (\Throwable $e) {
            Log::error("[Category] Gagal push '{$category->category_name}'", [
                'error' => $e->getMessage(),
            ]);

            $category->update(['sync_error' => $e->getMessage()]);
        }
    }
}