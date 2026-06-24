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
 * Step 1 — Fetch list semua PO dari Jubelio, simpan header ke DB,
 * lalu dispatch FetchPurchaseOrderDetailJob untuk setiap PO yang
 * belum diambil detailnya.
 */
class FetchPurchaseOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private int $pageSize = 20
    ) {}

    public function handle(PurchaseOrderSyncService $service): void
    {
        Log::info('[Job] FetchPurchaseOrdersJob mulai');

        // 1. Fetch & simpan semua header PO
        $service->syncList($this->pageSize);

        // 2. Dispatch detail job untuk PO yang belum diambil detailnya
        PurchaseOrder::detailNotFetched()
            ->select('id', 'jubelio_purchaseorder_id')
            ->each(function (PurchaseOrder $po) {
                FetchPurchaseOrderDetailJob::dispatch($po)
                    ->onQueue('jubelio');
            });

        Log::info('[Job] FetchPurchaseOrdersJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] FetchPurchaseOrdersJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}