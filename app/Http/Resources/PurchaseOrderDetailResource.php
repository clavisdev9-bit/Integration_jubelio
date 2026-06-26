<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderDetailResource extends JsonResource
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

    'supplier'=>[
        'id'=>$this->contact_id,
        'name'=>$this->supplier_name,
        'email'=>$this->supplier_email,
    ],

    'transaction_date'=>$this->transaction_date,

    'reference_no'=>$this->ref_no,

    'status'=>$this->status,

    'payment_method'=>$this->payment_method,

    'payment_term'=>$this->payment_term,

    'location'=>[
        'id'=>$this->location_id,
        'code'=>$this->location_code,
        'name'=>$this->location_name,
    ],

    'note'=>$this->note,

    'summary'=>[
        'sub_total'=>$this->sub_total,
        'discount'=>$this->total_disc,
        'tax'=>$this->total_tax,
        'grand_total'=>$this->grand_total,
    ],

    'items'=>PurchaseOrderItemResource::collection(
        $this->whenLoaded('items')
    ),

];
    }
}
