<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_salesorder_id')->unique();
            $table->string('salesorder_no')->nullable();
            $table->string('invoice_no')->nullable();

            // ── Identifier dari Odoo ─────────────────────────────────────────
            $table->bigInteger('odoo_id')->nullable()->index();
            $table->string('odoo_name')->nullable();

            // ── Customer ─────────────────────────────────────────────────────
            $table->bigInteger('contact_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();

            // ── Channel & Store ──────────────────────────────────────────────
            $table->string('channel_name')->nullable();
            $table->string('store_name')->nullable();
            $table->bigInteger('store_id')->nullable();
            $table->bigInteger('channel_id')->nullable();

            // ── Tanggal ──────────────────────────────────────────────────────
            $table->timestamp('transaction_date')->nullable()->index();
            $table->timestamp('created_date')->nullable();
            $table->timestamp('last_modified')->nullable();
            $table->timestamp('completed_date')->nullable();

            // ── Referensi ────────────────────────────────────────────────────
            $table->string('ref_no')->nullable();
            $table->string('internal_status')->nullable();
            $table->string('channel_status')->nullable();
            $table->string('wms_status')->nullable();
            $table->integer('source')->nullable();

            // ── Pengiriman ───────────────────────────────────────────────────
            $table->string('tracking_number')->nullable();
            $table->string('courier')->nullable();
            $table->string('shipper')->nullable();
            $table->string('shipping_full_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_post_code')->nullable();
            $table->string('shipping_country')->nullable();
            $table->decimal('shipping_cost', 20, 4)->default(0);

            // ── Pajak ────────────────────────────────────────────────────────
            $table->boolean('is_tax_included')->default(false);

            // ── Nilai Transaksi ──────────────────────────────────────────────
            $table->decimal('sub_total', 20, 4)->default(0);
            $table->decimal('total_disc', 20, 4)->default(0);
            $table->decimal('total_tax', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->decimal('add_disc', 20, 4)->default(0);
            $table->decimal('add_fee', 20, 4)->default(0);

            // ── Status ───────────────────────────────────────────────────────
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_canceled')->default(false);
            $table->string('cancel_reason')->nullable();
            $table->boolean('marked_as_complete')->default(false);

            // ── Pembayaran ───────────────────────────────────────────────────
            $table->string('payment_method')->nullable();

            // ── Lokasi ───────────────────────────────────────────────────────
            $table->bigInteger('location_id')->nullable();
            $table->string('location_name')->nullable();

            // ── Invoice ──────────────────────────────────────────────────────
            $table->bigInteger('invoice_id')->nullable();
            $table->timestamp('invoice_created_date')->nullable();

            // ── Lainnya ──────────────────────────────────────────────────────
            $table->text('note')->nullable();
            $table->string('process_number')->nullable();

            // ── Raw payload ──────────────────────────────────────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Fetch Detail ─────────────────────────────────────────────────
            $table->boolean('detail_fetched')->default(false);
            $table->timestamp('detail_fetched_at')->nullable();

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
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'so_sync_odoo_retry_idx');
            $table->index(['sync_from_jubelio', 'transaction_date'],      'so_sync_jubelio_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};