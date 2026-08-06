<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->enum('order_type', ['Dine-in', 'Take-out'])->default('Dine-in');
            $table->decimal('takeout_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('change_due', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->string('payment_method')->default('cash'); // cash, gcash, paymongo
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
