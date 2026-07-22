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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الشركة
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // رقم الهاتف
            $table->string('address')->nullable(); // العنوان
            $table->text('description')->nullable(); // وصف عن الشركة
            $table->string('logo')->nullable(); // شعار الشركة (صورة)
            $table->string('website')->nullable(); // الموقع الإلكتروني
            $table->boolean('is_active')->default(false); // حالة التفعيل
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
