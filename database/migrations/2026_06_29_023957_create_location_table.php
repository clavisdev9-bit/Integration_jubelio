<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // ── Identifier dari Jubelio ──────────────────────────────────────
            $table->bigInteger('jubelio_location_id')->unique();
            $table->string('location_code')->nullable();
            $table->string('location_name');
            $table->string('location_type')->nullable();

            // ── Identifier dari Odoo ─────────────────────────────────────────
            $table->bigInteger('odoo_id')->nullable()->index();

            // ── Alamat ───────────────────────────────────────────────────────
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('post_code')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('province_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('district_id')->nullable();
            $table->string('subdistrict_id')->nullable();

            // ── Kontak ───────────────────────────────────────────────────────
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();

            // ── Flags ────────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_warehouse')->default(false);
            $table->boolean('is_pos_outlet')->default(false);
            $table->boolean('is_fbl')->default(false);
            $table->boolean('is_tcb')->default(false);
            $table->boolean('is_sbs')->default(false);
            $table->boolean('is_o2o')->default(false);
            $table->boolean('is_multi_origin')->default(false);

            // ── Warehouse Info ───────────────────────────────────────────────
            $table->bigInteger('warehouse_id')->nullable();
            $table->bigInteger('warehouse_store_id')->nullable();
            $table->bigInteger('location_group_id')->nullable();
            $table->string('default_warehouse_user')->nullable();
            $table->bigInteger('source_replenishment')->nullable();
            $table->timestamp('wms_migration_date')->nullable();

            // ── Raw payload ──────────────────────────────────────────────────
            $table->jsonb('raw_payload')->nullable();

            // ── Sync dari Jubelio ────────────────────────────────────────────
            $table->boolean('sync_from_jubelio')->default(false)->index();
            $table->timestamp('sync_from_jubelio_at')->nullable();
            $table->text('sync_from_jubelio_error')->nullable();

            // ── Soft Delete ──────────────────────────────────────────────────
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};