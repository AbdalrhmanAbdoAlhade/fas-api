<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['hotels', 'properties', 'companies'];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->enum('status', ['pending', 'approved', 'rejected'])
                              ->default('pending')
                              ->after('user_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['hotels', 'properties', 'companies'] as $table) {
            if (Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('status');
                });
            }
        }
    }
};