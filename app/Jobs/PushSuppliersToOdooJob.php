<?php

namespace App\Jobs;

use App\Services\Odoo\SupplierOdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushSuppliersToOdooJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(SupplierOdooSyncService $service): void
    {
        Log::info('[Job] PushSuppliersToOdooJob mulai');
        $service->pushAll();
        Log::info('[Job] PushSuppliersToOdooJob selesai');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Job] PushSuppliersToOdooJob FAILED', [
            'error' => $exception->getMessage(),
        ]);
    }
}