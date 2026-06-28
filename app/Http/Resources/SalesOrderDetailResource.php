<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Header
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'salesorder_no' => $this->salesorder_no,

            'invoice_no' => $this->invoice_no,

            'ref_no' => $this->ref_no,

            'source' => $this->source,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' => [

                'contact_id' => $this->contact_id,

                'name' => $this->customer_name,

                'phone' => $this->customer_phone,

                'email' => $this->customer_email,

            ],

            /*
            |--------------------------------------------------------------------------
            | Marketplace
            |--------------------------------------------------------------------------
            */

            'marketplace' => [

                'channel_id' => $this->channel_id,

                'channel_name' => $this->channel_name,

                'store_id' => $this->store_id,

                'store_name' => $this->store_name,

            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'shipping' => [

                'receiver' => $this->shipping_full_name,

                'address' => $this->shipping_address,

                'city' => $this->shipping_city,

                'province' => $this->shipping_province,

                'postal_code' => $this->shipping_post_code,

                'country' => $this->shipping_country,

                'courier' => $this->courier,

                'shipper' => $this->shipper,

                'tracking_number' => $this->tracking_number,

                'shipping_cost' => (float) $this->shipping_cost,

            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment' => [

                'payment_method' => $this->payment_method,

                'is_paid' => (bool) $this->is_paid,

                'is_tax_included' => (bool) $this->is_tax_included,

            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [

                'internal_status' => $this->internal_status,

                'channel_status' => $this->channel_status,

                'wms_status' => $this->wms_status,

                'marketplace_complete' => (bool) $this->marketplace_complete,

                'is_canceled' => (bool) $this->is_canceled,

                'cancel_reason' => $this->cancel_reason,

            ],

            /*
            |--------------------------------------------------------------------------
            | Warehouse
            |--------------------------------------------------------------------------
            */

            'warehouse' => [

                'location_id' => $this->location_id,

                'location_name' => $this->location_name,

            ],

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' => [

                'sub_total' => (float) $this->sub_total,

                'total_disc' => (float) $this->total_disc,

                'total_tax' => (float) $this->total_tax,

                'add_disc' => (float) $this->add_disc,

                'add_fee' => (float) $this->add_fee,

                'grand_total' => (float) $this->grand_total,

            ],

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'date' => [

                'transaction_date' => $this->transaction_date?->toDateTimeString(),

                'created_date' => $this->created_date?->toDateTimeString(),

                'completed_date' => $this->completed_date?->toDateTimeString(),

                'last_modified' => $this->last_modified?->toDateTimeString(),

            ],

            /*
            |--------------------------------------------------------------------------
            | Integration
            |--------------------------------------------------------------------------
            */

            'integration' => [

                'detail_fetched' => (bool) $this->detail_fetched,

                'detail_fetched_at' => $this->detail_fetched_at?->toDateTimeString(),

                'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

                'sync_from_jubelio_at' => $this->sync_from_jubelio_at?->toDateTimeString(),

                'sync_from_jubelio_error' => $this->sync_from_jubelio_error,

                'sync_to_odoo' => (bool) $this->sync_to_odoo,

                'sync_to_odoo_at' => $this->sync_to_odoo_at?->toDateTimeString(),

                'sync_error' => $this->sync_error,

                'sync_to_odoo_attempts' => $this->sync_to_odoo_attempts,

                'sync_to_odoo_next_retry_at' => $this->sync_to_odoo_next_retry_at?->toDateTimeString(),

            ],

            /*
            |--------------------------------------------------------------------------
            | Note
            |--------------------------------------------------------------------------
            */

            'note' => $this->note,

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => SalesOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            /*
            |--------------------------------------------------------------------------
            | Timestamp
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->toDateTimeString(),

            'updated_at' => $this->updated_at?->toDateTimeString(),

        ];
    }
}
