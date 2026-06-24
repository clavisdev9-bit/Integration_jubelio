<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_purchaseorder_id')->unique();
            $table->string('purchaseorder_no')->nullable();

            // ── Identifier dari Odoo (diisi setelah berhasil push ke Odoo) ───
            $table->bigInteger('odoo_id')->nullable()->index();
            $table->string('odoo_name')->nullable();               // contoh: PO/2026/0001

            // ── Supplier ─────────────────────────────────────────────────────
            $table->bigInteger('contact_id')->nullable()->index();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_email')->nullable();

            // ── Tanggal ──────────────────────────────────────────────────────
            $table->timestamp('transaction_date')->nullable()->index();
            $table->timestamp('last_modified')->nullable();

            // ── Referensi ────────────────────────────────────────────────────
            $table->string('ref_no')->nullable();
            $table->string('status')->nullable();
            $table->string('bills')->nullable();

            // ── Pajak ────────────────────────────────────────────────────────
            $table->boolean('is_tax_included')->default(false);

            // ── Nilai Transaksi ──────────────────────────────────────────────
            $table->decimal('sub_total', 20, 4)->default(0);
            $table->decimal('total_disc', 20, 4)->default(0);
            $table->decimal('total_tax', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);

            // ── Pembayaran ───────────────────────────────────────────────────
            $table->string('payment_method')->nullable();
            $table->string('payment_term')->nullable();

            // ── Lokasi ───────────────────────────────────────────────────────
            $table->bigInteger('location_id')->nullable();
            $table->string('location_name')->nullable();
            $table->string('location_code')->nullable();

            // ── Sumber & Status ──────────────────────────────────────────────
            $table->integer('source')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->text('close_reason')->nullable();

            // ── Catatan & Lampiran ───────────────────────────────────────────
            $table->text('note')->nullable();
            $table->jsonb('attachment')->nullable();

            // ── Audit User ───────────────────────────────────────────────────
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();

            // ── Raw payload (safety net jika mapping berubah) ────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Fetch Detail dari Jubelio (/purchase/orders/{id}) ────────────
            $table->boolean('detail_fetched')->default(false);
            $table->timestamp('detail_fetched_at')->nullable();

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
            $table->index(['sync_from_jubelio', 'transaction_date'],      'po_sync_jubelio_date_idx');
            $table->index(['sync_to_odoo', 'is_closed'],                  'po_sync_odoo_closed_idx');
            $table->index(['sync_to_odoo', 'sync_to_odoo_next_retry_at'], 'po_sync_odoo_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};