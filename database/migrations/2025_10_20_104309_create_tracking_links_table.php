<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('custom_keyword')->nullable();
            $table->date('added_date')->nullable();
            $table->integer('visits')->default(0);
            $table->decimal('earns', 10, 2)->default(0);
            $table->integer('purchases_count')->default(0);
            $table->string('url');
            $table->boolean('is_archived')->default(false);

            // العلاقة مع المنسق (Coordinator)
            $table->integer('coordinator_id');
            $table->foreign('coordinator_id')
                  ->references('id')
                  ->on('coordinators')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_links');
    }
};
