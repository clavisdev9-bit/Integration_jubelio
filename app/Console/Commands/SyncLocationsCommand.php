<?php

namespace App\Console\Commands;

use App\Jobs\FetchLocationsJob;
use Illuminate\Console\Command;

class SyncLocationsCommand extends Command
{
    protected $signature   = 'sync:locations';
    protected $description = 'Sync locations/warehouses dari Jubelio ke database';

    public function handle(): void
    {
        $this->info('Dispatching FetchLocationsJob...');
        FetchLocationsJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}