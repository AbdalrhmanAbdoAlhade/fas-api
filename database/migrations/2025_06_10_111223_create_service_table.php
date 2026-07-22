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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable(); // Image path or URL, nullable if optional
            $table->string('status'); // e.g., 'active', 'inactive'
            $table->string('room_number')->nullable(); // Room number, nullable if optional
            $table->string('national_id'); // To link to user
            $table->timestamps();
        });
    }
    
    

    public function down()
    {
        Schema::dropIfExists('services');
    }
};
