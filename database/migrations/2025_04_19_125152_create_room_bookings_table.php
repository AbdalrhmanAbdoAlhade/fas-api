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
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('number_of_rooms')->nullable();
            $table->integer('number_of_guests')->nullable();
            $table->integer('adults');
            $table->integer('children');
            $table->decimal('total_price', 10, 2);
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->enum('title', ['Miss', 'Mr', 'Mrs'])->nullable();
            $table->string('national_id');
            $table->string('email');
            $table->string('phone');
            $table->string('floor_number')->nullable();
            $table->string('room_number')->nullable();
            $table->string('room_password')->nullable();
            $table->string('main_password')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
