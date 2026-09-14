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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->restrictOnUpdate();
            // String (not native PG ENUM) so sqlite test runs stay portable;
            // values validated at the FormRequest layer per database-design §3.4.
            $table->string('category', 20)->default('phone');
            $table->string('brand', 100);
            $table->string('model', 150);
            $table->string('serial_number', 150)->nullable();
            $table->string('imei', 20)->nullable();
            $table->string('color', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('imei');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
