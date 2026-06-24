<?php

namespace App\Console\Commands;

use App\Jobs\FetchPurchaseOrdersJob;
use Illuminate\Console\Command;

class SyncPurchaseOrdersCommand extends Command
{
    protected $signature   = 'sync:purchase-orders';
    protected $description = 'Sync purchase orders dari Jubelio ke database';

    public function handle(): void
    {
        $this->info('Dispatching FetchPurchaseOrdersJob...');
        FetchPurchaseOrdersJob::dispatch();
        $this->info('Done! Job sudah masuk queue.');
    }
}