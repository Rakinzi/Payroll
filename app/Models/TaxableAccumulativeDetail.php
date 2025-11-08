<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxableAccumulativeDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'accumulative_id',
        'employee_id',
        'employee_name',
        'nat_id',
        'ytd_taxable_income',
        'ytd_tax_paid',
        'outstanding_tax',
        'current_month_income',
        'current_month_tax',
    ];

    protected $casts = [
        'ytd_taxable_income' => 'decimal:2',
        'ytd_tax_paid' => 'decimal:2',
        'outstanding_tax' => 'decimal:2',
        'current_month_income' => 'decimal:2',
        'current_month_tax' => 'decimal:2',
    ];

    /**
     * Get the taxable accumulative that owns this detail.
     */
    public function accumulative(): BelongsTo
    {
        return $this->belongsTo(TaxableAccumulative::class, 'accumulative_id');
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the effective tax rate.
     */
    public function getEffectiveTaxRateAttribute(): float
    {
        if ($this->ytd_taxable_income == 0) {
            return 0;
        }

        return ($this->ytd_tax_paid / $this->ytd_taxable_income) * 100;
    }

    /**
     * Get the compliance status.
     */
    public function getComplianceStatusAttribute(): string
    {
        if ($this->outstanding_tax > 0) {
            return 'outstanding';
        }

        if ($this->ytd_tax_paid >= $this->ytd_taxable_income * 0.20) {
            return 'compliant';
        }

        return 'under_withheld';
    }

    /**
     * Scope a query to only include details with outstanding tax.
     */
    public function scopeWithOutstandingTax($query)
    {
        return $query->where('outstanding_tax', '>', 0);
    }

    /**
     * Scope a query to order by taxable income.
     */
    public function scopeOrderByTaxableIncome($query, string $direction = 'desc')
    {
        return $query->orderBy('ytd_taxable_income', $direction);
    }
}
