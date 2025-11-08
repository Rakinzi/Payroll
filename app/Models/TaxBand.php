<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxBand extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'currency',
        'period',
        'min_salary',
        'max_salary',
        'tax_rate',
        'tax_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     * Scope a query to only include active tax bands.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by currency.
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope a query to filter by period.
     */
    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
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
     * Calculate tax for a given salary.
     */
    public function calculateTax(float $salary): float
    {
        if (!$this->containsSalary($salary)) {
            return 0;
        }

        // Fixed tax amount + percentage of excess
        $taxableAmount = $salary - $this->min_salary;
        return $this->tax_amount + ($taxableAmount * ($this->tax_rate / 100));
    }
}
