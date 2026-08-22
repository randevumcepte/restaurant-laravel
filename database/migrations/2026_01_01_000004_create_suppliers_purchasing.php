<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satin alma: tedarikci, alis faturasi + kalemleri.
 * Kalemde FIYAT ZEKASI alanlari: onceki fiyat, yuzde/tutar farki, uyari seviyesi
 * (yesil/sari/kirmizi). Esik asilinca fatura "onay_bekliyor" -> patrona bildirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tedarikciler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('telefon')->nullable();
            $table->string('aciklama')->nullable();
            $table->timestamps();
        });

        Schema::create('alis_faturalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('tedarikci_id')->nullable()->constrained('tedarikciler')->nullOnDelete();
            $table->string('fatura_no')->nullable();
            $table->date('tarih');
            $table->decimal('toplam', 14, 2)->default(0);
            // taslak | onay_bekliyor | onaylandi
            $table->string('durum')->default('taslak');
            $table->foreignId('giren_personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->foreignId('onaylayan_personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamps();
            $table->index(['sube_id', 'tarih']);
        });

        Schema::create('alis_fatura_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fatura_id')->constrained('alis_faturalari')->cascadeOnDelete();
            $table->foreignId('malzeme_id')->constrained('malzemeler');
            $table->foreignId('alis_birim_id')->constrained('birimler');
            $table->decimal('miktar', 14, 3);
            $table->decimal('birim_fiyat', 14, 4);         // alis birimi basina
            $table->decimal('satir_toplam', 14, 2);
            // Fiyat zekasi (giris aninda hesaplanir)
            $table->decimal('onceki_fiyat', 14, 4)->nullable();
            $table->decimal('fiyat_farki_yuzde', 8, 2)->nullable();
            $table->decimal('fiyat_farki_tutar', 14, 2)->nullable();
            $table->string('uyari')->default('yesil');     // yesil | sari | kirmizi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alis_fatura_kalemleri');
        Schema::dropIfExists('alis_faturalari');
        Schema::dropIfExists('tedarikciler');
    }
};
