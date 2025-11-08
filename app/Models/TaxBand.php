<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxBand extends Model
{
    protected $fillable = [
        'min_salary',
        'max_salary',
        'tax_rate',
        'tax_amount',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
    ];

    /**
     * Currencies
     */
    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWG = 'ZWG';

    /**
     * Periods
     */
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_ANNUAL = 'annual';

    /**
     * Band type mappings
     */
    const BAND_TYPES = [
        'annual_zwl' => 'tax_bands_annual_zwl',
        'annual_usd' => 'tax_bands_annual_usd',
        'monthly_zwl' => 'tax_bands_monthly_zwl',
        'monthly_usd' => 'tax_bands_monthly_usd',
    ];

    /**
     * Get formatted tax rate as percentage.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->tax_rate * 100, 2) . '%';
    }

    /**
     * Get formatted minimum salary.
     */
    public function getFormattedMinSalaryAttribute(): string
    {
        return number_format($this->min_salary, 2);
    }

    /**
     * Get formatted maximum salary.
     */
    public function getFormattedMaxSalaryAttribute(): string
    {
        if ($this->max_salary === null || $this->max_salary >= 999999999999.00) {
            return 'Above';
        }
        return number_format($this->max_salary, 2);
    }

    /**
     * Scope query to annual ZWG table.
     */
    public function scopeAnnualZwl($query)
    {
        return $query->from('tax_bands_annual_zwl');
    }

    /**
     * Scope query to annual USD table.
     */
    public function scopeAnnualUsd($query)
    {
        return $query->from('tax_bands_annual_usd');
    }

    /**
     * Scope query to monthly ZWG table.
     */
    public function scopeMonthlyZwl($query)
    {
        return $query->from('tax_bands_monthly_zwl');
    }

    /**
     * Scope query to monthly USD table.
     */
    public function scopeMonthlyUsd($query)
    {
        return $query->from('tax_bands_monthly_usd');
    }

    /**
     * Check if a salary falls within this tax band.
     */
    public function containsSalary(float $salary): bool
    {
        return $salary >= $this->min_salary &&
               ($this->max_salary === null || $salary <= $this->max_salary);
    }

    /**
     * Calculate tax for income within this band's range.
     */
    public function calculateBandTax(float $taxableIncome, float $previousBandMax = 0): float
    {
        // If income is below this band's minimum, no tax from this band
        if ($taxableIncome <= $this->min_salary) {
            return 0;
        }

        // Calculate the amount of income taxable in this band
        $bandMin = max($this->min_salary, $previousBandMax);
        $bandMax = $this->max_salary ?? PHP_FLOAT_MAX;

        $taxableInBand = min($taxableIncome, $bandMax) - $bandMin;
        $taxableInBand = max(0, $taxableInBand);

        if ($taxableInBand <= 0) {
            return 0;
        }

        // Calculate tax: (income in band * rate) + fixed deduction
        return ($taxableInBand * $this->tax_rate) + $this->tax_amount;
    }

    /**
     * Validation rules for tax bands.
     */
    public static function rules(): array
    {
        return [
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'tax_amount' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get the table name for a specific band type.
     */
    public static function getTableForBandType(string $bandType): ?string
    {
        return self::BAND_TYPES[$bandType] ?? null;
    }

    /**
     * Create a new instance with a specific table.
     */
    public function setTableByType(string $bandType): self
    {
        $table = self::getTableForBandType($bandType);
        if ($table) {
            $this->setTable($table);
        }
        return $this;
    }
}
