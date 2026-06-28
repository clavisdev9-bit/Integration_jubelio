<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Item
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,

            'item_id' => $this->item_id,

            'item_code' => $this->item_code,

            'item_name' => $this->item_name,

            'description' => $this->description,

            'variant' => $this->variant,

            'thumbnail' => $this->thumbnail,

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'qty' => $this->qty,

            'unit' => $this->unit,

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'price' => $this->price,

            'disc' => $this->disc,

            'disc_amount' => $this->disc_amount,

            'tax_amount' => $this->tax_amount,

            'amount' => $this->amount,

        ];
    }
}