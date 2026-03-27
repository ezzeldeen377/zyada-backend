<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add box_id to reviews table to distinguish Box reviews from Item reviews
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('box_id')->nullable()->after('item_id');
            $table->foreign('box_id')->references('id')->on('boxes')->onDelete('cascade');
        });

        // Add rating fields to boxes table (mirrors Item rating fields)
        Schema::table('boxes', function (Blueprint $table) {
            $table->string('rating')->nullable()->after('available_count');
            $table->float('avg_rating', 3, 1)->default(0)->after('rating');
            $table->integer('rating_count')->default(0)->after('avg_rating');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
            $table->dropColumn('box_id');
        });

        Schema::table('boxes', function (Blueprint $table) {
            $table->dropColumn(['rating', 'avg_rating', 'rating_count']);
        });
    }
};
