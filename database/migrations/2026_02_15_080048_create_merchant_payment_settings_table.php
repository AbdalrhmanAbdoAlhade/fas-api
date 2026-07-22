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
    Schema::create('merchant_payment_settings', function (Blueprint $table) {
        $table->id();
        // ربط الإعدادات باليوزر (التاجر/صاحب الفندق)
        $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

        // بيانات EdfaPay
        $table->string('merchant_key');
        $table->string('password');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_payment_settings');
    }
};
