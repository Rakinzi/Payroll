<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxTables2025Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Zimbabwe 2025 Tax Tables
     * Update these values with official ZIMRA 2025 tax rates
     */
    public function run(): void
    {
        // Clear existing tax bands for 2025 update
        DB::table('tax_bands_annual_usd')->truncate();
        DB::table('tax_bands_annual_zwl')->truncate();
        DB::table('tax_bands_monthly_usd')->truncate();
        DB::table('tax_bands_monthly_zwl')->truncate();

        // Annual USD Tax Bands (2025)
        // TODO: Update with official 2025 ZIMRA rates
        $annualUsdBands = [
            ['min' => 0, 'max' => 60000, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 60000, 'max' => 120000, 'rate' => 0.20, 'amount' => 0.00],
            ['min' => 120000, 'max' => 240000, 'rate' => 0.25, 'amount' => 12000.00],
            ['min' => 240000, 'max' => 480000, 'rate' => 0.30, 'amount' => 42000.00],
            ['min' => 480000, 'max' => 960000, 'rate' => 0.35, 'amount' => 114000.00],
            ['min' => 960000, 'max' => null, 'rate' => 0.40, 'amount' => 282000.00],
        ];

        // Annual ZWG Tax Bands (2025)
        // TODO: Update with official 2025 ZIMRA rates
        $annualZwgBands = [
            ['min' => 0, 'max' => 60000, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 60000, 'max' => 120000, 'rate' => 0.20, 'amount' => 0.00],
            ['min' => 120000, 'max' => 240000, 'rate' => 0.25, 'amount' => 12000.00],
            ['min' => 240000, 'max' => 480000, 'rate' => 0.30, 'amount' => 42000.00],
            ['min' => 480000, 'max' => 960000, 'rate' => 0.35, 'amount' => 114000.00],
            ['min' => 960000, 'max' => null, 'rate' => 0.40, 'amount' => 282000.00],
        ];

        // Monthly USD Tax Bands (2025)
        // Derived from annual rates divided by 12
        $monthlyUsdBands = [
            ['min' => 0, 'max' => 5000, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 5000, 'max' => 10000, 'rate' => 0.20, 'amount' => 0.00],
            ['min' => 10000, 'max' => 20000, 'rate' => 0.25, 'amount' => 1000.00],
            ['min' => 20000, 'max' => 40000, 'rate' => 0.30, 'amount' => 3500.00],
            ['min' => 40000, 'max' => 80000, 'rate' => 0.35, 'amount' => 9500.00],
            ['min' => 80000, 'max' => null, 'rate' => 0.40, 'amount' => 23500.00],
        ];

        // Monthly ZWG Tax Bands (2025)
        $monthlyZwgBands = [
            ['min' => 0, 'max' => 5000, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 5000, 'max' => 10000, 'rate' => 0.20, 'amount' => 0.00],
            ['min' => 10000, 'max' => 20000, 'rate' => 0.25, 'amount' => 1000.00],
            ['min' => 20000, 'max' => 40000, 'rate' => 0.30, 'amount' => 3500.00],
            ['min' => 40000, 'max' => 80000, 'rate' => 0.35, 'amount' => 9500.00],
            ['min' => 80000, 'max' => null, 'rate' => 0.40, 'amount' => 23500.00],
        ];

        // Insert Annual USD
        foreach ($annualUsdBands as $band) {
            DB::table('tax_bands_annual_usd')->insert([
                'min_salary' => $band['min'],
                'max_salary' => $band['max'],
                'tax_rate' => $band['rate'],
                'tax_amount' => $band['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert Annual ZWG
        foreach ($annualZwgBands as $band) {
            DB::table('tax_bands_annual_zwl')->insert([
                'min_salary' => $band['min'],
                'max_salary' => $band['max'],
                'tax_rate' => $band['rate'],
                'tax_amount' => $band['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert Monthly USD
        foreach ($monthlyUsdBands as $band) {
            DB::table('tax_bands_monthly_usd')->insert([
                'min_salary' => $band['min'],
                'max_salary' => $band['max'],
                'tax_rate' => $band['rate'],
                'tax_amount' => $band['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert Monthly ZWG
        foreach ($monthlyZwgBands as $band) {
            DB::table('tax_bands_monthly_zwl')->insert([
                'min_salary' => $band['min'],
                'max_salary' => $band['max'],
                'tax_rate' => $band['rate'],
                'tax_amount' => $band['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('2025 Zimbabwe Tax Tables seeded successfully!');
        $this->command->warn('NOTE: These are placeholder rates. Update with official 2025 ZIMRA tax rates.');
    }
}
