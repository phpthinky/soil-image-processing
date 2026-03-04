<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('min_ph', 4, 2)->default(0.00);
            $table->decimal('max_ph', 4, 2)->default(14.00);
            $table->decimal('min_nitrogen', 6, 2)->default(0.00);
            $table->decimal('max_nitrogen', 6, 2)->default(100.00);
            $table->decimal('min_phosphorus', 6, 2)->default(0.00);
            $table->decimal('max_phosphorus', 6, 2)->default(100.00);
            $table->decimal('min_potassium', 6, 2)->default(0.00);
            $table->decimal('max_potassium', 6, 2)->default(100.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
