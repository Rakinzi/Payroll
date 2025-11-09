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
        // Insert ELDERLY_ALLOWANCE tax credit (USD version)
        DB::table('tax_credits')->insert([
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'credit_name' => 'ELDERLY_ALLOWANCE',
                'description' => 'Elderly Allowance for employees 55 years and older',
                'credit_amount' => 75.00,
                'currency' => 'USD',
                'period' => 'monthly',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'credit_name' => 'ELDERLY_ALLOWANCE',
                'description' => 'Elderly Allowance for employees 55 years and older',
                'credit_amount' => 75.00,
                'currency' => 'ZWG',
                'period' => 'monthly',
                'is_active' => true,
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
