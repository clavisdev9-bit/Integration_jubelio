<?php

namespace App\Jobs;

use App\Services\Jubelio\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        private int $pageSize = 20
    ) {}

    public function handle(ProductSyncService $service): void
    {
        Log::info('[Job] FetchProductsJob mulai');
        $service->syncAll($this->pageSize);
        Log::info('[Job] FetchProductsJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] FetchProductsJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}