<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'jubelio_location_id' => $this->jubelio_location_id,

            'location_code' => $this->location_code,

            'location_name' => $this->location_name,

            'location_type' => $this->location_type,

            'city' => $this->city,

            'province' => $this->province,

            'contact_name' => $this->contact_name,

            'phone' => $this->phone,

            'email' => $this->email,

            'is_active' => $this->is_active,

            'is_warehouse' => $this->is_warehouse,

            'odoo_id' => $this->odoo_id,

            'sync_from_jubelio' => $this->sync_from_jubelio,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

        ];
    }
}