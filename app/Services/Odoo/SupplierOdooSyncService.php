<?php

namespace App\Services\Odoo;

use App\Models\Supplier;
use App\Models\SyncLog;
use App\Services\OdooService;
use Illuminate\Support\Facades\Log;

class SupplierOdooSyncService
{
    public function __construct(
        private readonly OdooService $odoo
    ) {}

    // ────────────────────────────────────────────────────────────────────────
    // Push semua supplier yang belum sync ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushAll(): void
    {
        $suppliers = Supplier::where('sync_to_odoo', false)
            ->where('contact_type', 1) // hanya supplier asli, bukan marketplace
            ->readyToRetry()
            ->get();

        Log::info("[Odoo Supplier] Push {$suppliers->count()} supplier ke Odoo");

        foreach ($suppliers as $supplier) {
            $this->pushOne($supplier);
        }

        Log::info('[Odoo Supplier] Push selesai.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Push satu supplier ke Odoo
    // ────────────────────────────────────────────────────────────────────────

    public function pushOne(Supplier $supplier): void
    {
        Log::info("[Odoo Supplier] Push supplier #{$supplier->jubelio_contact_id} — {$supplier->contact_name}");

        try {
            // 1. Cek apakah supplier sudah ada di Odoo berdasarkan nama
            $existing = $this->findInOdoo($supplier->contact_name);

            if ($existing) {
                // Sudah ada — update saja
                $odooId = $existing['id'];
                $this->updateInOdoo($odooId, $supplier);
                Log::info("[Odoo Supplier] Update existing supplier odoo_id={$odooId}");
            } else {
                // Belum ada — create baru
                $odooId = $this->createInOdoo($supplier);
                Log::info("[Odoo Supplier] Created supplier odoo_id={$odooId}");
            }

            // 2. Update DB Laravel
            $supplier->update([
                'odoo_id'        => $odooId,
                'sync_to_odoo'   => true,
                'sync_to_odoo_at' => now(),
                'sync_error'     => null,
                'sync_to_odoo_attempts' => $supplier->sync_to_odoo_attempts + 1,
            ]);

            // 3. Catat log sukses
            SyncLog::record(
                entityType: 'supplier',
                entityId:   $supplier->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_SUCCESS,
                message:    "Supplier berhasil push ke Odoo dengan id={$odooId}",
                attempt:    $supplier->sync_to_odoo_attempts + 1,
            );

        } catch (\Throwable $e) {
            Log::error("[Odoo Supplier] Gagal push supplier #{$supplier->jubelio_contact_id}", [
                'error' => $e->getMessage(),
            ]);

            // Hitung next retry dengan exponential backoff
            $attempts = $supplier->sync_to_odoo_attempts + 1;
            $nextRetry = match (true) {
                $attempts === 1 => now()->addMinutes(1),
                $attempts === 2 => now()->addMinutes(5),
                default         => now()->addMinutes(15),
            };

            $supplier->update([
                'sync_error'                 => $e->getMessage(),
                'sync_to_odoo_attempts'      => $attempts,
                'sync_to_odoo_next_retry_at' => $nextRetry,
            ]);

            SyncLog::record(
                entityType: 'supplier',
                entityId:   $supplier->id,
                direction:  SyncLog::DIRECTION_LARAVEL_TO_ODOO,
                status:     SyncLog::STATUS_FAILED,
                message:    $e->getMessage(),
                context:    ['jubelio_contact_id' => $supplier->jubelio_contact_id],
                attempt:    $attempts,
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Cari supplier di Odoo berdasarkan nama.
     * Return array data supplier jika ditemukan, null jika tidak.
     */
    private function findInOdoo(string $name): ?array
    {
        $result = $this->odoo->execute(
            'res.partner',
            'search_read',
            [[['name', '=', $name]]],
            ['fields' => ['id', 'name'], 'limit' => 1]
        );

        return $result[0] ?? null;
    }

    /**
     * Create supplier baru di Odoo, return odoo_id.
     */
    private function createInOdoo(Supplier $supplier): int
    {
        return $this->odoo->execute(
            'res.partner',
            'create',
            [$this->mapToOdoo($supplier)]
        );
    }

    /**
     * Update supplier yang sudah ada di Odoo.
     */
    private function updateInOdoo(int $odooId, Supplier $supplier): void
    {
        $this->odoo->execute(
            'res.partner',
            'write',
            [[$odooId], $this->mapToOdoo($supplier)]
        );
    }

    /**
     * Mapping field Jubelio → Odoo res.partner.
     */
    private function mapToOdoo(Supplier $supplier): array
    {
        return [
            'name'          => $supplier->contact_name,
            'supplier_rank' => 1,
            'customer_rank' => 0,
            'phone'         => $supplier->phone         ?: false,
            'mobile'        => $supplier->mobile        ?: false,
            'email'         => $supplier->email         ?: false,
            'fax'           => $supplier->fax           ?: false,
            'street'        => $supplier->billing_address  ?: false,
            'street2'       => $supplier->shipping_address ?: false,
            'city'          => $supplier->billing_city     ?: false,
            'zip'           => $supplier->billing_post_code ?: false,
            'vat'           => $supplier->npwp            ?: false,
            'comment'       => $supplier->notes           ?: false,
        ];
    }
}