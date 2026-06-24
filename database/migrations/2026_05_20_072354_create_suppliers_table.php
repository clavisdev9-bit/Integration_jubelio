<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_contact_id')->unique();

            // ── Identifier dari Odoo (diisi setelah push ke Odoo) ────────────
            $table->bigInteger('odoo_id')->nullable()->index();

            // ── Data Supplier ────────────────────────────────────────────────
            $table->string('contact_name');
            $table->string('contact_full')->nullable();
            $table->integer('contact_type')->nullable();       // 1=supplier, 2=marketplace
            $table->string('primary_contact')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('npwp')->nullable();
            $table->string('payment_term')->nullable();
            $table->text('notes')->nullable();

            // ── Alamat Pengiriman ────────────────────────────────────────────
            $table->text('shipping_address')->nullable();
            $table->string('shipping_area')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_postcode')->nullable();

            // ── Alamat Tagihan ───────────────────────────────────────────────
            $table->text('billing_address')->nullable();
            $table->string('billing_area')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_province')->nullable();
            $table->string('billing_post_code')->nullable();

            // ── Flags ────────────────────────────────────────────────────────
            $table->boolean('is_dropshipper')->default(false);
            $table->boolean('is_reseller')->default(false);

            // ── Kategori ─────────────────────────────────────────────────────
            $table->bigInteger('category_id')->nullable();
            $table->string('category_display')->nullable();

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
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'sup_sync_odoo_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};