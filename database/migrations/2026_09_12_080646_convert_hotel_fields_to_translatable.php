<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->longText('name')->change();
            $table->longText('description')->change();
        });

        DB::table('hotels')->orderBy('id')->chunkById(100, function ($hotels) {
            foreach ($hotels as $hotel) {
                DB::table('hotels')->where('id', $hotel->id)->update([
                    'name' => json_encode(['ar' => $hotel->name], JSON_UNESCAPED_UNICODE),
                    'description' => json_encode(['ar' => $hotel->description], JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('hotels')->orderBy('id')->chunkById(100, function ($hotels) {
            foreach ($hotels as $hotel) {
                $name = json_decode($hotel->name, true)['ar'] ?? $hotel->name;
                $description = json_decode($hotel->description, true)['ar'] ?? $hotel->description;
                DB::table('hotels')->where('id', $hotel->id)->update([
                    'name' => $name,
                    'description' => $description,
                ]);
            }
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->change();
        });
    }
};