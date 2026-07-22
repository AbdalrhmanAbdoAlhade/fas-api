<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // صاحب العقار

            $table->string('title'); // عنوان العقار مثل "شقة فاخرة تطل على البحر"
            $table->text('description')->nullable(); // وصف تفصيلي
            $table->string('type'); // نوع العقار (شقة، فيلا، غرفة، سرير، كبسولة نوم ...)
            $table->string('city')->nullable();
            $table->string('address')->nullable();

            $table->integer('rooms')->nullable(); // عدد الغرف
            $table->integer('beds')->nullable(); // عدد الأسرة
            $table->integer('bathrooms')->nullable(); // عدد الحمامات
            $table->integer('guests')->nullable(); // عدد الضيوف الممكن استضافتهم
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('price_per_night', 10, 2)->nullable(); // السعر لليلة
            $table->boolean('is_available')->default(true); // متاح للحجز أم لا

            $table->json('images')->nullable(); // صور العقار
            $table->string('main_image')->nullable(); // الصورة الرئيسية

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
