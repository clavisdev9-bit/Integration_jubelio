<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
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

            'jubelio_salesorder_detail_id' => $this->jubelio_salesorder_detail_id,

            'item_id' => $this->item_id,

            'item_group_id' => $this->item_group_id,

            'item_code' => $this->item_code,

            'item_name' => $this->item_name,

            'description' => $this->description,

            'variant' => $this->variant,

            'thumbnail' => $this->thumbnail,

            'qty' => (float) $this->qty,

            'qty_in_base' => (float) $this->qty_in_base,

            'unit' => $this->unit,

            'price' => (float) $this->price,

            'buy_price' => (float) $this->buy_price,

            'last_buy_price' => (float) $this->last_buy_price,

            'original_price' => (float) $this->original_price,

            'disc' => (float) $this->disc,

            'disc_amount' => (float) $this->disc_amount,

            'tax_id' => $this->tax_id,

            'tax_name' => $this->tax_name,

            'tax_amount' => (float) $this->tax_amount,

            'rate' => (float) $this->rate,

            'amount' => (float) $this->amount,

            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

            'sync_to_odoo' => (bool) $this->sync_to_odoo,

            'created_at' => $this->created_at?->toDateTimeString(),

            'updated_at' => $this->updated_at?->toDateTimeString(),

        ];
    }
}