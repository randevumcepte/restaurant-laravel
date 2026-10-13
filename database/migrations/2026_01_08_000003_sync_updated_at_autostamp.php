<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LOCAL-FIRST pull dogrulugu: senkronlanan tablolarda updated_at'i DB seviyesinde
 * otomatik damgala (ON UPDATE CURRENT_TIMESTAMP). Boylece SADECE sync uclari degil,
 * mevcut 248 uc ve web paneli de her yazmada updated_at'i gunceller -> pull cursor
 * bu degisiklikleri kacirmaz.
 *
 * Not: Laravel query builder (DB::table()->update()) Eloquent gibi updated_at'i
 * otomatik set etmez; bu yuzden DB seviyesinde garanti altina aliyoruz. MySQL'e ozgu.
 */
return new class extends Migration
{
    private array $tablolar = [
        'masalar', 'menu_kategorileri', 'urunler',
        'adisyonlar', 'adisyon_kalemleri', 'odemeler',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return; // sadece MySQL
        foreach ($this->tablolar as $t) {
            if (!Schema::hasTable($t)) continue;
            if (!Schema::hasColumn($t, 'updated_at')) {
                try {
                    Schema::table($t, fn ($tbl) => $tbl->timestamp('updated_at')->nullable());
                } catch (\Throwable $e) {
                }
            }
            try {
                DB::statement("ALTER TABLE `$t` MODIFY `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        foreach ($this->tablolar as $t) {
            if (!Schema::hasTable($t) || !Schema::hasColumn($t, 'updated_at')) continue;
            try {
                DB::statement("ALTER TABLE `$t` MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL");
            } catch (\Throwable $e) {
            }
        }
    }
};
