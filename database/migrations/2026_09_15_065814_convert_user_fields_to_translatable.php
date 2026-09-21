<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('name')->nullable()->change();
            $table->longText('full_name')->nullable()->change();
            $table->longText('last_name')->nullable()->change();
            $table->longText('nationality')->nullable()->change();
            $table->longText('property_type')->nullable()->change();
            $table->longText('city')->nullable()->change();
            $table->longText('address')->nullable()->change();
            $table->longText('area')->nullable()->change();
        });

        // تحويل البيانات الحالية
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $update = [];

                foreach (['name', 'full_name', 'last_name', 'nationality', 'property_type', 'city', 'address', 'area'] as $field) {
                    if (!empty($user->$field)) {
                        // لو القيمة مش JSON، حوّلها
                        $decoded = json_decode($user->$field, true);
                        if (!is_array($decoded)) {
                            $update[$field] = json_encode(['ar' => $user->$field], JSON_UNESCAPED_UNICODE);
                        }
                    }
                }

                if (!empty($update)) {
                    DB::table('users')->where('id', $user->id)->update($update);
                }
            }
        });
    }

    public function down(): void
    {
        // رجّع البيانات لـ string
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $update = [];

                foreach (['name', 'full_name', 'last_name', 'nationality', 'property_type', 'city', 'address', 'area'] as $field) {
                    if (!empty($user->$field)) {
                        $decoded = json_decode($user->$field, true);
                        $update[$field] = is_array($decoded) ? ($decoded['ar'] ?? array_values($decoded)[0] ?? null) : $user->$field;
                    }
                }

                if (!empty($update)) {
                    DB::table('users')->where('id', $user->id)->update($update);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('full_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('nationality')->nullable()->change();
            $table->string('property_type')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('area')->nullable()->change();
        });
    }
};