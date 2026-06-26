<?php

namespace App\Jobs;

use App\Services\Odoo\SalesOrderOdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushSalesOrdersToOdooJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(SalesOrderOdooSyncService $service): void
    {
        Log::info('[Job] PushSalesOrdersToOdooJob mulai');
        $service->pushAll();
        Log::info('[Job] PushSalesOrdersToOdooJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] PushSalesOrdersToOdooJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}