<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SepetTakip tarzi teslimat katmani + CRM:
 * musteriler, kuryeler, cagri loglari (CallerID) ve adisyonlara paket servis alanlari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musteriler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');
            $table->string('telefon')->index();
            $table->string('adres')->nullable();
            $table->unsignedInteger('puan')->default(0);          // sadakat
            $table->unsignedInteger('siparis_sayisi')->default(0);
            $table->decimal('toplam_harcama', 14, 2)->default(0);
            $table->string('notlar')->nullable();
            $table->timestamps();
        });

        Schema::create('kuryeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');
            $table->string('telefon')->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('durum')->default('musait');           // musait | teslimatta | mola
            $table->decimal('son_lat', 10, 7)->nullable();
            $table->decimal('son_lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('cagri_loglari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('telefon');
            $table->foreignId('musteri_id')->nullable()->constrained('musteriler')->nullOnDelete();
            $table->string('yon')->default('gelen');              // gelen | giden
            $table->string('sonuc')->default('cevaplandi');       // cevaplandi | siparis | kacan
            $table->foreignId('adisyon_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('adisyonlar', function (Blueprint $table) {
            $table->foreignId('musteri_id')->nullable()->after('masa_id')->constrained('musteriler')->nullOnDelete();
            $table->foreignId('kurye_id')->nullable()->after('musteri_id')->constrained('kuryeler')->nullOnDelete();
            // Paket platformu: salon | telefon | whatsapp | qr | getir | yemeksepeti | trendyol | migros | gofody
            $table->string('platform')->nullable()->after('kanal');
            $table->string('platform_siparis_no')->nullable()->after('platform');
            $table->string('teslimat_adres')->nullable()->after('platform_siparis_no');
            // paket durum: hazirlaniyor | hazir | yolda | teslim | iptal
            $table->string('teslimat_durumu')->nullable()->after('teslimat_adres');
            $table->timestamp('teslim_zamani')->nullable()->after('teslimat_durumu');
        });
    }

    public function down(): void
    {
        Schema::table('adisyonlar', function (Blueprint $table) {
            $table->dropConstrainedForeignId('musteri_id');
            $table->dropConstrainedForeignId('kurye_id');
            $table->dropColumn(['platform', 'platform_siparis_no', 'teslimat_adres', 'teslimat_durumu', 'teslim_zamani']);
        });
        Schema::dropIfExists('cagri_loglari');
        Schema::dropIfExists('kuryeler');
        Schema::dropIfExists('musteriler');
    }
};
