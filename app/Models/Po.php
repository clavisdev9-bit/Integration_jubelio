<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PoItem;


class Po extends Model
{
     use HasFactory, SoftDeletes;
     protected $table = 'purchase_orders';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;
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


    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
{
    return $only ? $query->onlyTrashed() : $query;
}

// Search
public function scopeSearch(Builder $query, ?string $search): Builder
{
    if (blank($search)) {
        return $query;
    }

    return $query->where(function ($q) use ($search) {

        $q->where('purchaseorder_no', 'like', "%{$search}%")
          ->orWhere('supplier_name', 'like', "%{$search}%")
          ->orWhere('status', 'like', "%{$search}%")
          ->orWhere('ref_no', 'like', "%{$search}%")
          ->orWhere('location_name', 'like', "%{$search}%");
    });
}

// Dynamic sorting
public function scopeSort(
    Builder $query,
    ?string $sortBy = 'created_at',
    ?string $sortDir = 'desc'
): Builder {

    $allowed = [
        'id',
        'purchaseorder_no',
        'supplier_name',
        'transaction_date',
        'created_at',
        'updated_at'
    ];

    if (! in_array($sortBy, $allowed)) {
        $sortBy = 'created_at';
    }

    $sortDir = strtolower($sortDir) === 'asc'
        ? 'asc'
        : 'desc';

    return $query->orderBy($sortBy, $sortDir);
}

public function scopeStatus(Builder $query, ?string $status)
{
    if (blank($status)) {
        return $query;
    }

    return $query->where('status', $status);
}


public function scopeSupplier(Builder $query, ?int $supplierId)
{
    if (!$supplierId) {
        return $query;
    }

    return $query->where('contact_id', $supplierId);
}


public function scopeDateBetween(
    Builder $query,
    ?string $start,
    ?string $end
)
{
    if ($start && $end) {

        return $query->whereBetween(
            'transaction_date',
            [$start, $end]
        );
    }

    return $query;
}


public function scopeLocation(
    Builder $query,
    ?int $locationId
)
{
    if (!$locationId) {
        return $query;
    }

    return $query->where(
        'location_id',
        $locationId
    );
}


public function scopeOpen(Builder $query)
{
    return $query->where(
        'is_closed',
        false
    );
}

public function scopeClosed(Builder $query)
{
    return $query->where(
        'is_closed',
        true
    );
}

public function items()
{
    return $this->hasMany(PoItem::class, 'purchase_order_id', 'id');
}

// public function items()
// {
//     return $this->hasMany(
//         PoItem::class,
//         'purchase_order_id',
//         'id'
//     );
// }

}
