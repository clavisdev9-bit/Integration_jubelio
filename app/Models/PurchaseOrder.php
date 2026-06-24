<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Identifier Jubelio
        'jubelio_purchaseorder_id',
        'purchaseorder_no',

        // Identifier Odoo
        'odoo_id',
        'odoo_name',

        // Supplier
        'contact_id',
        'supplier_name',
        'supplier_email',

        // Tanggal
        'transaction_date',
        'last_modified',

        // Referensi
        'ref_no',
        'status',
        'bills',

        // Pajak
        'is_tax_included',

        // Nilai Transaksi
        'sub_total',
        'total_disc',
        'total_tax',
        'grand_total',

        // Pembayaran
        'payment_method',
        'payment_term',

        // Lokasi
        'location_id',
        'location_name',
        'location_code',

        // Sumber & Status
        'source',
        'is_closed',
        'close_reason',

        // Lainnya
        'note',
        'attachment',
        'created_by',
        'updated_by',

        // Raw
        'raw_payload',

        // Fetch detail
        'detail_fetched',
        'detail_fetched_at',

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
        'transaction_date'           => 'datetime',
        'last_modified'              => 'datetime',
        'is_tax_included'            => 'boolean',
        'is_closed'                  => 'boolean',
        'attachment'                 => 'array',
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
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'entity_id')
                    ->where('entity_type', 'purchase_order');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeNotSyncedToOdoo($query)
    {
        return $query->where('sync_to_odoo', false)
                     ->where('is_closed', false);
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