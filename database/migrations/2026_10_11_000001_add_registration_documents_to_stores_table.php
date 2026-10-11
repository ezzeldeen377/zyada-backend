<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('trade_license')->nullable();
            $table->string('emirates_id')->nullable();
            $table->string('passport_copy')->nullable();
            $table->string('iban_letter')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['trade_license', 'emirates_id', 'passport_copy', 'iban_letter']);
        });
    }
};
