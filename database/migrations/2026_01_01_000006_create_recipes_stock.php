<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recete + Stok omurgasi (2/2).
 *
 * RECETE cok katmanli: recete_kalemleri bir satirda YA malzeme YA da alt_recete
 * (yari mamul) referanslar. Yari mamul recetesi uretilen_malzeme_id ile stoga girer,
 * sonra baska recetelerde malzeme gibi kullanilir.
 *
 * STOK anlik sayi DEGIL: stok_hareketleri (immutable defter) satirlarindan turetilir.
 * Her satir kendi anindaki birim_maliyeti saklar. Maliyet = hareketli agirlikli ort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receteler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('tip')->default('urun');   // urun | yari_mamul
            $table->foreignId('urun_id')->nullable()->constrained('urunler')->nullOnDelete();
            // Yari mamul ise: bu recete hangi malzemeyi uretir + verimi
            $table->foreignId('uretilen_malzeme_id')->nullable()->constrained('malzemeler')->nullOnDelete();
            $table->decimal('verim_miktar', 14, 3)->default(1);
            $table->foreignId('verim_birim_id')->nullable()->constrained('birimler');
            $table->timestamps();
        });

        Schema::create('recete_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recete_id')->constrained('receteler')->cascadeOnDelete();
            // Satir YA malzeme YA alt_recete referanslar (biri dolu):
            $table->foreignId('malzeme_id')->nullable()->constrained('malzemeler')->nullOnDelete();
            $table->foreignId('alt_recete_id')->nullable()->constrained('receteler')->nullOnDelete();
            $table->decimal('miktar', 14, 3);
            $table->foreignId('birim_id')->constrained('birimler');
            $table->timestamps();
        });

        // IMMUTABLE stok hareketi defteri
        Schema::create('stok_hareketleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->foreignId('malzeme_id')->constrained('malzemeler');
            // alis | tuketim | fire | sayim | iade | transfer
            $table->string('tip');
            $table->decimal('miktar', 16, 4);        // temel birimde, isaretli (+/-)
            $table->decimal('birim_maliyet', 14, 4)->default(0); // o anki maliyet
            $table->string('kaynak_tip')->nullable();  // fatura | adisyon | sayim | fire
            $table->unsignedBigInteger('kaynak_id')->nullable();
            $table->string('aciklama')->nullable();
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['malzeme_id', 'created_at']);
        });

        Schema::create('sayimlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->date('tarih');
            $table->string('durum')->default('acik');  // acik | kapandi
            $table->foreignId('personel_id')->nullable()->constrained('personeller')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sayim_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sayim_id')->constrained('sayimlar')->cascadeOnDelete();
            $table->foreignId('malzeme_id')->constrained('malzemeler');
            $table->decimal('sayilan', 16, 4)->default(0);   // fiili
            $table->decimal('teorik', 16, 4)->default(0);    // sistem
            $table->decimal('fark', 16, 4)->default(0);      // sayilan - teorik (=fire+kacak)
            $table->decimal('fark_maliyet', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sayim_kalemleri');
        Schema::dropIfExists('sayimlar');
        Schema::dropIfExists('stok_hareketleri');
        Schema::dropIfExists('recete_kalemleri');
        Schema::dropIfExists('receteler');
    }
};
