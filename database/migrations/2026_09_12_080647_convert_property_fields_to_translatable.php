<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->longText('title')->change();
            $table->longText('description')->nullable()->change();
        });

        DB::table('properties')->orderBy('id')->chunkById(100, function ($properties) {
            foreach ($properties as $property) {
                DB::table('properties')->where('id', $property->id)->update([
                    'title' => json_encode(['ar' => $property->title], JSON_UNESCAPED_UNICODE),
                    'description' => $property->description
                        ? json_encode(['ar' => $property->description], JSON_UNESCAPED_UNICODE)
                        : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('properties')->orderBy('id')->chunkById(100, function ($properties) {
            foreach ($properties as $property) {
                $title = json_decode($property->title, true)['ar'] ?? $property->title;
                $description = $property->description
                    ? (json_decode($property->description, true)['ar'] ?? $property->description)
                    : null;
                DB::table('properties')->where('id', $property->id)->update([
                    'title' => $title,
                    'description' => $description,
                ]);
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('description')->nullable()->change();
        });
    }
};