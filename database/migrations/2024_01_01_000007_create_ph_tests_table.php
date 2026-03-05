<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ph_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained('soil_samples')->cascadeOnDelete();

            // Step 1 — CPR solution
            $table->json('step1_readings')->nullable()
                  ->comment('Array of up to 3 captures: [{hex,r,g,b,computed_value}]');
            $table->decimal('step1_ph', 5, 2)->nullable();
            $table->decimal('step1_variance', 8, 4)->nullable();
            $table->string('step1_confidence', 10)->nullable()
                  ->comment('High or Low — based on variance threshold');
            $table->string('step1_outcome', 20)->nullable()
                  ->comment('win-bcg, win-btb, retest, high-acid, alkaline');
            $table->text('step1_remarks')->nullable()
                  ->comment('Auto-generated interpretation for technician review');

            // Decision
            $table->string('next_solution', 10)->nullable()
                  ->comment('BCG, BTB, or RETEST');

            // Step 2 — BCG or BTB solution
            $table->string('step2_solution', 10)->nullable()
                  ->comment('BCG or BTB — set after step 1 decision');
            $table->json('step2_readings')->nullable();
            $table->decimal('step2_ph', 5, 2)->nullable();
            $table->decimal('step2_variance', 8, 4)->nullable();
            $table->string('step2_confidence', 10)->nullable();
            $table->string('step2_outcome', 20)->nullable()
                  ->comment('confirmed, borderline, inconsistent');
            $table->text('step2_remarks')->nullable()
                  ->comment('Auto-generated interpretation for technician review');
            $table->text('technician_notes')->nullable()
                  ->comment('Optional manual notes by the technician');

            // Final result
            $table->decimal('final_ph', 5, 2)->nullable();

            // Workflow state
            $table->enum('status', ['step1', 'step2', 'complete', 'retest'])
                  ->default('step1');

            $table->timestamps();

            $table->unique('sample_id'); // one ph_test record per sample
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ph_tests');
    }
};
