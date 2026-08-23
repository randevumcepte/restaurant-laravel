<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-Donusum (Model A: "kendi entegratorunu getir").
 * edonusum_ayarlari: restoranin entegrator (Parasut/Izibiz) + API anahtari + firma/VKN + mali muhur.
 * e_faturalar: kesilen e-Arsiv/e-Fatura kayitlari (adisyondan olusturulur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edonusum_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('entegrator')->default('parasut');   // parasut | izibiz | uyumsoft | nes ...
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('firma_unvan')->nullable();
            $table->string('vkn_tckn')->nullable();
            $table->string('vergi_dairesi')->nullable();
            $table->string('adres')->nullable();
            $table->boolean('mali_muhur_yuklu')->default(false);
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->unique('sube_id');
        });

        Schema::create('e_faturalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('adisyon_id')->nullable();
            $table->foreignId('musteri_id')->nullable();
            $table->string('tip')->default('e_arsiv');           // e_arsiv | e_fatura
            $table->string('belge_no');
            $table->string('alici_unvan')->nullable();
            $table->string('alici_vkn')->nullable();
            $table->decimal('matrah', 14, 2)->default(0);
            $table->decimal('kdv', 14, 2)->default(0);
            $table->decimal('toplam', 14, 2)->default(0);
            // simulasyon | gonderildi | onaylandi | hata
            $table->string('durum')->default('gonderildi');
            $table->string('entegrator')->nullable();
            $table->string('hata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sube_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_faturalar');
        Schema::dropIfExists('edonusum_ayarlari');
    }
};
