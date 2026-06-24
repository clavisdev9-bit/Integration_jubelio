<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();

            // ── Entity yang di-sync ──────────────────────────────────────────
            // entity_type: 'purchase_order' | 'purchase_order_item'
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');              // id di tabel Laravel (bukan Jubelio/Odoo)

            // ── Arah Sinkronisasi ────────────────────────────────────────────
            // direction: 'jubelio_to_laravel' | 'laravel_to_odoo'
            $table->string('direction');

            // ── Status ───────────────────────────────────────────────────────
            // status: 'success' | 'failed' | 'retrying'
            $table->string('status');

            // ── Informasi Error / Pesan ──────────────────────────────────────
            $table->text('message')->nullable();

            // ── Konteks Request & Response (untuk debugging) ─────────────────
            $table->jsonb('context')->nullable();                  // menyimpan request payload & response error

            // ── Attempt ke berapa ────────────────────────────────────────────
            $table->integer('attempt')->default(1);

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['entity_type', 'entity_id'], 'sl_entity_idx');
            $table->index('status',                      'sl_status_idx');
            $table->index('direction',                   'sl_direction_idx');
            $table->index('created_at',                  'sl_created_at_idx'); // untuk pruning log lama
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};