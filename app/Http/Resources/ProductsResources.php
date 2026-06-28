<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Hindari N+1 / repeated loading safety
        $variants = $this->whenLoaded('variants', fn () => $this->variants, collect());
        $firstVariant = $variants->first();

        $rawPayload = $this->raw_payload ?? [];

        return [
            // =========================
            // PRODUCT
            // =========================
            'id' => $this->id,
            'item_name' => $this->item_name,
            'thumbnail' => $this->thumbnail,
            'item_category_id' => $this->item_category_id,
            'category_name' => $this->category?->category_name,

            // =========================
            // PRODUCT TYPE
            // =========================
            'is_bundle' => $variants->contains('is_bundle', true),
            'is_consignment' => (bool) $this->is_consignment,
            'total_composition' => $this->total_composition,

            // =========================
            // SKU INFO
            // =========================
            'sku' => $firstVariant?->item_code,
            'barcode' => $firstVariant?->barcode,

            'stock' => $variants->sum('available_qty'),
            'price' => $firstVariant?->sell_price,

            'variant_count' => $variants->count(),

            'channel_count' => count($rawPayload['online_status'] ?? []),

            // =========================
            // ODOO
            // =========================
            'odoo_id' => $this->odoo_id,
            'odoo_ref' => $this->odoo_ref,

            // =========================
            // VARIANTS
            // =========================
            'variants' => $variants->map(fn ($variant) => [
                'id' => $variant->id,
                'item_code' => $variant->item_code,
                'item_name' => $variant->item_name,
                'barcode' => $variant->barcode,
                'thumbnail' => $variant->thumbnail,
                'sell_price' => $variant->sell_price,
                'available_qty' => $variant->available_qty,
                'order_qty' => $variant->order_qty,
                'end_qty' => $variant->end_qty,
                'is_bundle' => (bool) $variant->is_bundle,
                'variation_values' => $variant->variation_values,
            ]),

            // =========================
            // CHANNELS
            // =========================
            'channels' => collect($rawPayload['online_status'] ?? [])
                ->map(fn ($channel) => [
                    'channel_id' => $channel['channel_id'] ?? null,
                    'store_name' => $channel['store_name'] ?? null,
                    'channel_url' => $channel['channel_url'] ?? null,
                ])
                ->values(),

            // =========================
            // SYNC
            // =========================
            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,
            'sync_to_odoo' => (bool) $this->sync_to_odoo,
            'sync_error' => $this->sync_error,

            // =========================
            // DATE
            // =========================
            'last_modified' => $this->last_modified?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}