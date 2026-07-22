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
Schema::table('users', function (Blueprint $table) {
    $table->string('full_name')->nullable();
    $table->string('last_name')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('phone')->nullable();
    $table->enum('gender', ['male', 'female'])->nullable();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
