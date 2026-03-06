<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ph_tests', function (Blueprint $table) {
            // Nearest paper chart pH value for step 1 (CPR fixed points)
            $table->decimal('step1_chart_ph', 4, 1)->nullable()->after('step1_ph');
            // Nearest paper chart pH value for step 2 (BCG or BTB fixed points)
            $table->decimal('step2_chart_ph', 4, 1)->nullable()->after('step2_ph');
        });
    }

    public function down(): void
    {
        Schema::table('ph_tests', function (Blueprint $table) {
            $table->dropColumn(['step1_chart_ph', 'step2_chart_ph']);
        });
    }
};
