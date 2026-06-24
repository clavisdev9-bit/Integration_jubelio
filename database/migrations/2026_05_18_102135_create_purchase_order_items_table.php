<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            // ── Relasi ke purchase_orders ────────────────────────────────────
            $table->unsignedBigInteger('purchase_order_id');
            $table->foreign('purchase_order_id')
                  ->references('id')
                  ->on('purchase_orders')
                  ->onDelete('cascade');

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_purchaseorder_detail_id')->unique();

            // ── Identifier dari Odoo (diisi setelah berhasil push ke Odoo) ───
            $table->bigInteger('odoo_id')->nullable()->index();

            // ── Item ─────────────────────────────────────────────────────────
            $table->bigInteger('item_id')->nullable()->index();
            $table->bigInteger('item_group_id')->nullable();
            $table->string('item_code')->nullable()->index();
            $table->text('item_name')->nullable();
            $table->text('description')->nullable();

            // ── Satuan & Kuantitas ───────────────────────────────────────────
            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('qty_in_base', 20, 4)->default(0);
            $table->bigInteger('uom_id')->nullable();
            $table->string('unit')->nullable();

            // ── Harga ────────────────────────────────────────────────────────
            $table->decimal('price', 20, 4)->default(0);
            $table->decimal('buy_price', 20, 4)->default(0);
            $table->decimal('last_price_receive', 20, 4)->default(0);
            $table->decimal('original_price', 20, 4)->default(0);

            // ── Diskon ───────────────────────────────────────────────────────
            $table->decimal('disc', 10, 2)->default(0);
            $table->decimal('disc_amount', 20, 4)->default(0);

            // ── Pajak ────────────────────────────────────────────────────────
            $table->bigInteger('tax_id')->nullable();
            $table->string('tax_name')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('rate', 10, 2)->default(0);

            // ── Total ────────────────────────────────────────────────────────
            $table->decimal('amount', 20, 4)->default(0);

            // ── Lainnya ──────────────────────────────────────────────────────
            $table->text('variant')->nullable();
            $table->text('thumbnail')->nullable();

            // ── Raw payload (safety net jika mapping berubah) ────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Sync dari Jubelio ────────────────────────────────────────────
            $table->boolean('sync_from_jubelio')->default(false);
            $table->timestamp('sync_from_jubelio_at')->nullable();
            $table->text('sync_from_jubelio_error')->nullable();

            // ── Sync ke Odoo ─────────────────────────────────────────────────
            $table->boolean('sync_to_odoo')->default(false);
            $table->timestamp('sync_to_odoo_at')->nullable();
            $table->text('sync_error')->nullable();

            // ── Retry Mechanism ──────────────────────────────────────────────
            $table->integer('sync_to_odoo_attempts')->default(0);
            $table->timestamp('sync_to_odoo_next_retry_at')->nullable();

            // ── Soft Delete ──────────────────────────────────────────────────
            $table->softDeletes();

            $table->timestamps();

            // ── Composite Indexes ────────────────────────────────────────────
            $table->index(['purchase_order_id', 'sync_to_odoo'],          'poi_po_sync_odoo_idx');
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'poi_sync_odoo_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};