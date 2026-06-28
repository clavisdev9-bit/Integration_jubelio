<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_order_items';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [

        'sales_order_id',

        // Identifier Jubelio
        'jubelio_salesorder_detail_id',

        // Identifier Odoo
        'odoo_id',

        // Product
        'item_id',
        'item_group_id',
        'item_code',
        'item_name',
        'description',

        // Quantity
        'qty',
        'qty_in_base',
        'uom_id',
        'unit',

        // Price
        'price',
        'buy_price',
        'last_buy_price',
        'original_price',

        // Discount
        'disc',
        'disc_amount',

        // Tax
        'tax_id',
        'tax_name',
        'tax_amount',
        'rate',

        // Total
        'amount',

        // Variant
        'variant',

        // Image
        'thumbnail',

        // Raw Response
        'raw_payload',

        // Sync Jubelio
        'sync_from_jubelio',
        'sync_from_jubelio_at',
        'sync_from_jubelio_error',

        // Sync Odoo
        'sync_to_odoo',
        'sync_to_odoo_at',
        'sync_error',
        'sync_to_odoo_attempts',
        'sync_to_odoo_next_retry_at',
    ];

    protected $casts = [

        'raw_payload' => 'array',

        'sync_from_jubelio' => 'boolean',
        'sync_to_odoo' => 'boolean',

        'sync_from_jubelio_at' => 'datetime',
        'sync_to_odoo_at' => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',

        'qty' => 'decimal:4',
        'qty_in_base' => 'decimal:4',

        'price' => 'decimal:4',
        'buy_price' => 'decimal:4',
        'last_buy_price' => 'decimal:4',
        'original_price' => 'decimal:4',

        'disc' => 'decimal:2',
        'disc_amount' => 'decimal:4',

        'tax_amount' => 'decimal:4',
        'rate' => 'decimal:2',

        'amount' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function salesOrder()
    {
        return $this->belongsTo(
            SalesOrder::class,
            'sales_order_id',
            'id'
        );
    }
}