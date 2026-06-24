<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jubelio_item_group_id',
        'odoo_id',
        'odoo_ref',
        'item_name',
        'item_category_id',
        'sell_price',
        'thumbnail',
        'total_composition',
        'is_consignment',
        'variations',
        'last_modified',
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
        'variations'                 => 'array',
        'raw_payload'                => 'array',
        'is_consignment'             => 'boolean',
        'last_modified'              => 'datetime',
        'sync_from_jubelio'          => 'boolean',
        'sync_from_jubelio_at'       => 'datetime',
        'sync_to_odoo'               => 'boolean',
        'sync_to_odoo_at'            => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
        'sell_price'                 => 'decimal:4',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'product');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

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