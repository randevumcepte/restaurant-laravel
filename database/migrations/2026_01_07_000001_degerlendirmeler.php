<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Musteri degerlendirmeleri (QR/SMS ile odeme sonrasi anket).
 * Patron adisyon detayinda "musteri mutlu mu" gorur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('degerlendirmeler')) return;
        Schema::create('degerlendirmeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('adisyon_id')->nullable()->constrained('adisyonlar')->nullOnDelete();
            $table->foreignId('masa_id')->nullable()->constrained('masalar')->nullOnDelete();
            $table->foreignId('musteri_id')->nullable()->constrained('musteriler')->nullOnDelete();
            $table->unsignedTinyInteger('puan')->default(0);     // genel 1-5
            $table->unsignedTinyInteger('lezzet')->default(0);   // 1-5
            $table->unsignedTinyInteger('servis')->default(0);   // 1-5
            $table->unsignedTinyInteger('hiz')->default(0);      // 1-5
            $table->text('yorum')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sube_id', 'adisyon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('degerlendirmeler');
    }
};
