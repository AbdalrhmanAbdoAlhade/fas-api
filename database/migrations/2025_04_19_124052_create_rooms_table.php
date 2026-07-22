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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('cover_image');
            $table->json('images')->nullable();
            $table->text('details')->nullable();
            $table->text('room_number')->nullable();
            $table->text('floor_number')->nullable();
            $table->string('size')->nullable(); // مثلًا: 30 متر مربع
            $table->text('facilities')->nullable(); // مسبح، واي فاي، الخ
            $table->text('description')->nullable();
            $table->decimal('price_per_night', 8, 2);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
