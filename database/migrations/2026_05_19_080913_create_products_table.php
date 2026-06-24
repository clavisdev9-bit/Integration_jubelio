<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_item_group_id')->unique();

            // ── Identifier dari Odoo (diisi setelah push ke Odoo) ────────────
            $table->bigInteger('odoo_id')->nullable()->index();
            $table->string('odoo_ref')->nullable();            // internal reference di Odoo

            // ── Data Produk ──────────────────────────────────────────────────
            $table->text('item_name');
            $table->bigInteger('item_category_id')->nullable();
            $table->decimal('sell_price', 20, 4)->nullable();
            $table->text('thumbnail')->nullable();
            $table->integer('total_composition')->default(0);
            $table->boolean('is_consignment')->default(false);
            $table->jsonb('variations')->nullable();           // array variasi (warna, ukuran, dll)

            // ── Tanggal ──────────────────────────────────────────────────────
            $table->timestamp('last_modified')->nullable();

            // ── Raw payload ──────────────────────────────────────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Sync dari Jubelio ────────────────────────────────────────────
            $table->boolean('sync_from_jubelio')->default(false)->index();
            $table->timestamp('sync_from_jubelio_at')->nullable();
            $table->text('sync_from_jubelio_error')->nullable();

            // ── Sync ke Odoo ─────────────────────────────────────────────────
            $table->boolean('sync_to_odoo')->default(false)->index();
            $table->timestamp('sync_to_odoo_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->integer('sync_to_odoo_attempts')->default(0);
            $table->timestamp('sync_to_odoo_next_retry_at')->nullable();

            // ── Soft Delete ──────────────────────────────────────────────────
            $table->softDeletes();
            $table->timestamps();

            // ── Composite Indexes ────────────────────────────────────────────
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'prod_sync_odoo_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};