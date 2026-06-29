<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'jubelio_location_id',
        'location_code',
        'location_name',
        'location_type',
        'odoo_id',
        'address',
        'area',
        'city',
        'province',
        'post_code',
        'subdistrict',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'phone',
        'email',
        'contact_name',
        'is_active',
        'is_warehouse',
        'is_pos_outlet',
        'is_fbl',
        'is_tcb',
        'is_sbs',
        'is_o2o',
        'is_multi_origin',
        'warehouse_id',
        'warehouse_store_id',
        'location_group_id',
        'default_warehouse_user',
        'source_replenishment',
        'wms_migration_date',
        'raw_payload',
        'sync_from_jubelio',
        'sync_from_jubelio_at',
        'sync_from_jubelio_error',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'is_warehouse'       => 'boolean',
        'is_pos_outlet'      => 'boolean',
        'is_fbl'             => 'boolean',
        'is_tcb'             => 'boolean',
        'is_sbs'             => 'boolean',
        'is_o2o'             => 'boolean',
        'is_multi_origin'    => 'boolean',
        'raw_payload'        => 'array',
        'wms_migration_date' => 'datetime',
        'sync_from_jubelio'  => 'boolean',
        'sync_from_jubelio_at' => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeWarehousesOnly($query)
    {
        return $query->where('is_warehouse', true);
    }

    public function scopeActiveOnly($query)
    {
        return $query->where('is_active', true);
    }
}