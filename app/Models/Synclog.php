<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'direction',
        'status',
        'message',
        'context',
        'attempt',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    // ── Constants ────────────────────────────────────────────────────────────

    const ENTITY_PURCHASE_ORDER      = 'purchase_order';
    const ENTITY_PURCHASE_ORDER_ITEM = 'purchase_order_item';

    const DIRECTION_JUBELIO_TO_LARAVEL = 'jubelio_to_laravel';
    const DIRECTION_LARAVEL_TO_ODOO    = 'laravel_to_odoo';

    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILED   = 'failed';
    const STATUS_RETRYING = 'retrying';

    // ── Helper static ────────────────────────────────────────────────────────

    public static function record(
        string $entityType,
        int    $entityId,
        string $direction,
        string $status,
        string $message   = '',
        array  $context   = [],
        int    $attempt   = 1,
    ): self {
        return self::create([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'direction'   => $direction,
            'status'      => $status,
            'message'     => $message,
            'context'     => $context,
            'attempt'     => $attempt,
        ]);
    }
}