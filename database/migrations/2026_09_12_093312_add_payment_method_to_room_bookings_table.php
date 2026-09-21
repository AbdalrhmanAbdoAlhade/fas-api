<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->enum('payment_method', ['online', 'on_arrival'])
                  ->default('online')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('room_bookings', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};