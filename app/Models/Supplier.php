<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes;

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

    // ── Relationships ────────────────────────────────────────────────────────

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'supplier');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeRealSuppliers($query)
    {
        // contact_type 1 = supplier asli, bukan marketplace
        return $query->where('contact_type', 1);
    }

    public function scopeNotSyncedToOdoo($query)
    {
        return $query->where('sync_to_odoo', false);
    }

    public function scopeReadyToRetry($query)
    {
        return $query->where('sync_to_odoo', false)
                     ->where(function ($q) {
                         $q->whereNull('sync_to_odoo_next_retry_at')
                           ->orWhere('sync_to_odoo_next_retry_at', '<=', now());
                     });
    }
}
