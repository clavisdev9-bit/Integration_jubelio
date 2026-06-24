<?php

namespace App\Jobs;

use App\Services\Odoo\PurchaseOrderOdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushPurchaseOrdersToOdooJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(PurchaseOrderOdooSyncService $service): void
    {
        Log::info('[Job] PushPurchaseOrdersToOdooJob mulai');
        $service->pushAll();
        Log::info('[Job] PushPurchaseOrdersToOdooJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] PushPurchaseOrdersToOdooJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}