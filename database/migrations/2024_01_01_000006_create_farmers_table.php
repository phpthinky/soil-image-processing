<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('address', 255);
            $table->string('farm_location', 200)->nullable();
            $table->string('farm_id', 100)->nullable()
                  ->comment('Arduino/Phase 2 record ID — populated by external device');
            $table->timestamps();

            $table->index('user_id');
            $table->index('farm_id');
        });

        Schema::table('soil_samples', function (Blueprint $table) {
            $table->foreignId('farmer_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('farmers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
            $table->dropColumn('farmer_id');
        });
        Schema::dropIfExists('farmers');
    }
};
