<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Paket servis entegrasyon katmani.
 * entegrasyonlar: platform basina baglanti ayarlari (api_key, magaza_id, oto onay).
 * subeler.webhook_token: middleware (Posentegra vb.) siparisleri /webhook/siparis/{token} adresine POST eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subeler', function (Blueprint $table) {
            $table->string('webhook_token', 40)->nullable()->after('aktif');
        });
        // Mevcut subelere token uret
        foreach (DB::table('subeler')->whereNull('webhook_token')->pluck('id') as $id) {
            DB::table('subeler')->where('id', $id)->update(['webhook_token' => Str::random(32)]);
        }

        Schema::create('entegrasyonlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sube_id')->constrained('subeler')->cascadeOnDelete();
            $table->string('platform');            // getir | yemeksepeti | trendyol | migros | ubereats
            $table->boolean('aktif')->default(false);
            $table->string('magaza_id')->nullable();
            $table->string('api_key')->nullable();
            $table->boolean('otomatik_onay')->default(true);
            $table->timestamp('son_siparis_at')->nullable();
            $table->timestamps();
            $table->unique(['sube_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entegrasyonlar');
        Schema::table('subeler', function (Blueprint $table) {
            $table->dropColumn('webhook_token');
        });
    }
};
