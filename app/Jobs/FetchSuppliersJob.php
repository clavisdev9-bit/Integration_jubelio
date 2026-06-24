<?php

namespace App\Jobs;

use App\Services\Jubelio\SupplierSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchSuppliersJob implements ShouldQueue
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

    public function handle(SupplierSyncService $service): void
    {
        Log::info('[Job] FetchSuppliersJob mulai');
        $service->syncAll($this->pageSize);
        Log::info('[Job] FetchSuppliersJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] FetchSuppliersJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}