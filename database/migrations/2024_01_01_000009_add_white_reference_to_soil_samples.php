<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->unsignedTinyInteger('white_ref_r')->nullable()->after('potassium_color_hex');
            $table->unsignedTinyInteger('white_ref_g')->nullable()->after('white_ref_r');
            $table->unsignedTinyInteger('white_ref_b')->nullable()->after('white_ref_g');
        });
    }

    public function down(): void
    {
        Schema::table('soil_samples', function (Blueprint $table) {
            $table->dropColumn(['white_ref_r', 'white_ref_g', 'white_ref_b']);
        });
    }
};
