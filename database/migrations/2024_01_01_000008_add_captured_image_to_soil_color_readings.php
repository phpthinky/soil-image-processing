<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soil_color_readings', function (Blueprint $table) {
            // Path to the saved webcam snapshot (relative to public storage disk).
            // Null for readings captured before this feature was added.
            $table->string('captured_image', 255)->nullable()->after('color_hex');
        });
    }

    public function down(): void
    {
        Schema::table('soil_color_readings', function (Blueprint $table) {
            $table->dropColumn('captured_image');
        });
    }
};
