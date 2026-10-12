<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WHITE-LABEL: her restoran/zincir kendi markali mobil uygulamasiyla eslesir.
 * app_bundle  -> Flutter uygulamasinin bundle/package id'si (com.marka.x); /api/login'de eslesir.
 * marka_ad    -> uygulamada gosterilen marka adi (bos -> subeler.ad)
 * marka_logo  -> logo URL (uygulama basligi/splash)
 * marka_renk  -> tema ana rengi #RRGGBB
 *
 * NOT: hasColumn guard'li -> runtime-ensure (_ryEnsure) daha once eklediyse de guvenle calisir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subeler')) return;
        Schema::table('subeler', function (Blueprint $table) {
            if (!Schema::hasColumn('subeler', 'app_bundle')) $table->string('app_bundle', 80)->nullable()->index();
            if (!Schema::hasColumn('subeler', 'marka_ad'))   $table->string('marka_ad', 120)->nullable();
            if (!Schema::hasColumn('subeler', 'marka_logo')) $table->string('marka_logo', 255)->nullable();
            if (!Schema::hasColumn('subeler', 'marka_renk')) $table->string('marka_renk', 9)->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subeler')) return;
        Schema::table('subeler', function (Blueprint $table) {
            foreach (['app_bundle', 'marka_ad', 'marka_logo', 'marka_renk'] as $col) {
                if (Schema::hasColumn('subeler', $col)) $table->dropColumn($col);
            }
        });
    }
};
