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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->nullableMorphs('booking'); // booking_id + booking_type
        $table->string('order_id')->unique();
        $table->string('order_name')->nullable();
        $table->decimal('amount', 10, 2);
        $table->string('currency', 10)->default('SAR');
        $table->string('payment_method')->nullable();
        $table->string('status')->default('pending');
        $table->string('redirect_url')->nullable();
        $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('tracking_link_id')->nullable();
            $table->foreign('tracking_link_id')
                  ->references('id')
                  ->on('tracking_links')
                  ->onDelete('set null');
        $table->text('order_description')->nullable();
               $table->foreignId('merchant_id')
                  ->nullable()
                  ->constrained('users') // بيفترض إن التجار هم يوزرز في جدول الـ users
                  ->onDelete('set null');
      
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
