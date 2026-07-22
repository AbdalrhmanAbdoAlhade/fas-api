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
    Schema::table('room_bookings', function (Blueprint $table) {
        $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending');
    });
}

public function down()
{
    Schema::table('room_bookings', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}

};
