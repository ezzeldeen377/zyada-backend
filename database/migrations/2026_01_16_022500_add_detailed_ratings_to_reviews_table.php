<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->integer('quality_rating')->nullable()->default(0);
            $table->integer('value_rating')->nullable()->default(0);
            $table->integer('packaging_rating')->nullable()->default(0);
            $table->integer('service_rating')->nullable()->default(0);
            $table->integer('usability_rating')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['quality_rating', 'value_rating', 'packaging_rating', 'service_rating', 'usability_rating']);
        });
    }
};
