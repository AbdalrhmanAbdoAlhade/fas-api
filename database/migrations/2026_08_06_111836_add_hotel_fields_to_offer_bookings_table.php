<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقول الفندق المختار وجدول الحجوزات
     * hotel_price_snapshot: لحفظ سعر الفندق وقت الحجز كلقطة تاريخية
     */
    public function up(): void
    {
        Schema::table('offer_bookings', function (Blueprint $table) {
            $table->foreignId('selected_hotel_id')
                  ->nullable()
                  ->after('offer_id')
                  ->constrained('hotels')
                  ->nullOnDelete();
                  
            $table->decimal('hotel_price_snapshot', 10, 2)
                  ->nullable()
                  ->after('total_price')
                  ->comment('سعر الفندق وقت الحجز كلقطة تاريخية');
        });
    }

    public function down(): void
    {
        Schema::table('offer_bookings', function (Blueprint $table) {
            $table->dropForeign(['selected_hotel_id']);
            $table->dropColumn(['selected_hotel_id', 'hotel_price_snapshot']);
        });
    }
};