<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('module_id');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->string('discount_type')->nullable()->after('price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'discount_type', 'discount_amount']);
        });
    }
};
