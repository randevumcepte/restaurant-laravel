<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salon yerlesimi: bolge (Ic Salon/Bahce/Teras) ve masalar (harita konumu + durum).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bolgeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('ad');                 // Ic Salon, Bahce, Teras...
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();
        });

        Schema::create('masalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('bolge_id')->constrained('bolgeler')->cascadeOnDelete();
            $table->string('ad');                 // "12", "Bahce 3"
            $table->unsignedInteger('kapasite')->default(4);
            $table->string('sekil')->default('kare');   // kare | yuvarlak
            $table->integer('x')->default(0);     // harita konumu
            $table->integer('y')->default(0);
            // bos | dolu | rezerve | odendi | kirli | birlesik
            $table->string('durum')->default('bos');
            $table->timestamps();
            $table->index(['sube_id', 'bolge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masalar');
        Schema::dropIfExists('bolgeler');
    }
};
