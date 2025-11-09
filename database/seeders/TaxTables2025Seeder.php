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
     * Zimbabwe 2025 Official ZIMRA Tax Tables
     * Source: ZIMRA PAYE Tax Tables for 1 January to 31 December 2025
     * Note: AIDS Levy (3% of tax payable) is applied separately
     */
    public function run(): void
    {
        // Clear existing tax bands for 2025 update
        DB::table('tax_bands_annual_usd')->truncate();
        DB::table('tax_bands_annual_zwl')->truncate();
        DB::table('tax_bands_monthly_usd')->truncate();
        DB::table('tax_bands_monthly_zwl')->truncate();

        // Annual USD Tax Bands (2025) - Official ZIMRA rates
        $annualUsdBands = [
            ['min' => 0, 'max' => 1200, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 1200.01, 'max' => 3600, 'rate' => 0.20, 'amount' => 240.00],
            ['min' => 3600.01, 'max' => 12000, 'rate' => 0.25, 'amount' => 420.00],
            ['min' => 12000.01, 'max' => 24000, 'rate' => 0.30, 'amount' => 1020.00],
            ['min' => 24000.01, 'max' => 36000, 'rate' => 0.35, 'amount' => 2220.00],
            ['min' => 36000.01, 'max' => null, 'rate' => 0.40, 'amount' => 4020.00],
        ];

        // Annual ZWG Tax Bands (2025) - Official ZIMRA rates
        $annualZwgBands = [
            ['min' => 0, 'max' => 33600, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 33600.01, 'max' => 100800, 'rate' => 0.20, 'amount' => 6720.00],
            ['min' => 100800.01, 'max' => 336000, 'rate' => 0.25, 'amount' => 11760.00],
            ['min' => 336000.01, 'max' => 672000, 'rate' => 0.30, 'amount' => 28560.00],
            ['min' => 672000.01, 'max' => 1008000, 'rate' => 0.35, 'amount' => 62160.00],
            ['min' => 1008000.01, 'max' => null, 'rate' => 0.40, 'amount' => 112560.00],
        ];

        // Monthly USD Tax Bands (2025) - Official ZIMRA rates
        $monthlyUsdBands = [
            ['min' => 0, 'max' => 100, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 100.01, 'max' => 300, 'rate' => 0.20, 'amount' => 20.00],
            ['min' => 300.01, 'max' => 1000, 'rate' => 0.25, 'amount' => 35.00],
            ['min' => 1000.01, 'max' => 2000, 'rate' => 0.30, 'amount' => 85.00],
            ['min' => 2000.01, 'max' => 3000, 'rate' => 0.35, 'amount' => 185.00],
            ['min' => 3000.01, 'max' => null, 'rate' => 0.40, 'amount' => 335.00],
        ];

        // Monthly ZWG Tax Bands (2025) - Official ZIMRA rates
        $monthlyZwgBands = [
            ['min' => 0, 'max' => 2800, 'rate' => 0.00, 'amount' => 0.00],
            ['min' => 2800.01, 'max' => 8400, 'rate' => 0.20, 'amount' => 560.00],
            ['min' => 8400.01, 'max' => 28000, 'rate' => 0.25, 'amount' => 980.00],
            ['min' => 28000.01, 'max' => 56000, 'rate' => 0.30, 'amount' => 2380.00],
            ['min' => 56000.01, 'max' => 84000, 'rate' => 0.35, 'amount' => 5180.00],
            ['min' => 84000.01, 'max' => null, 'rate' => 0.40, 'amount' => 9380.00],
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
        $this->command->info('Official ZIMRA tax rates for 2025 have been applied.');
        $this->command->warn('NOTE: AIDS Levy (3% of tax payable) is applied separately in the tax calculator.');
    }
}
