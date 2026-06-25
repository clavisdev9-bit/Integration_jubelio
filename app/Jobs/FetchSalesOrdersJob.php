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
 * Step 1 — Fetch list semua SO dari Jubelio, simpan header ke DB,
 * lalu dispatch FetchSalesOrderDetailJob untuk setiap SO yang
 * belum diambil detailnya.
 */
class FetchSalesOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        private int $pageSize = 20
    ) {}

    public function handle(SalesOrderSyncService $service): void
    {
        Log::info('[Job] FetchSalesOrdersJob mulai');

        // 1. Fetch & simpan semua header SO
        $service->syncList($this->pageSize);

        // 2. Dispatch detail job untuk SO yang belum diambil detailnya
        SalesOrder::detailNotFetched()
            ->select('id', 'jubelio_salesorder_id')
            ->each(function (SalesOrder $so) {
                FetchSalesOrderDetailJob::dispatch($so)
                    ->onQueue('jubelio');
            });

        Log::info('[Job] FetchSalesOrdersJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] FetchSalesOrdersJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}