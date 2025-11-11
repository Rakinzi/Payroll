<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TaxBand;
use App\Models\TaxCredit;
use Illuminate\Support\Collection;

class TaxCalculator
{
    protected Collection $taxCredits;
    protected float $exchangeRate;

    /**
     * Create a new TaxCalculator instance.
     *
     * @param float|null $exchangeRate Exchange rate for currency conversion
     */
    public function __construct(?float $exchangeRate = null)
    {
        $this->taxCredits = TaxCredit::active()->get()->keyBy('credit_name');
        $this->exchangeRate = $exchangeRate ?? 1.0;
    }

    /**
     * Calculate tax for an employee.
     *
     * @param Employee $employee The employee to calculate tax for
     * @param float $grossIncome The gross income to calculate tax on
     * @param string $currency The currency to calculate in (USD or ZWG)
     * @return array Tax calculation results
     */
    public function calculateTax(Employee $employee, float $grossIncome, string $currency = 'USD'): array
    {
        // Get applicable tax credits
        $applicableCredits = $this->getApplicableCredits($employee, $currency);

        // Calculate total credits
        $totalCredits = $this->calculateTotalCredits($applicableCredits, $currency);

        // Calculate taxable income
        $taxableIncome = max(0, $grossIncome - $totalCredits);

        // Calculate tax using tax bands
        $taxAmount = $this->calculateTaxFromBands($taxableIncome, $currency);

        return [
            'gross_income' => $grossIncome,
            'total_credits' => $totalCredits,
            'taxable_income' => $taxableIncome,
            'tax_amount' => $taxAmount,
            'effective_rate' => $grossIncome > 0 ? ($taxAmount / $grossIncome) * 100 : 0,
            'credits_applied' => $applicableCredits,
        ];
    }

    /**
     * Get applicable tax credits for an employee.
     *
     * @param Employee $employee The employee
     * @param string $currency The currency to calculate in
     * @return array Applicable credits
     */
    private function getApplicableCredits(Employee $employee, string $currency): array
    {
        $applicableCredits = [];

        // Personal allowance (always applicable)
        if (isset($this->taxCredits['PERSONAL_ALLOWANCE'])) {
            $applicableCredits['PERSONAL_ALLOWANCE'] = $this->taxCredits['PERSONAL_ALLOWANCE'];
        }

        // Child allowances (based on dependents)
        if (($employee->dependents ?? 0) > 0 && isset($this->taxCredits['CHILD_ALLOWANCE'])) {
            $childCredit = $this->taxCredits['CHILD_ALLOWANCE'];
            $applicableCredits['CHILD_ALLOWANCE'] = [
                'credit' => $childCredit,
                'quantity' => $employee->dependents,
                'total_value' => $childCredit->getValueInCurrency($currency, $this->exchangeRate) * $employee->dependents,
            ];
        }

        // Disability allowance
        if (($employee->disability_status ?? false) && isset($this->taxCredits['DISABILITY_ALLOWANCE'])) {
            $applicableCredits['DISABILITY_ALLOWANCE'] = $this->taxCredits['DISABILITY_ALLOWANCE'];
        }

        // Elderly allowance (automatically applies for employees 55+ years old)
        if ($employee->date_of_birth && isset($this->taxCredits['ELDERLY_ALLOWANCE'])) {
            $age = \Carbon\Carbon::parse($employee->date_of_birth)->age;
            if ($age >= 55) {
                $applicableCredits['ELDERLY_ALLOWANCE'] = $this->taxCredits['ELDERLY_ALLOWANCE'];
            }
        }

        // Blind person allowance (if we add this field to employees)
        // if (($employee->is_blind ?? false) && isset($this->taxCredits['BLIND_ALLOWANCE'])) {
        //     $applicableCredits['BLIND_ALLOWANCE'] = $this->taxCredits['BLIND_ALLOWANCE'];
        // }

        return $applicableCredits;
    }

    /**
     * Calculate total credits from applicable credits.
     *
     * @param array $applicableCredits Applicable credits
     * @param string $currency The currency to calculate in
     * @return float Total credits
     */
    private function calculateTotalCredits(array $applicableCredits, string $currency): float
    {
        $total = 0;

        foreach ($applicableCredits as $key => $creditData) {
            if (is_array($creditData) && isset($creditData['total_value'])) {
                // Multiplier credits (like children)
                $total += $creditData['total_value'];
            } elseif ($creditData instanceof TaxCredit) {
                // Single credits
                $total += $creditData->getValueInCurrency($currency, $this->exchangeRate);
            }
        }

        return $total;
    }

