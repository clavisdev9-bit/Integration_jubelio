<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class MasterLocations extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [

        // Jubelio
        'jubelio_location_id',

        // Odoo
        'odoo_id',
        'odoo_ref',

        // Location
        'location_code',
        'location_name',

        // Sync
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

        'raw_payload' => 'array',

        'sync_from_jubelio' => 'boolean',
        'sync_to_odoo' => 'boolean',

        'sync_from_jubelio_at' => 'datetime',
        'sync_to_odoo_at' => 'datetime',
        'sync_to_odoo_next_retry_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scope
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

        if (blank($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {

            $q->where('location_name', 'like', "%{$search}%")
              ->orWhere('location_code', 'like', "%{$search}%")
              ->orWhere('jubelio_location_id', 'like', "%{$search}%");

        });
    }

    public function scopeSort(
        Builder $query,
        ?string $sortBy = 'created_at',
        ?string $sortDir = 'desc'
    ): Builder {

        $allowed = [

            'id',
            'location_code',
            'location_name',
            'created_at',
            'updated_at'

        ];

        if (!in_array($sortBy, $allowed)) {
            $sortBy = 'created_at';
        }

        $sortDir = strtolower($sortDir) === 'asc'
            ? 'asc'
            : 'desc';

        return $query->orderBy(
            $sortBy,
            $sortDir
        );
    }


    
}