<?php

namespace App\Console\Commands;

use App\Services\Jubelio\CategorySyncService;
use Illuminate\Console\Command;

class SyncCategoriesCommand extends Command
{
    protected $signature   = 'sync:categories';
    protected $description = 'Fetch kategori dari Jubelio lalu push ke Odoo';

    public function handle(CategorySyncService $service): void
    {
        $this->info('Step 1 — Fetch kategori dari Jubelio...');
        $service->syncFromJubelio();
        $this->info('Done fetch.');

        $this->info('Step 2 — Push kategori ke Odoo...');
        $service->pushToOdoo();
        $this->info('Done push!');
    }
}
