<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jubelio_salesorder_id',
        'salesorder_no',
        'invoice_no',
        'odoo_id',
        'odoo_name',
        'contact_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'channel_name',
        'store_name',
        'store_id',
        'channel_id',
        'transaction_date',
        'created_date',
        'last_modified',
        'completed_date',
        'ref_no',
        'internal_status',
        'channel_status',
        'wms_status',
        'source',
        'tracking_number',
        'courier',
        'shipper',
        'shipping_full_name',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_post_code',
        'shipping_country',
        'shipping_cost',
        'is_tax_included',
        'sub_total',
        'total_disc',
        'total_tax',
        'grand_total',
        'add_disc',
        'add_fee',
        'is_paid',
        'is_canceled',
        'cancel_reason',
        'marked_as_complete',
        'payment_method',
        'location_id',
        'location_name',
        'invoice_id',
        'invoice_created_date',
        'note',
        'process_number',
        'raw_payload',
        'detail_fetched',
        'detail_fetched_at',
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
        'transaction_date'           => 'datetime',
        'created_date'               => 'datetime',
        'last_modified'              => 'datetime',
        'completed_date'             => 'datetime',
        'invoice_created_date'       => 'datetime',
        'is_tax_included'            => 'boolean',
        'is_paid'                    => 'boolean',
        'is_canceled'                => 'boolean',
        'marked_as_complete'         => 'boolean',
        'raw_payload'                => 'array',
        'detail_fetched'             => 'boolean',
        'detail_fetched_at'          => 'datetime',
        'sync_from_jubelio'          => 'boolean',
        'sync_from_jubelio_at'       => 'datetime',
        'sync_to_odoo'               => 'boolean',
        'sync_to_odoo_at'            => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
        'grand_total'                => 'decimal:4',
        'sub_total'                  => 'decimal:4',
        'total_disc'                 => 'decimal:4',
        'total_tax'                  => 'decimal:4',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'sales_order');
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

    public function scopeDetailNotFetched($query)
    {
        return $query->where('detail_fetched', false);
    }
}