<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = [
        'jubelio_category_id',
        'category_name',
        'parent_id',
        'last_modified',
        'odoo_id',
        'odoo_parent_id',
        'raw_payload',
        'sync_from_jubelio',
        'sync_from_jubelio_at',
        'sync_to_odoo',
        'sync_to_odoo_at',
        'sync_error',
    ];

    protected $casts = [
        'raw_payload'          => 'array',
        'last_modified'        => 'datetime',
        'sync_from_jubelio'    => 'boolean',
        'sync_from_jubelio_at' => 'datetime',
        'sync_to_odoo'         => 'boolean',
        'sync_to_odoo_at'      => 'datetime',
    ];

    /**
     * Cari odoo_id berdasarkan jubelio_category_id.
     * Return null jika tidak ditemukan atau belum sync ke Odoo.
     */
    public static function getOdooId(int $jubCategoryId): ?int
    {
        $cat = self::where('jubelio_category_id', $jubCategoryId)
                   ->whereNotNull('odoo_id')
                   ->first();

        return $cat?->odoo_id;
    }
}