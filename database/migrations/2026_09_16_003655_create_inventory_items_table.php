<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('inventory_categories')->restrictOnDelete()->restrictOnUpdate();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            // Plain nullable column for now; the FK to suppliers lands in Phase 7.
            $table->unsignedBigInteger('preferred_supplier_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
        });

        // DB-level safety net; the service layer enforces this first (see
        // InventoryMovementService). Statement runs on pgsql only so sqlite
        // test runs stay portable.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE inventory_items ADD CONSTRAINT inventory_items_quantity_non_negative CHECK (quantity >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
