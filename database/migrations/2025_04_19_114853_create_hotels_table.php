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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('images');
            $table->decimal('stars', 2, 1);
            $table->string('address');
            $table->string('country');
            $table->json('details');
            $table->text('description');

            $table->string('property_type')->nullable(); // نوع العقار
            $table->string('city')->nullable(); // المدينة
            $table->string('area')->nullable(); // المساحة (متر مربع)
            $table->integer('rooms')->nullable(); // عدد الغرف
            $table->json('facilities');
          
            $table->json('cover_image')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('price_per_night', 8, 2);
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ربط الفندق بصاحب الفندق
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
