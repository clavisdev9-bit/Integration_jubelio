<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationDetailResource extends JsonResource
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

            'odoo_id' => $this->odoo_id,

            'contact' => [

                'name' => $this->contact_name,

                'phone' => $this->phone,

                'email' => $this->email,

            ],

            'address' => [

                'address' => $this->address,

                'area' => $this->area,

                'city' => $this->city,

                'province' => $this->province,

                'post_code' => $this->post_code,

                'district_id' => $this->district_id,

                'subdistrict_id' => $this->subdistrict_id,

            ],

            'warehouse' => [

                'warehouse_id' => $this->warehouse_id,

                'warehouse_store_id' => $this->warehouse_store_id,

                'location_group_id' => $this->location_group_id,

                'default_warehouse_user' => $this->default_warehouse_user,

            ],

            'flags' => [

                'is_active' => $this->is_active,

                'is_warehouse' => $this->is_warehouse,

                'is_pos_outlet' => $this->is_pos_outlet,

                'is_fbl' => $this->is_fbl,

                'is_tcb' => $this->is_tcb,

                'is_sbs' => $this->is_sbs,

                'is_o2o' => $this->is_o2o,

                'is_multi_origin' => $this->is_multi_origin,

            ],

            'integration' => [

                'source_replenishment' => $this->source_replenishment,

                'wms_migration_date' => $this->wms_migration_date,

                'sync_from_jubelio' => $this->sync_from_jubelio,

                'sync_from_jubelio_at' => $this->sync_from_jubelio_at,

                'sync_from_jubelio_error' => $this->sync_from_jubelio_error,

            ],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

        ];
    }
}