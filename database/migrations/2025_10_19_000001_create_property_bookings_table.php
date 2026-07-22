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
        Schema::create('property_bookings', function (Blueprint $table) {
            $table->id();

            // العلاقات
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // تفاصيل الحجز
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('guests')->default(1);
            $table->decimal('total_price', 10, 2)->default(0);

            // حالة الحجز
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending');

            // بيانات الحاجز
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_bookings');
    }
};
