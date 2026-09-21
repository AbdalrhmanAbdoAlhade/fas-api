<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->longText('name')->change();
            $table->longText('description')->nullable()->change();
            $table->longText('details')->nullable()->change();
            $table->longText('facilities')->nullable()->change();
        });

        DB::table('rooms')->orderBy('id')->chunkById(100, function ($rooms) {
            foreach ($rooms as $room) {
                DB::table('rooms')->where('id', $room->id)->update([
                    'name'        => json_encode(['ar' => $room->name], JSON_UNESCAPED_UNICODE),
                    'description' => $room->description
                        ? json_encode(['ar' => $room->description], JSON_UNESCAPED_UNICODE)
                        : null,
                    'details'     => $room->details
                        ? json_encode(['ar' => $room->details], JSON_UNESCAPED_UNICODE)
                        : null,
                    'facilities'  => $room->facilities
                        ? json_encode(['ar' => $room->facilities], JSON_UNESCAPED_UNICODE)
                        : null,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('rooms')->orderBy('id')->chunkById(100, function ($rooms) {
            foreach ($rooms as $room) {
                $name        = json_decode($room->name, true)['ar'] ?? $room->name;
                $description = $room->description
                    ? (json_decode($room->description, true)['ar'] ?? $room->description)
                    : null;
                $details     = $room->details
                    ? (json_decode($room->details, true)['ar'] ?? $room->details)
                    : null;
                $facilities  = $room->facilities
                    ? (json_decode($room->facilities, true)['ar'] ?? $room->facilities)
                    : null;

                DB::table('rooms')->where('id', $room->id)->update([
                    'name'        => $name,
                    'description' => $description,
                    'details'     => $details,
                    'facilities'  => $facilities,
                ]);
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
            $table->text('details')->nullable()->change();
            $table->text('facilities')->nullable()->change();
        });
    }
};