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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // الشركة (إلزامية)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // الفندق (اختياري)
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('set null');

            $table->string('name'); // اسم العرض
            $table->text('description')->nullable();
            $table->json('cover_images')->nullable(); // صور الغلاف المتعددة
            $table->json('images')->nullable();       // صور العرض
            $table->string('hotel_name')->nullable(); // اسم الفندق إن لزم
            $table->text('features')->nullable();     // المميزات
            $table->integer('people_count')->default(1); // عدد الأفراد
            $table->string('transportation')->nullable(); // المواصلات
            $table->text('program')->nullable(); // برنامج الرحلة
            $table->text('path')->nullable(); // مسار الرحلة
            $table->text('required_documents')->nullable(); // الأوراق المطلوبة
            $table->dateTime('departure_time')->nullable(); // وقت المغادرة
            $table->dateTime('return_time')->nullable(); // وقت العودة
            $table->decimal('price', 10, 2); // سعر العرض
               $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
