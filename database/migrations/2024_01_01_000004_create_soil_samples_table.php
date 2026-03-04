<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soil_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Sample & farmer info
            $table->string('sample_name', 150);
            $table->string('location', 200)->nullable();
            $table->date('sample_date');
            $table->string('farmer_name', 150);
            $table->string('address', 255);
            $table->date('date_tested');

            // Raw soil color (overall, legacy)
            $table->string('color_hex', 7)->default('#8B4513');

            // Per-parameter averaged colors (written after 3rd test per param)
            $table->string('ph_color_hex', 7)->nullable();
            $table->string('nitrogen_color_hex', 7)->nullable();
            $table->string('phosphorus_color_hex', 7)->nullable();
            $table->string('potassium_color_hex', 7)->nullable();

            // Computed nutrient values
            $table->decimal('ph_level', 4, 2)->nullable()->comment('pH scale 0-14');
            $table->decimal('nitrogen_level', 6, 2)->nullable()->comment('ppm');
            $table->decimal('phosphorus_level', 6, 2)->nullable()->comment('ppm');
            $table->decimal('potassium_level', 6, 2)->nullable()->comment('ppm');

            // Results
            $table->unsignedTinyInteger('fertility_score')->nullable();
            $table->text('ai_recommendation')->nullable();
            $table->string('recommended_crop', 150)->nullable();
            $table->unsignedTinyInteger('tests_completed')->default(0)->comment('0-12');

            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soil_samples');
    }
};
