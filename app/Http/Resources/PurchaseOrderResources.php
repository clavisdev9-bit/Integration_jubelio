<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResources extends JsonResource
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

            'purchaseorder_no'=>$this->purchaseorder_no,

            'transaction_date'=>$this->transaction_date?->format('Y-m-d'),

            'supplier_name'=>$this->supplier_name,

            'location_name'=>$this->location_name,

            'status'=>$this->status,

            'grand_total'=>$this->grand_total,

            'created_at'=>$this->created_at?->format('Y-m-d H:i:s'),

            'updated_at'=>$this->updated_at?->format('Y-m-d H:i:s'),

            ];
    }
}
