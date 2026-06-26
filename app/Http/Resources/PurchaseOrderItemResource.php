<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

    'id'=>$this->id,

    'item_code'=>$this->item_code,

    'item_name'=>$this->item_name,

    'description'=>$this->description,

    'thumbnail'=>$this->thumbnail,

    'qty'=>$this->qty,

    'unit'=>$this->unit,

    'price'=>$this->price,

    'discount'=>$this->disc,

    'tax'=>$this->tax_amount,

    'amount'=>$this->amount

];
    }
}
