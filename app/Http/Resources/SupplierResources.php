<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResources extends JsonResource
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
        'jubelio_contact_id' => $this->jubelio_contact_id,
        'status' => $this->jubelio_contact_id < 0 ? 'Tidak Aktif' : 'Aktif',
        'contact_name' => $this->contact_name,
        'contact_full' => $this->contact_full,
        'category_display' => $this->category_display,
        'sync_from_jubelio' => $this->sync_from_jubelio,
        'sync_from_jubelio_at' => $this->sync_from_jubelio_at?->toDateString() ?? '-',
        'created_at' => $this->created_at?->toDateString() ?? '-',
        'updated_at' => $this->updated_at?->toDateString() ?? '-',
    ];
}
}
