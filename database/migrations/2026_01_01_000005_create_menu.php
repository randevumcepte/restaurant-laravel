<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu: kategori, urun, modifier gruplari + modifierlar (urunle cok-cok).
 * "tukendi" = 86'lama (anlik stok yok isareti).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');
            $table->unsignedInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('urunler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('menu_kategorileri')->nullOnDelete();
            $table->string('ad');
            $table->string('aciklama')->nullable();
            $table->decimal('fiyat', 12, 2)->default(0);
            $table->boolean('stok_takipli')->default(true);  // recete uzerinden stok duser
            $table->boolean('tukendi')->default(false);      // 86
            $table->boolean('aktif')->default(true);
            $table->string('gorsel')->nullable();
            $table->timestamps();
            $table->index(['sube_id', 'kategori_id']);
        });

        Schema::create('modifier_gruplari', function (Blueprint $table) {
            $table->id();
            $table->string('ad');                 // Pisirme, Ekstralar, Icecek Boyu
            $table->unsignedInteger('min_secim')->default(0);
            $table->unsignedInteger('max_secim')->default(1);
            $table->timestamps();
        });

        Schema::create('modifierlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grup_id')->constrained('modifier_gruplari')->cascadeOnDelete();
            $table->string('ad');                 // Az pismis, Ekstra peynir...
            $table->decimal('ek_fiyat', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('urun_modifier_gruplari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('urun_id')->constrained('urunler')->cascadeOnDelete();
            $table->foreignId('grup_id')->constrained('modifier_gruplari')->cascadeOnDelete();
            $table->unique(['urun_id', 'grup_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urun_modifier_gruplari');
        Schema::dropIfExists('modifierlar');
        Schema::dropIfExists('modifier_gruplari');
        Schema::dropIfExists('urunler');
        Schema::dropIfExists('menu_kategorileri');
    }
};
