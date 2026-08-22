<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizasyon: sube, rol, personel.
 * personeller = POS'ta PIN ile giren saha calisani (users tablosu = panel/patron girisi ayri).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subeler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('adres')->nullable();
            $table->string('telefon')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('personeller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');
            $table->string('telefon')->nullable();
            // sahip | mudur | garson | mutfak | kasa
            $table->string('rol')->default('garson');
            $table->string('pin', 8)->nullable();          // POS hizli giris
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['sube_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personeller');
        Schema::dropIfExists('subeler');
    }
};
