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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_id');
            $table->string('image')->nullable();
             $table->string('role')->default('user');
            $table->string('registration_role')->nullable();
            $table->string('email')->unique();
             $table->string('national_img')->nullable(); // صورة الهوية
            $table->string('nationality')->nullable(); // الجنسية
            $table->string('status')->nullable(); // الحالة
            $table->string('tax_certificate')->nullable(); // شهادة ضريبة (ملف)
            $table->string('ownership_deed')->nullable(); // صك الملكية (ملف)
            $table->string('commercial_register')->nullable(); // السجل التجاري (ملف)
            $table->string('property_type')->nullable(); // نوع العقار
            $table->string('city')->nullable(); // المدينة
            $table->text('address')->nullable(); // عنوان العقار التفصيلي
            $table->string('area')->nullable(); // المساحة (متر مربع)
            $table->integer('rooms')->nullable(); // عدد الغرف
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('EDFA_PAY_PASSWORD')->nullable();
            $table->string('EDFA_PAY_MERCHANT_KEY')->nullable();
            $table->string('EDFA_PAY_URL')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
