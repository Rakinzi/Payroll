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
        // Annual ZWG Tax Bands
        Schema::create('tax_bands_annual_zwl', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 4); // Store as decimal (e.g., 0.2000 for 20%)
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
        });

        // Annual USD Tax Bands
        Schema::create('tax_bands_annual_usd', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 4);
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
        });

        // Monthly ZWG Tax Bands
        Schema::create('tax_bands_monthly_zwl', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 4);
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
        });

        // Monthly USD Tax Bands
        Schema::create('tax_bands_monthly_usd', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 4);
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_bands_annual_zwl');
        Schema::dropIfExists('tax_bands_annual_usd');
        Schema::dropIfExists('tax_bands_monthly_zwl');
        Schema::dropIfExists('tax_bands_monthly_usd');
    }
};
