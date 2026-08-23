<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fis modu (earsiv | okc) + Yeni Nesil Yazarkasa (OKC) cihaz ayarlari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edonusum_ayarlari', function (Blueprint $table) {
            $table->string('fis_modu')->default('earsiv');   // earsiv | okc
            $table->string('okc_marka')->nullable();          // ingenico | hugin | pavo | beko | inpos
            $table->string('okc_ip')->nullable();
            $table->string('okc_port')->nullable();
            $table->boolean('okc_aktif')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('edonusum_ayarlari', function (Blueprint $table) {
            $table->dropColumn(['fis_modu', 'okc_marka', 'okc_ip', 'okc_port', 'okc_aktif']);
        });
    }
};
