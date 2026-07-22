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
    Schema::table('hotels', function (Blueprint $table) {
        // غيرنا 'commercial_register' بـ 'id' عشان نتفادى الخطأ
        $table->unsignedBigInteger('property_type_id')->nullable()->after('id');

        $table->foreign('property_type_id')
              ->references('id')
              ->on('property_types')
              ->onDelete('set null');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            //
        });
    }
};
