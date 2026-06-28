<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class So extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_orders';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

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

        'marketplace_complete',

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

        'transaction_date' => 'datetime',
        'created_date' => 'datetime',
        'last_modified' => 'datetime',
        'completed_date' => 'datetime',

        'invoice_created_date' => 'datetime',

        'is_tax_included' => 'boolean',
        'is_paid' => 'boolean',
        'is_cancelled' => 'boolean',

        'marketplace_complete' => 'boolean',

        'detail_fetched' => 'boolean',

        'sync_from_jubelio' => 'boolean',
        'sync_to_odoo' => 'boolean',

        'detail_fetched_at' => 'datetime',

        'sync_from_jubelio_at' => 'datetime',
        'sync_to_odoo_at' => 'datetime',

        'sync_to_odoo_next_retry_at' => 'datetime',

        'raw_payload' => 'array',

        'shipping_cost' => 'decimal:4',

        'sub_total' => 'decimal:4',
        'total_disc' => 'decimal:4',
        'total_tax' => 'decimal:4',
        'grand_total' => 'decimal:4',

        'add_disc' => 'decimal:4',
        'add_fee' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            SalesOrderItem::class,
            'sales_order_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOnlyDeleted(
        Builder $query,
        bool $only = false
    ): Builder {
        return $only
            ? $query->onlyTrashed()
            : $query;
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {

        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {

            $q->where('salesorder_no', 'like', "%{$search}%")
                ->orWhere('invoice_no', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('ref_no', 'like', "%{$search}%");
        });
    }

    public function scopeStatus(
        Builder $query,
        ?string $status
    ): Builder {

        if (!$status) {
            return $query;
        }

        return $query->where(function ($q) use ($status) {

            $q->where('internal_status', $status)
                ->orWhere('channel_status', $status)
                ->orWhere('wms_status', $status);
        });
    }

    public function scopeCustomer(
        Builder $query,
        ?string $customer
    ): Builder {

        if (!$customer) {
            return $query;
        }

        return $query->where(
            'customer_name',
            'like',
            "%{$customer}%"
        );
    }

    public function scopeLocation(
        Builder $query,
        ?int $locationId
    ): Builder {

        if (!$locationId) {
            return $query;
        }

        return $query->where(
            'location_id',
            $locationId
        );
    }

    public function scopeDateBetween(
        Builder $query,
        ?string $dateFrom,
        ?string $dateTo
    ): Builder {

        if ($dateFrom && $dateTo) {

            return $query->whereBetween(
                'transaction_date',
                [$dateFrom, $dateTo]
            );
        }

        if ($dateFrom) {

            return $query->whereDate(
                'transaction_date',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {

            return $query->whereDate(
                'transaction_date',
                '<=',
                $dateTo
            );
        }

        return $query;
    }

    public function scopeSort(
        Builder $query,
        ?string $sortBy = 'created_at',
        ?string $sortDir = 'desc'
    ): Builder {

        return $query->orderBy(
            $sortBy,
            $sortDir
        );
    }
}