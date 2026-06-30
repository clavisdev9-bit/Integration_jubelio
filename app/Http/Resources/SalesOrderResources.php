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

            'is_cod' => $this->is_cod,

            // =========================
            // QTY
            // =========================
            'qty_total' => (float) $this->items->sum('qty_in_base'),

            // =========================
            // COST BREAKDOWN
            // (sesuai bagian "Rincian" di Jubelio)
            // =========================
            'cost_breakdown' => [
                'sub_total'             => (float) $this->sub_total,
                'disc'                  => (float) $this->total_disc,
                'disc_lainnya'          => (float) $this->add_disc,
                'tax'                   => (float) $this->total_tax,
                'shipping_cost'         => (float) $this->shipping_cost,
                'biaya_lainnya'         => (float) $this->add_fee,
                'grand_total'           => (float) $this->grand_total,
            ],

            'sub_total' => (float) $this->sub_total,

            'total_disc' => (float) $this->total_disc,

            'total_tax' => (float) $this->total_tax,

            'grand_total' => (float) $this->grand_total,

            'is_paid' => (bool) $this->is_paid,

            'is_canceled' => (bool) $this->is_canceled,

            'cancel_reason' => $this->cancel_reason,

            // =========================
            // PENERIMA / SHIPPING
            // (sesuai bagian "Penerima" & "Pengiriman" di Jubelio)
            // =========================
            'shipping' => [
                'full_name'   => $this->shipping_full_name,
                'address'     => $this->shipping_address,
                'city'        => $this->shipping_city,
                'province'    => $this->shipping_province,
                'post_code'   => $this->shipping_post_code,
                'country'     => $this->shipping_country,
                'phone'       => $this->customer_phone,
                'is_cod'      => (bool) $this->is_cod,
                'courier'     => $this->courier,
                'shipper'     => $this->shipper,
                'tracking_no' => $this->tracking_number,
            ],

            // =========================
            // ITEMS / PRODUK
            // (sesuai bagian "Produk" di Jubelio)
            // =========================
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'item_name'   => $item->item_name,
                        'item_code'   => $item->item_code,
                        'qty'         => (float) $item->qty_in_base,
                        'price'       => (float) $item->price,
                        'disc'        => (float) $item->disc,
                        'disc_amount' => (float) $item->disc_amount,
                        'tax_amount'  => (float) $item->tax_amount,
                        'amount'      => (float) $item->amount,
                        'thumbnail'   => $item->thumbnail,
                    ];
                });
            }),

            // =========================
            // SYNC
            // =========================
            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

            'sync_to_odoo' => (bool) $this->sync_to_odoo,

            'created_at' => $this->created_at?->toDateTimeString(),

            'updated_at' => $this->updated_at?->toDateTimeString(),

        ];
    }
}