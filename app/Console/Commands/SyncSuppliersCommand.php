<?php

namespace App\Console\Commands;

use App\Jobs\FetchSuppliersJob;
use Illuminate\Console\Command;

class SyncSuppliersCommand extends Command
{
    protected $signature   = 'sync:suppliers';
    protected $description = 'Sync suppliers dari Jubelio ke database';

    public function handle(): void
    {
        $this->info('Dispatching FetchSuppliersJob...');
        FetchSuppliersJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}