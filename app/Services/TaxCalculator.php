<?php

namespace App\Services;

use App\Models\Employee;
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
     * @return float Tax amount
     */
    private function calculateTaxFromBands(float $taxableIncome, string $currency): float
    {
        // Get applicable tax bands for the currency
        $taxBands = $this->getTaxBands($currency);

        $taxAmount = 0;
        $remainingIncome = $taxableIncome;

        foreach ($taxBands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandAmount = min($remainingIncome, $band['max_amount'] - $band['min_amount']);
            $taxAmount += $bandAmount * ($band['rate'] / 100);
            $remainingIncome -= $bandAmount;
        }

        return $taxAmount;
    }

    /**
     * Get tax bands for a currency.
     *
     * TODO: This should be moved to database configuration when tax bands are implemented.
     *
     * @param string $currency The currency
     * @return array Tax bands
     */
    private function getTaxBands(string $currency): array
    {
        // Placeholder implementation
        // In production, these should be retrieved from a TaxBand model/database

        if ($currency === 'USD') {
            return [
                ['min_amount' => 0, 'max_amount' => 3000, 'rate' => 20],
                ['min_amount' => 3000, 'max_amount' => 10000, 'rate' => 25],
                ['min_amount' => 10000, 'max_amount' => 20000, 'rate' => 30],
                ['min_amount' => 20000, 'max_amount' => PHP_FLOAT_MAX, 'rate' => 35],
            ];
        }

        // ZWG tax bands (placeholder - adjust based on local regulations)
        return [
            ['min_amount' => 0, 'max_amount' => 100000, 'rate' => 20],
            ['min_amount' => 100000, 'max_amount' => 300000, 'rate' => 25],
            ['min_amount' => 300000, 'max_amount' => 600000, 'rate' => 30],
            ['min_amount' => 600000, 'max_amount' => PHP_FLOAT_MAX, 'rate' => 35],
        ];
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
     * @return array Detailed breakdown
     */
    public function getDetailedBreakdown(Employee $employee, float $grossIncome, string $currency = 'USD'): array
    {
        $result = $this->calculateTax($employee, $grossIncome, $currency);

        // Add band-by-band breakdown
        $taxBands = $this->getTaxBands($currency);
        $bandBreakdown = [];
        $remainingIncome = $result['taxable_income'];

        foreach ($taxBands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandAmount = min($remainingIncome, $band['max_amount'] - $band['min_amount']);
            $bandTax = $bandAmount * ($band['rate'] / 100);

            $bandBreakdown[] = [
                'min_amount' => $band['min_amount'],
                'max_amount' => $band['max_amount'],
                'rate' => $band['rate'],
                'taxable_in_band' => $bandAmount,
                'tax_in_band' => $bandTax,
            ];

            $remainingIncome -= $bandAmount;
        }

        $result['band_breakdown'] = $bandBreakdown;

        return $result;
    }
}
