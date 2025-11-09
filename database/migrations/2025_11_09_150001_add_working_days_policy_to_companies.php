<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_details', function (Blueprint $table) {
            // Working days configuration
            $table->enum('working_days_policy', ['5_day', '6_day', '7_day'])->default('5_day')
                ->comment('5_day = Mon-Fri, 6_day = Mon-Sat, 7_day = Everyday')
                ->after('is_active');

            $table->integer('standard_working_days_per_month')->default(22)
                ->comment('Standard working days per month for leave calculation')
                ->after('working_days_policy');

            // Weekend configuration
            $table->boolean('exclude_saturdays')->default(true)
                ->comment('Exclude Saturdays from leave calculation')
                ->after('standard_working_days_per_month');

            $table->boolean('exclude_sundays')->default(true)
                ->comment('Exclude Sundays from leave calculation')
                ->after('exclude_saturdays');

            // Public holiday configuration
            $table->boolean('exclude_public_holidays')->default(true)
                ->comment('Exclude Zimbabwe public holidays from leave calculation')
                ->after('exclude_sundays');

            $table->json('custom_holidays')->nullable()
                ->comment('Company-specific holidays (JSON array of dates)')
                ->after('exclude_public_holidays');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_details', function (Blueprint $table) {
            $table->dropColumn([
                'working_days_policy',
                'standard_working_days_per_month',
                'exclude_saturdays',
                'exclude_sundays',
                'exclude_public_holidays',
                'custom_holidays',
            ]);
        });
    }
};
