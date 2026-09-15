<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Append-only audit trail: no updated_at/deleted_at by design.
        Schema::create('repair_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs')->cascadeOnDelete()->restrictOnUpdate();
            $table->string('status', 30);
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('repair_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_status_history');
    }
};
