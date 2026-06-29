<?php

namespace App\Jobs;

use App\Services\Jubelio\LocationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchLocationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(LocationSyncService $service): void
    {
        Log::info('[Job] FetchLocationsJob mulai');
        $service->syncAll();
        Log::info('[Job] FetchLocationsJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] FetchLocationsJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}