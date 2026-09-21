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
    Schema::table('service_requests', function (Blueprint $table) {
        // إضافة العمود بعد service_id
        $table->unsignedBigInteger('hotel_id')->nullable()->after('service_id');
        
        // إضافة علاقة (Foreign Key) مع جدول الفنادق (لو عندك جدول اسمه hotels)
        $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            //
        });
    }
};
