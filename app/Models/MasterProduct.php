<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

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

    /*
    |---------------------------------------------------
    | SCOPES
    |---------------------------------------------------
    */

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

    public function scopeSort(Builder $query, ?string $sortBy = null, ?string $sortDir = null): Builder
    {
        $allowedSorts = ['created_at', 'item_name', 'sell_price'];
        $allowedDirs  = ['asc', 'desc'];

        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array($sortDir, $allowedDirs) ? $sortDir : 'asc';

        return $query->orderBy($sortBy, $sortDir);
    }

    public function scopeFilterDate(
        Builder $query,
        ?string $dateFrom,
        ?string $dateTo
    ): Builder {
        return $query->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('created_at', [$dateFrom, $dateTo]);
        })
        ->when($dateFrom && !$dateTo, function ($q) use ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        })
        ->when(!$dateFrom && $dateTo, function ($q) use ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        });
    }

    /*
    |---------------------------------------------------
    | RELATIONS
    |---------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ProductCategory::class,
            'item_category_id',
            'jubelio_category_id'
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(
            ProductVariant::class,
            'product_id',
            'id'
        );
    }
}