<?php

namespace App\Jobs;

use App\Services\Odoo\ProductOdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushProductsToOdooJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 180;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ProductOdooSyncService $service): void
    {
        Log::info('[Job] PushProductsToOdooJob mulai');
        $service->pushAll();
        Log::info('[Job] PushProductsToOdooJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] PushProductsToOdooJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}