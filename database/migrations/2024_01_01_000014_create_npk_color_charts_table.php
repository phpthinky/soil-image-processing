<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npk_color_charts', function (Blueprint $table) {
            $table->id();
            $table->enum('nutrient', ['N', 'P', 'K']);
            $table->decimal('ppm_value', 6, 1);
            $table->string('hex_value', 7);
            $table->enum('category', ['low', 'medium', 'high']);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['nutrient', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npk_color_charts');
    }
};
