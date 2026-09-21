<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء الجدول الوسيط لربط العروض بالفنادق وأسعارها
     * تم تسمية الجدول hotel_offer اتباعاً للترتيب الأبجدي في Laravel
     */
    public function up(): void
    {
        Schema::create('hotel_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->comment('السعر الذي وضعته الشركة لهذا الفندق في هذا العرض');
            $table->timestamps();
            
            // لمنع تكرار نفس الفندق في نفس العرض
            $table->unique(['offer_id', 'hotel_id']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_offer');
    }
};