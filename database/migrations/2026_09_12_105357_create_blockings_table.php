<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockings', function (Blueprint $table) {
            $table->id();

            // Polymorphic
            $table->string('blockable_type');
            $table->unsignedBigInteger('blockable_id');

            // نوع الحظر
            $table->enum('type', ['temporary', 'permanent'])->default('permanent');
            $table->text('reason')->nullable();

            // تواريخ الحظر
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('blocked_until')->nullable();

            // مين حظر
            $table->foreignId('blocked_by')->constrained('users')->cascadeOnDelete();

            // فك الحظر
            $table->timestamp('unblocked_at')->nullable();
            $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete();

            // الحالة
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['blockable_type', 'blockable_id'], 'blockings_polymorphic_index');
            $table->index(['blockable_type', 'blockable_id', 'is_active'], 'blockings_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockings');
    }
};