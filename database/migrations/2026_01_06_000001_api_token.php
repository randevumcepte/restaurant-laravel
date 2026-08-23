<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flutter API icin basit token auth (Sanctum'suz): personele api_token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->string('api_token', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
