<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ph_color_charts', function (Blueprint $table) {
            $table->id();
            $table->enum('indicator', ['CPR', 'BCG', 'BTB']);
            $table->decimal('ph_value', 3, 1);
            $table->string('hex_value', 7);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['indicator', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ph_color_charts');
    }
};
