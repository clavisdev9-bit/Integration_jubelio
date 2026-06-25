<?php

namespace App\Console\Commands;

use App\Jobs\FetchSalesOrdersJob;
use Illuminate\Console\Command;

class SyncSalesOrdersCommand extends Command
{
    protected $signature   = 'sync:sales-orders';
    protected $description = 'Sync sales orders dari Jubelio ke database';

    public function handle(): void
    {
        $this->info('Dispatching FetchSalesOrdersJob...');
        FetchSalesOrdersJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}