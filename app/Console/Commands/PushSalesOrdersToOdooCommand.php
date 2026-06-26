<?php

namespace App\Console\Commands;

use App\Jobs\PushSalesOrdersToOdooJob;
use Illuminate\Console\Command;

class PushSalesOrdersToOdooCommand extends Command
{
    protected $signature   = 'push:sales-orders-to-odoo';
    protected $description = 'Push sales orders dari Laravel ke Odoo';

    public function handle(): void
    {
        $this->info('Dispatching PushSalesOrdersToOdooJob...');
        PushSalesOrdersToOdooJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}