    /**
     * Calculate tax from progressive tax bands.
     *
     * @param float $taxableIncome Taxable income
     * @param string $currency The currency to calculate in
     * @param string $period The period (monthly or annual)
     * @return float Tax amount
     */
    private function calculateTaxFromBands(float $taxableIncome, string $currency, string $period = 'monthly'): float
    {
        // Get applicable tax bands for the currency and period
        $taxBands = $this->getTaxBands($currency, $period);

        $totalTax = 0;
        $remainingIncome = $taxableIncome;

        foreach ($taxBands as $band) {
            // Skip if income is below this band's minimum
            if ($remainingIncome <= $band->min_salary) {
                continue;
            }

            // Calculate taxable amount in this band
            $bandMin = $band->min_salary;
            $bandMax = $band->max_salary ?? PHP_FLOAT_MAX;

            $taxableInBand = min($remainingIncome, $bandMax) - $bandMin;
            $taxableInBand = max(0, $taxableInBand);

            if ($taxableInBand > 0) {
                // Calculate tax: (amount in band * rate) + fixed deduction
                $bandTax = ($taxableInBand * $band->tax_rate) + $band->tax_amount;
                $totalTax += $bandTax;

                // Reduce remaining income
                $remainingIncome -= $taxableInBand;
            }

            // Break if we've covered all income
            if ($remainingIncome <= 0) {
                break;
            }
        }

        return round($totalTax, 2);
    }

    /**
     * Get tax bands for a currency and period from database.
     *
     * @param string $currency The currency (USD or ZWG)
     * @param string $period The period (monthly or annual)
     * @return Collection Tax bands
     */
    private function getTaxBands(string $currency, string $period = 'monthly'): Collection
    {
        $bandType = strtolower($period . '_' . strtolower($currency));

        // Map band type to scope method
        $scopeMethod = match($bandType) {
            'monthly_usd' => 'monthlyUsd',
            'monthly_zwg' => 'monthlyZwg',
            'annual_usd' => 'annualUsd',
            'annual_zwg' => 'annualZwg',
            default => 'monthlyUsd',
        };

        return TaxBand::$scopeMethod()->orderBy('min_salary')->get();
    }

    /**
     * Calculate monthly tax from annual salary.
     *
     * @param Employee $employee The employee
     * @param float $annualSalary Annual salary
     * @param string $currency The currency
     * @return array Monthly tax calculation
     */
    public function calculateMonthlyTax(Employee $employee, float $annualSalary, string $currency = 'USD'): array
    {
        $monthlyGross = $annualSalary / 12;
        return $this->calculateTax($employee, $monthlyGross, $currency);
    }

    /**
     * Calculate annual tax from monthly salary.
     *
     * @param Employee $employee The employee
     * @param float $monthlySalary Monthly salary
     * @param string $currency The currency
     * @return array Annual tax calculation
     */
    public function calculateAnnualTax(Employee $employee, float $monthlySalary, string $currency = 'USD'): array
    {
        $annualGross = $monthlySalary * 12;
        $result = $this->calculateTax($employee, $annualGross, $currency);

        // Convert credits to annual if they're monthly
        foreach ($result['credits_applied'] as $key => $credit) {
            if ($credit instanceof TaxCredit && $credit->period === 'monthly') {
                $result['credits_applied'][$key]['annual_value'] = $credit->credit_amount * 12;
            }
        }

        return $result;
    }

    /**
     * Get a detailed breakdown of tax calculation.
     *
     * @param Employee $employee The employee
     * @param float $grossIncome Gross income
     * @param string $currency The currency
     * @param string $period The period (monthly or annual)
     * @return array Detailed breakdown
     */
    public function getDetailedBreakdown(Employee $employee, float $grossIncome, string $currency = 'USD', string $period = 'monthly'): array
    {
        $result = $this->calculateTax($employee, $grossIncome, $currency);

        // Add band-by-band breakdown
        $taxBands = $this->getTaxBands($currency, $period);
        $bandBreakdown = [];
        $remainingIncome = $result['taxable_income'];

        foreach ($taxBands as $band) {
            if ($remainingIncome <= $band->min_salary) {
                continue;
            }

            $bandMin = $band->min_salary;
            $bandMax = $band->max_salary ?? PHP_FLOAT_MAX;

            $taxableInBand = min($remainingIncome, $bandMax) - $bandMin;
            $taxableInBand = max(0, $taxableInBand);

            if ($taxableInBand > 0) {
                $bandTax = ($taxableInBand * $band->tax_rate) + $band->tax_amount;

                $bandBreakdown[] = [
                    'min_salary' => $band->min_salary,
                    'max_salary' => $band->max_salary,
                    'rate' => $band->tax_rate * 100, // Convert to percentage
                    'fixed_deduction' => $band->tax_amount,
                    'taxable_in_band' => $taxableInBand,
                    'tax_in_band' => $bandTax,
                ];

                $remainingIncome -= $taxableInBand;
            }

            if ($remainingIncome <= 0) {
                break;
            }
        }

        $result['band_breakdown'] = $bandBreakdown;

        return $result;
    }
}
