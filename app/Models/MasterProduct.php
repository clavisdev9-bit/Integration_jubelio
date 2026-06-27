<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class MasterProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

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
        'is_consignment' => 'boolean',
        'sync_from_jubelio' => 'boolean',
        'sync_to_odoo' => 'boolean',

        'last_modified' => 'datetime',
        'sync_from_jubelio_at' => 'datetime',
        'sync_to_odoo_at' => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',

        'variations' => 'array',
        'raw_payload' => 'array',
    ];

    // Optional: tampilkan data yang sudah dihapus
public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
{
    return $only ? $query->onlyTrashed() : $query;
}

// Search
public function scopeSearch(Builder $query, ?string $search): Builder
{
    if (!$search) {
        return $query;
    }

    return $query->where(function ($q) use ($search) {
        $q->where('item_name', 'like', "%{$search}%")
          ->orWhere('odoo_ref', 'like', "%{$search}%")
          ->orWhere('jubelio_item_group_id', 'like', "%{$search}%");
    });
}

// Dynamic sorting
public function scopeSort(
    Builder $query,
    ?string $sortBy = 'created_at',
    ?string $sortDir = 'asc'
): Builder {
    return $query->orderBy($sortBy, $sortDir);
}

// Duplicate check
public static function isDuplicate(array $data, $id = null): array
{
    $errors = [];

    $query = static::where('item_name', $data['item_name']);

    if ($id) {
        $query->where('id', '!=', $id);
    }

    if ($query->exists()) {
        $errors['item_name'] = ['Product Name Already Exists.'];
    }

    return $errors;
}


public function scopeFilterDate(
    Builder $query,
    ?string $dateFrom,
    ?string $dateTo
): Builder {

    return $query
        ->when($dateFrom, function ($q) use ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        })
        ->when($dateTo, function ($q) use ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        });
}


// relasi
public function category()
{
    return $this->belongsTo(
        ProductCategory::class,
        'item_category_id',
        'jubelio_category_id'
    );
}

public function variants()
{
    return $this->hasMany(
        ProductVariant::class,
        'product_id',
        'id'
    );
}
}