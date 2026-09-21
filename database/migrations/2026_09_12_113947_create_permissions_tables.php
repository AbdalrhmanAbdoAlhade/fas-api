<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول الصلاحيات
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();       // hotels.view
            $table->string('module');                // hotels
            $table->string('action');                // view
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->timestamps();
        });

        // 2. جدول ربط الموظفين بالصلاحيات
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();

            $table->unique(['user_id', 'permission_id']);
            $table->index('user_id');
        });

        // 3. إضافة employee للـ role (لو enum)
        // اختبر الأول: SHOW COLUMNS FROM users LIKE 'role';
        // لو ENUM، شغّل السطر ده. لو VARCHAR، سيبك منه.
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role 
                ENUM('admin', 'user', 'coordinator', 'hotel_owner', 'company_owner', 'property_owner', 'employee') 
                DEFAULT 'user'");
        } catch (\Exception $e) {
            // لو VARCHAR أو النوع مختلف، تجاهل
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');

        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role 
                ENUM('admin', 'user', 'coordinator', 'hotel_owner', 'company_owner', 'property_owner') 
                DEFAULT 'user'");
        } catch (\Exception $e) {}
    }
};