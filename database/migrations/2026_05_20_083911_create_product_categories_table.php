<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_category_id')->unique();
            $table->string('category_name');
            $table->bigInteger('parent_id')->nullable();           // parent di Jubelio
            $table->timestamp('last_modified')->nullable();

            // ── Identifier dari Odoo (diisi setelah push ke Odoo) ────────────
            $table->bigInteger('odoo_id')->nullable()->index();    // id product.category di Odoo
            $table->bigInteger('odoo_parent_id')->nullable();      // parent id di Odoo

            // ── Raw payload ──────────────────────────────────────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Sync ─────────────────────────────────────────────────────────
            $table->boolean('sync_from_jubelio')->default(false)->index();
            $table->timestamp('sync_from_jubelio_at')->nullable();
            $table->boolean('sync_to_odoo')->default(false)->index();
            $table->timestamp('sync_to_odoo_at')->nullable();
            $table->text('sync_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};