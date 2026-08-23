<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sadakat/kampanya motoru + satin alma teklif karsilastirma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanyalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');
            // yuzde | tutar | ikinci_bedava | urun_hediye | puan_carpani
            $table->string('tip')->default('yuzde');
            $table->decimal('deger', 12, 2)->default(0);   // %10 -> 10, tutar -> 50
            $table->decimal('min_tutar', 12, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->date('baslangic')->nullable();
            $table->date('bitis')->nullable();
            $table->unsignedInteger('kullanim')->default(0);
            $table->timestamps();
        });

        // Tedarikci teklifleri (malzeme bazinda fiyat karsilastirma)
        Schema::create('teklifler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('malzeme_id')->constrained('malzemeler')->cascadeOnDelete();
            $table->foreignId('tedarikci_id')->constrained('tedarikciler')->cascadeOnDelete();
            $table->decimal('birim_fiyat', 14, 4);        // alis birimi basina teklif
            $table->foreignId('birim_id')->nullable()->constrained('birimler');
            $table->string('durum')->default('bekliyor'); // bekliyor | secildi
            $table->date('tarih');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teklifler');
        Schema::dropIfExists('kampanyalar');
    }
};
