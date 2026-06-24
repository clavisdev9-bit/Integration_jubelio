<?php

namespace App\Jobs;

use App\Models\PurchaseOrder;
use App\Services\Jubelio\PurchaseOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Step 2 — Fetch detail satu PO (beserta items-nya) dari Jubelio
 * dan simpan ke DB. Dipanggil oleh FetchPurchaseOrdersJob.
 */
class FetchPurchaseOrderDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /**
     * Exponential backoff: retry setelah 1 menit, 5 menit, 15 menit.
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        private PurchaseOrder $purchaseOrder
    ) {}

    public function handle(PurchaseOrderSyncService $service): void
    {
        Log::info("[Job] FetchPurchaseOrderDetailJob PO #{$this->purchaseOrder->jubelio_purchaseorder_id}");

        $service->syncDetail($this->purchaseOrder);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[Job] FetchPurchaseOrderDetailJob FAILED PO #{$this->purchaseOrder->jubelio_purchaseorder_id}", [
            'error' => $exception->getMessage(),
        ]);

        // Update retry tracking di DB
        $this->purchaseOrder->update([
            'sync_to_odoo_attempts'      => $this->purchaseOrder->sync_to_odoo_attempts + 1,
            'sync_to_odoo_next_retry_at' => now()->addMinutes(15),
            'sync_from_jubelio_error'    => $exception->getMessage(),
        ]);
    }
}