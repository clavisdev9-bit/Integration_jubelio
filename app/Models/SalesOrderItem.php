<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_order_id',
        'jubelio_salesorder_detail_id',
        'odoo_id',
        'item_id',
        'item_group_id',
        'item_code',
        'item_name',
        'description',
        'qty',
        'qty_in_base',
        'uom_id',
        'unit',
        'price',
        'sell_price',
        'original_price',
        'disc',
        'disc_amount',
        'disc_marketplace',
        'tax_id',
        'tax_name',
        'tax_amount',
        'rate',
        'amount',
        'variant',
        'thumbnail',
        'is_bundle',
        'is_free_gift',
        'weight_in_gram',
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
        'is_bundle'                  => 'boolean',
        'is_free_gift'               => 'boolean',
        'sync_from_jubelio'          => 'boolean',
        'sync_from_jubelio_at'       => 'datetime',
        'sync_to_odoo'               => 'boolean',
        'sync_to_odoo_at'            => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
        'qty'                        => 'decimal:4',
        'qty_in_base'                => 'decimal:4',
        'price'                      => 'decimal:4',
        'amount'                     => 'decimal:4',
        'disc'                       => 'decimal:2',
        'disc_amount'                => 'decimal:4',
        'tax_amount'                 => 'decimal:4',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}