<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',

        // Identifier Jubelio
        'jubelio_purchaseorder_detail_id',

        // Identifier Odoo
        'odoo_id',

        // Item
        'item_id',
        'item_group_id',
        'item_code',
        'item_name',
        'description',

        // Satuan & Qty
        'qty',
        'qty_in_base',
        'uom_id',
        'unit',

        // Harga
        'price',
        'buy_price',
        'last_price_receive',
        'original_price',

        // Diskon
        'disc',
        'disc_amount',

        // Pajak
        'tax_id',
        'tax_name',
        'tax_amount',
        'rate',

        // Total
        'amount',

        // Lainnya
        'variant',
        'thumbnail',
        'raw_payload',

        // Sync dari Jubelio
        'sync_from_jubelio',
        'sync_from_jubelio_at',
        'sync_from_jubelio_error',

        // Sync ke Odoo
        'sync_to_odoo',
        'sync_to_odoo_at',
        'sync_error',
        'sync_to_odoo_attempts',
        'sync_to_odoo_next_retry_at',
    ];

    protected $casts = [
        'raw_payload'                => 'array',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'purchase_order_item');
    }
}