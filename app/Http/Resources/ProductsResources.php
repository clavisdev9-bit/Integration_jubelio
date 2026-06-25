<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ProductsResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variant = $this->variants->first();

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
            'is_bundle' => (int) $this->total_composition > 0,

            'is_consignment' => $this->is_consignment,

            'total_composition' => $this->total_composition,

            // =========================
            // SKU INFO
            // =========================
            'sku' => $variant?->item_code,

            'barcode' => $variant?->barcode,

            'price' => $variant?->sell_price,

            'stock' => $variant?->available_qty,

            'variant_count' => $this->variants->count(),

            'channel_count' => count(
                $this->raw_payload['online_status'] ?? []
            ),

            // =========================
            // ODOO
            // =========================
            'odoo_id' => $this->odoo_id,

            'odoo_ref' => $this->odoo_ref,

            // =========================
            // VARIANTS
            // =========================
            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,

                    'item_code' => $variant->item_code,

                    'item_name' => $variant->item_name,

                    'barcode' => $variant->barcode,

                    'thumbnail' => $variant->thumbnail,

                    'sell_price' => $variant->sell_price,

                    'available_qty' => $variant->available_qty,

                    'order_qty' => $variant->order_qty,

                    'end_qty' => $variant->end_qty,

                    'is_bundle' => $variant->is_bundle,

                    'variation_values' => $variant->variation_values,
                ];
            }),

            // =========================
            // CHANNELS
            // =========================
            'channels' => collect(
                $this->raw_payload['online_status'] ?? []
            )->map(function ($channel) {
                return [
                    'channel_id' => $channel['channel_id'] ?? null,
                    'store_name' => $channel['store_name'] ?? null,
                    'channel_url' => $channel['channel_url'] ?? null,
                ];
            }),

            // =========================
            // SYNC
            // =========================
            'sync_from_jubelio' => $this->sync_from_jubelio,

            'sync_to_odoo' => $this->sync_to_odoo,

            'sync_error' => $this->sync_error,

            // =========================
            // DATE
            // =========================
            'last_modified' => $this->last_modified
                ? Carbon::parse($this->last_modified)
                    ->format('Y-m-d H:i:s')
                : null,

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)
                    ->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)
                    ->format('Y-m-d H:i:s')
                : null,
        ];
    }
}