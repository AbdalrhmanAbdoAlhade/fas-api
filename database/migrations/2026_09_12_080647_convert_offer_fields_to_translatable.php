<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->longText('name')->change();
            $table->longText('description')->nullable()->change();
        });

        DB::table('offers')->orderBy('id')->chunkById(100, function ($offers) {
            foreach ($offers as $offer) {
                DB::table('offers')->where('id', $offer->id)->update([
                    'name' => json_encode(['ar' => $offer->name], JSON_UNESCAPED_UNICODE),
                    'description' => $offer->description
                        ? json_encode(['ar' => $offer->description], JSON_UNESCAPED_UNICODE)
                        : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('offers')->orderBy('id')->chunkById(100, function ($offers) {
            foreach ($offers as $offer) {
                $name = json_decode($offer->name, true)['ar'] ?? $offer->name;
                $description = $offer->description
                    ? (json_decode($offer->description, true)['ar'] ?? $offer->description)
                    : null;
                DB::table('offers')->where('id', $offer->id)->update([
                    'name' => $name,
                    'description' => $description,
                ]);
            }
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });
    }
};