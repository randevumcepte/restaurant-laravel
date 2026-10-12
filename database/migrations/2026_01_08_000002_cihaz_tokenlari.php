<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FCM push: mobil uygulama cihaz token tablosu.
 * Yeni siparis / garson cagrisi / masa tasima bildirimleri bu tokenlara gonderilir.
 *
 * NOT: routes/web.php icindeki _fcmTokenEnsure() ayni tabloyu runtime'da da olusturur
 * (eski kurulumlar/fresh DB icin guvenlik agi). hasTable guard'li -> cakismaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cihaz_tokenlari')) return;
        Schema::create('cihaz_tokenlari', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('sube_id')->index();
            $table->unsignedBigInteger('personel_id')->nullable()->index();
            $table->string('token', 255)->index();          // FCM registration token
            $table->string('platform', 10)->nullable();      // ios|android|web
            $table->string('app_bundle', 80)->nullable();    // white-label marka anahtari
            $table->string('kullanici_tipi', 12)->default('personel'); // personel|musteri
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cihaz_tokenlari');
    }
};
