<?php

namespace App\Console\Commands;

use App\Jobs\PushProductsToOdooJob;
use Illuminate\Console\Command;

class PushProductsToOdooCommand extends Command
{
    protected $signature   = 'push:products-to-odoo';
    protected $description = 'Push products dari Laravel ke Odoo';

    public function handle(): void
    {
        $this->info('Dispatching PushProductsToOdooJob...');
        PushProductsToOdooJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}