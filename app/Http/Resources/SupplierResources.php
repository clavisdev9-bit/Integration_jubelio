<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'jubelio_contact_id' => $this->jubelio_contact_id,

            // lebih flexible daripada hardcoded string
            'status' => $this->jubelio_contact_id < 0 ? 'inactive' : 'active',

            'contact_name' => $this->contact_name,

            'contact_full' => $this->contact_full,

            'category_display' => $this->category_display,

            'sync_from_jubelio' => (bool) $this->sync_from_jubelio,

            'sync_from_jubelio_at' => $this->sync_from_jubelio_at?->format('Y-m-d H:i:s'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}