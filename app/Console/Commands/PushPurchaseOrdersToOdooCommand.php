<?php

namespace App\Console\Commands;

use App\Jobs\PushPurchaseOrdersToOdooJob;
use Illuminate\Console\Command;

class PushPurchaseOrdersToOdooCommand extends Command
{
    protected $signature   = 'push:purchase-orders-to-odoo';
    protected $description = 'Push purchase orders dari Laravel ke Odoo';

    public function handle(): void
    {
        $this->info('Dispatching PushPurchaseOrdersToOdooJob...');
        PushPurchaseOrdersToOdooJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}