<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'salesorder_no' => $this->salesorder_no,

            'invoice_no' => $this->invoice_no,

            'ref_no' => $this->ref_no,

            'customer_name' => $this->customer_name,

            'customer_phone' => $this->customer_phone,

            'customer_email' => $this->customer_email,

            'channel_name' => $this->channel_name,

            'store_name' => $this->store_name,

            'transaction_date' => $this->transaction_date?->toDateString(),

            'completed_date' => $this->completed_date?->toDateString(),

            'internal_status' => $this->internal_status,

            'channel_status' => $this->channel_status,

            'wms_status' => $this->wms_status,

            'payment_method' => $this->payment_method,

            'location_name' => $this->location_name,

            'sub_total' => (float) $this->sub_total,

            'total_disc' => (float) $this->total_disc,

            'total_tax' => (float) $this->total_tax,

            'grand_total' => (float) $this->grand_total,

            'is_paid' => (bool) $this->is_paid,

            'is_canceled' => (bool) $this->is_canceled,

            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

            'sync_to_odoo' => (bool) $this->sync_to_odoo,

            'created_at' => $this->created_at?->toDateTimeString(),

            'updated_at' => $this->updated_at?->toDateTimeString(),

        ];
    }
}