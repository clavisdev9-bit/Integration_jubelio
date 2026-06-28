<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Identifier
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'purchaseorder_no' => $this->purchaseorder_no,

            'reference_no' => $this->ref_no,

            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

            'supplier' => [

                'id' => $this->contact_id,

                'name' => $this->supplier_name,

                'email' => $this->supplier_email,

            ],

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),

            'status' => $this->status,

            'is_closed' => (bool) $this->is_closed,

            'payment_method' => $this->payment_method,

            'payment_term' => $this->payment_term,

            'source' => $this->source,

            'bills' => $this->bills,

            'note' => $this->note,

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            'location' => [

                'id' => $this->location_id,

                'code' => $this->location_code,

                'name' => $this->location_name,

            ],

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' => [

                'sub_total' => $this->sub_total,

                'total_disc' => $this->total_disc,

                'total_tax' => $this->total_tax,

                'grand_total' => $this->grand_total,

            ],

            /*
            |--------------------------------------------------------------------------
            | Integration
            |--------------------------------------------------------------------------
            */

            'integration' => [

                'detail_fetched' => (bool) $this->detail_fetched,

                'detail_fetched_at' => $this->detail_fetched_at?->format('Y-m-d H:i:s'),

                'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

                'sync_from_jubelio_at' => $this->sync_from_jubelio_at?->format('Y-m-d H:i:s'),

                'sync_from_jubelio_error' => $this->sync_from_jubelio_error,

                'sync_to_odoo' => (bool) $this->sync_to_odoo,

                'sync_to_odoo_at' => $this->sync_to_odoo_at?->format('Y-m-d H:i:s'),

                'sync_error' => $this->sync_error,

            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => PurchaseOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            /*
            |--------------------------------------------------------------------------
            | Timestamp
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

        ];
    }
}