<?php

namespace App\Console\Commands;

use App\Jobs\PushSuppliersToOdooJob;
use Illuminate\Console\Command;

class PushSuppliersToOdooCommand extends Command
{
    protected $signature   = 'push:suppliers-to-odoo';
    protected $description = 'Push suppliers dari Laravel ke Odoo';

    public function handle(): void
    {
        $this->info('Dispatching PushSuppliersToOdooJob...');
        PushSuppliersToOdooJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}