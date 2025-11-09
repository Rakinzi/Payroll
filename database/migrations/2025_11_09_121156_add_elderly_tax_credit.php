<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert ELDERLY_ALLOWANCE tax credit
        DB::table('tax_credits')->insert([
            [
                'credit_name' => 'ELDERLY_ALLOWANCE',
                'description' => 'Elderly Allowance for employees 55 years and older',
                'value_usd' => 75.00,
                'value_zwg' => 75.00, // Can be adjusted based on exchange rate policy
                'is_active' => true,
                'is_percentage' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove ELDERLY_ALLOWANCE tax credit
        DB::table('tax_credits')->where('credit_name', 'ELDERLY_ALLOWANCE')->delete();
    }
};
