<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stok omurgasi (1/2): birimler, malzeme kategorileri, malzemeler, birim cevrimleri.
 *
 * KRITIK MIMARI: 3 ayri birim mantigi -> her malzemenin bir TEMEL/STOK birimi vardir
 * (gram/ml/adet). Alis birimi (koli/cuval) ve recete birimi bu temele CEVRILIR.
 * Tum hesap temel birimde yapilir. Cevrim katsayilari birim_cevrimleri'nde tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birimler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');                 // Gram, Kilogram, Litre, Adet, Koli...
            $table->string('kisaltma', 12);       // g, kg, lt, ad
            $table->string('tip')->default('adet'); // agirlik | hacim | adet
            $table->timestamps();
        });

        Schema::create('malzeme_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->string('ad');                 // Et, Sebze, Sut Urunleri...
            $table->timestamps();
        });

        Schema::create('malzemeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->nullable()->constrained('malzeme_kategorileri')->nullOnDelete();
            $table->foreignId('temel_birim_id')->constrained('birimler');   // stok/temel birim
            $table->string('ad');
            $table->boolean('stok_takipli')->default(true); // false ise stok dusumu yapilmaz
            $table->decimal('kritik_stok', 14, 3)->default(0);
            // Hareketli agirlikli ortalama maliyet (temel birim basina) - stok_hareketleri ile guncellenir
            $table->decimal('guncel_maliyet', 14, 4)->default(0);
            $table->timestamps();
        });

        // Bir malzemenin alternatif birimleri: 1 <birim> kac TEMEL birim eder?
        // Orn: Domates temel=gram; 1 Koli = 12000 gram -> temel_birim_karsiligi=12000
        Schema::create('birim_cevrimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('malzeme_id')->constrained('malzemeler')->cascadeOnDelete();
            $table->foreignId('birim_id')->constrained('birimler');
            $table->decimal('temel_birim_karsiligi', 14, 4); // 1 birim = X temel birim
            $table->timestamps();
            $table->unique(['malzeme_id', 'birim_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birim_cevrimleri');
        Schema::dropIfExists('malzemeler');
        Schema::dropIfExists('malzeme_kategorileri');
        Schema::dropIfExists('birimler');
    }
};
