<?php

namespace App\Jobs;

use App\Models\SalesOrder;
use App\Services\Jubelio\SalesOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Step 2 — Fetch detail satu SO (beserta items-nya) dari Jubelio
 * dan simpan ke DB.
 */
class FetchSalesOrderDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        private SalesOrder $salesOrder
    ) {}

    public function handle(SalesOrderSyncService $service): void
    {
        Log::info("[Job] FetchSalesOrderDetailJob SO #{$this->salesOrder->jubelio_salesorder_id}");
        $service->syncDetail($this->salesOrder);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[Job] FetchSalesOrderDetailJob FAILED SO #{$this->salesOrder->jubelio_salesorder_id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->salesOrder->update([
            'sync_to_odoo_attempts'      => $this->salesOrder->sync_to_odoo_attempts + 1,
            'sync_to_odoo_next_retry_at' => now()->addMinutes(15),
            'sync_from_jubelio_error'    => $exception->getMessage(),
        ]);
    }
}