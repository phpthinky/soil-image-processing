<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soil_color_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')
                ->constrained('soil_samples')
                ->cascadeOnDelete();
            $table->enum('parameter', ['ph', 'nitrogen', 'phosphorus', 'potassium']);
            $table->unsignedTinyInteger('test_number')->default(1)->comment('1, 2 or 3');
            $table->string('color_hex', 7);
            $table->unsignedSmallInteger('r');
            $table->unsignedSmallInteger('g');
            $table->unsignedSmallInteger('b');
            $table->decimal('computed_value', 6, 2)->nullable();
            $table->timestamp('captured_at')->useCurrent();

            $table->unique(['sample_id', 'parameter', 'test_number'], 'uq_reading');
            $table->index('sample_id');
            $table->index(['sample_id', 'parameter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soil_color_readings');
    }
};
