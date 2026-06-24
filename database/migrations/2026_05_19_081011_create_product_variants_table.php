<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            // ── Relasi ke products ───────────────────────────────────────────
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_item_id')->unique();
            $table->bigInteger('jubelio_item_group_id')->index();

            // ── Identifier dari Odoo (diisi setelah push ke Odoo) ────────────
            $table->bigInteger('odoo_id')->nullable()->index();         // id product.product di Odoo
            $table->bigInteger('odoo_product_tmpl_id')->nullable();     // id product.template di Odoo

            // ── Data Variant ─────────────────────────────────────────────────
            $table->string('item_code')->nullable()->index();           // SKU / internal reference
            $table->text('item_name');
            $table->string('barcode')->nullable()->index();
            $table->text('thumbnail')->nullable();
            $table->boolean('is_bundle')->default(false);
            $table->bigInteger('invt_acct_id')->nullable();
            $table->decimal('tax_rate', 10, 2)->default(0);
            $table->decimal('sell_price', 20, 4)->nullable();
            $table->jsonb('variation_values')->nullable();              // nilai variasi

            // ── Stok ─────────────────────────────────────────────────────────
            $table->decimal('end_qty', 20, 4)->nullable();
            $table->decimal('order_qty', 20, 4)->nullable();
            $table->decimal('available_qty', 20, 4)->nullable();

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
            $table->index(['product_id', 'sync_to_odoo'],               'pv_prod_sync_odoo_idx');
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'pv_sync_odoo_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};