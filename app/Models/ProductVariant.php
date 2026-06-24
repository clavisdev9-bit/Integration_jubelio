<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'jubelio_item_id',
        'jubelio_item_group_id',
        'odoo_id',
        'odoo_product_tmpl_id',
        'item_code',
        'item_name',
        'barcode',
        'thumbnail',
        'is_bundle',
        'invt_acct_id',
        'tax_rate',
        'sell_price',
        'variation_values',
        'end_qty',
        'order_qty',
        'available_qty',
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
        'variation_values'           => 'array',
        'raw_payload'                => 'array',
        'is_bundle'                  => 'boolean',
        'sync_from_jubelio'          => 'boolean',
        'sync_from_jubelio_at'       => 'datetime',
        'sync_to_odoo'               => 'boolean',
        'sync_to_odoo_at'            => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
        'sell_price'                 => 'decimal:4',
        'end_qty'                    => 'decimal:4',
        'order_qty'                  => 'decimal:4',
        'available_qty'              => 'decimal:4',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'product_variant');
    }
}