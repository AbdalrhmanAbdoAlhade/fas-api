<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::create('hotel_reviews', function (Blueprint $table) {
        $table->id();
          $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('set null');
         $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
          $table->foreignId('properties_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->decimal('stars', 2, 1); // عدد النجوم من 1 إلى 5
        $table->text('comment')->nullable(); // تعليق المستخدم
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_reviews');
    }
};
