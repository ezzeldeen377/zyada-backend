<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'digital_payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('digital_payment_fee', 24, 2)->default(0)->after('additional_charge');
            });
        }

        if (!Schema::hasColumn('order_transactions', 'digital_payment_fee')) {
            Schema::table('order_transactions', function (Blueprint $table) {
                $table->decimal('digital_payment_fee', 24, 2)->default(0)->after('additional_charge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'digital_payment_fee')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('digital_payment_fee');
            });
        }

        if (Schema::hasColumn('order_transactions', 'digital_payment_fee')) {
            Schema::table('order_transactions', function (Blueprint $table) {
                $table->dropColumn('digital_payment_fee');
            });
        }
    }
};
