<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->longText('name')->change();
            $table->longText('description')->nullable()->change();
        });

        DB::table('companies')->orderBy('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('companies')->where('id', $company->id)->update([
                    'name'        => json_encode(['ar' => $company->name], JSON_UNESCAPED_UNICODE),
                    'description' => $company->description
                        ? json_encode(['ar' => $company->description], JSON_UNESCAPED_UNICODE)
                        : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('companies')->orderBy('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                $name        = json_decode($company->name, true)['ar'] ?? $company->name;
                $description = $company->description
                    ? (json_decode($company->description, true)['ar'] ?? $company->description)
                    : null;

                DB::table('companies')->where('id', $company->id)->update([
                    'name'        => $name,
                    'description' => $description,
                ]);
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });
    }
};