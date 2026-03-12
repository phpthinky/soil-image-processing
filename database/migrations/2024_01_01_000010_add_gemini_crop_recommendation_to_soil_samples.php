<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->text('gemini_crop_recommendation')->nullable()->after('ai_recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropColumn('gemini_crop_recommendation');
        });
    }
};
