<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('date_of_birth');
            $table->string('national_id');
            $table->string('email');
            $table->string('phone');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('room_password');
            $table->string('main_password');
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending');
            $table->json('required_documents')->nullable();
            $table->json('selected_options')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_bookings');
    }
};
