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
        // Postgres sequence for race-safe REP-{n} job numbers.
        // Created only on pgsql so sqlite test runs stay portable;
        // the generator falls back to max(id)+1 outside pgsql.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE SEQUENCE IF NOT EXISTS repair_job_seq START WITH 1000');
        }

        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('device_id')->constrained('devices')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete()->restrictOnUpdate();
            // String (not native PG ENUM) so sqlite test runs stay portable;
            // values validated at the FormRequest layer per database-design §3.5.
            $table->string('status', 30)->default('received');
            $table->string('priority', 20)->default('normal');
            $table->text('reported_problem');
            $table->json('intake_condition')->nullable();
            $table->decimal('estimate_amount', 12, 2)->nullable();
            $table->decimal('final_cost', 12, 2)->nullable();
            $table->date('expected_completion_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->integer('warranty_days')->default(0);
            $table->date('warranty_expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('device_id');
            $table->index('assigned_technician_id');
            $table->index('status');
            $table->index(['status', 'assigned_technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP SEQUENCE IF EXISTS repair_job_seq');
        }
    }
};
