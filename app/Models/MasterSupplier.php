<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class MasterSupplier extends Model
{
        use HasFactory, SoftDeletes;
         protected $table = 'suppliers';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;
     protected $fillable = [
        'jubelio_contact_id',
        'odoo_id',
        'contact_name',
        'contact_full',
        'contact_type',
        'primary_contact',
        'contact_position',
        'email',
        'phone',
        'mobile',
        'fax',
        'npwp',
        'payment_term',
        'notes',
        'shipping_address',
        'shipping_area',
        'shipping_city',
        'shipping_province',
        'shipping_postcode',
        'billing_address',
        'billing_area',
        'billing_city',
        'billing_province',
        'billing_post_code',
        'is_dropshipper',
        'is_reseller',
        'category_id',
        'category_display',
        'raw_payload',
        'sync_from_jubelio',
        'sync_from_jubelio_at',
        'sync_from_jubelio_error',
        'sync_to_odoo',
        'sync_to_odoo_at',
        'sync_error',
        'sync_to_odoo_attempts',
        'sync_to_odoo_next_retry_at',
    ];

    protected $casts = [
        'raw_payload'                => 'array',
        'is_dropshipper'             => 'boolean',
        'is_reseller'                => 'boolean',
        'sync_from_jubelio'          => 'boolean',
        'sync_from_jubelio_at'       => 'datetime',
        'sync_to_odoo'               => 'boolean',
        'sync_to_odoo_at'            => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
    ];


    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
{
    return $only ? $query->onlyTrashed() : $query;
}

// Search
public function scopeSearch(Builder $query, ?string $search): Builder
{
    if (!$search) {
        return $query;
    }

    return $query->where(function ($q) use ($search) {
        $q->where('contact_name', 'like', "%{$search}%");
    });
}

// Dynamic sorting
public function scopeSort(
    Builder $query,
    ?string $sortBy = 'created_at',
    ?string $sortDir = 'asc'
): Builder {
    return $query->orderBy($sortBy, $sortDir);
}

}
