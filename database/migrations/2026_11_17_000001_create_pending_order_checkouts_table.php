<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pending_order_checkouts')) {
            Schema::create('pending_order_checkouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->boolean('is_guest')->default(false);
                $table->unsignedBigInteger('module_id')->nullable();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('payment_method')->default('digital_payment');
                $table->string('payment_gateway')->nullable();
                $table->decimal('order_amount', 24, 2)->default(0);
                $table->decimal('digital_payment_fee', 24, 2)->default(0);
                $table->decimal('partially_paid_amount', 24, 2)->default(0);
                $table->text('callback')->nullable();
                $table->longText('payload');
                $table->string('status', 32)->default('pending')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_order_checkouts');
    }
};
