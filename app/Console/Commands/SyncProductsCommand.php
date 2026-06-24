<?php

namespace App\Console\Commands;

use App\Jobs\FetchProductsJob;
use Illuminate\Console\Command;

class SyncProductsCommand extends Command
{
    protected $signature   = 'sync:products';
    protected $description = 'Sync products dari Jubelio ke database';

    public function handle(): void
    {
        $this->info('Dispatching FetchProductsJob...');
        FetchProductsJob::dispatch()->onQueue('jubelio');
        $this->info('Done! Job sudah masuk queue.');
    }
}