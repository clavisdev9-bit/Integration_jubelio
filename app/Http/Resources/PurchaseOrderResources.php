<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResources extends JsonResource
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

            'ref_no' => $this->ref_no,

            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

            'supplier_name' => $this->supplier_name,

            'supplier_email' => $this->supplier_email,

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            'transaction_date' => $this->transaction_date?->format('Y-m-d'),

            'status' => $this->status,

            'payment_method' => $this->payment_method,

            'payment_term' => $this->payment_term,

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            'location_name' => $this->location_name,

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            'sub_total' => $this->sub_total,

            'total_disc' => $this->total_disc,

            'total_tax' => $this->total_tax,

            'grand_total' => $this->grand_total,

            /*
            |--------------------------------------------------------------------------
            | Integration
            |--------------------------------------------------------------------------
            */

            'detail_fetched' => (bool) $this->detail_fetched,

            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

            'sync_to_odoo' => (bool) $this->sync_to_odoo,

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