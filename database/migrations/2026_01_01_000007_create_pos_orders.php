<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS / Adisyon: adisyon, kalemler, kalem secenekleri (modifier), odemeler,
 * iptal/indirim/ikram loglari (personel+zaman damgali = kayip radari yakiti),
 * ve masa tasima/birlestirme audit logu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adisyonlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('masa_id')->nullable()->constrained('masalar')->nullOnDelete(); // paket/gel-al'da null
            $table->string('kanal')->default('salon');   // salon | paket | qr
            $table->unsignedInteger('misafir_sayisi')->default(1);
            $table->string('durum')->default('acik');    // acik | odendi | iptal
            $table->foreignId('acan_personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->decimal('ara_toplam', 14, 2)->default(0);
            $table->decimal('indirim', 14, 2)->default(0);
            $table->decimal('ikram', 14, 2)->default(0);
            $table->decimal('toplam', 14, 2)->default(0);
            $table->timestamp('acilis')->nullable();
            $table->timestamp('kapanis')->nullable();
            $table->timestamps();
            $table->index(['sube_id', 'durum']);
            $table->index('acilis');
        });

        Schema::create('adisyon_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adisyon_id')->constrained('adisyonlar')->cascadeOnDelete();
            $table->foreignId('urun_id')->nullable()->constrained('urunler')->nullOnDelete();
            $table->string('urun_adi');                  // snapshot (urun sonradan degisirse bozulmasin)
            $table->decimal('adet', 10, 2)->default(1);
            $table->decimal('birim_fiyat', 12, 2)->default(0);
            $table->decimal('tutar', 14, 2)->default(0);
            $table->string('durum')->default('yeni');    // yeni | gonderildi | hazir | iptal
            $table->string('kur')->nullable();           // baslangic | ana | tatli
            $table->unsignedInteger('seat')->nullable(); // hangi misafir (kisiye gore hesap)
            $table->string('not')->nullable();           // "sozansiz"
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamp('gonderim_zamani')->nullable(); // mutfaga fire edildigi an
            $table->timestamps();
            $table->index('adisyon_id');
        });

        Schema::create('adisyon_kalem_secenekleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adisyon_kalem_id')->constrained('adisyon_kalemleri')->cascadeOnDelete();
            $table->foreignId('modifier_id')->nullable()->constrained('modifierlar')->nullOnDelete();
            $table->string('modifier_adi');              // snapshot
            $table->decimal('ek_fiyat', 12, 2)->default(0);
        });

        Schema::create('odemeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adisyon_id')->constrained('adisyonlar')->cascadeOnDelete();
            $table->string('tip');                       // nakit | kredi | yemek_karti | acik
            $table->decimal('tutar', 14, 2);
            $table->decimal('bahsis', 12, 2)->default(0);
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Void / indirim / ikram: personel + zaman damgali (SUISTIMAL RADARI)
        Schema::create('iptal_indirim_loglari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('adisyon_id')->nullable()->constrained('adisyonlar')->nullOnDelete();
            $table->foreignId('adisyon_kalem_id')->nullable()->constrained('adisyon_kalemleri')->nullOnDelete();
            $table->string('tip');                       // void | indirim | ikram
            $table->decimal('tutar', 14, 2)->default(0);
            $table->string('sebep')->nullable();
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sube_id', 'tip', 'created_at']);
        });

        // Masa tasima / birlestirme / bolme audit
        Schema::create('adisyon_masa_loglari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adisyon_id')->constrained('adisyonlar')->cascadeOnDelete();
            $table->string('islem');                     // tasima | birlestirme | bolme | garson_devri
            $table->foreignId('eski_masa_id')->nullable()->constrained('masalar')->nullOnDelete();
            $table->foreignId('yeni_masa_id')->nullable()->constrained('masalar')->nullOnDelete();
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adisyon_masa_loglari');
        Schema::dropIfExists('iptal_indirim_loglari');
        Schema::dropIfExists('odemeler');
        Schema::dropIfExists('adisyon_kalem_secenekleri');
        Schema::dropIfExists('adisyon_kalemleri');
        Schema::dropIfExists('adisyonlar');
    }
};
