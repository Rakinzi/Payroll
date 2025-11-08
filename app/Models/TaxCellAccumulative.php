<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCellAccumulative extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'tax_year',
        'currency',
        'tax_bracket_summary',
        'generated_at',
    ];

    protected $casts = [
        'tax_year' => 'integer',
        'tax_bracket_summary' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the payroll that owns the tax cell accumulative.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the user who generated the report.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the detail records for this tax cell accumulative.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TaxCellAccumulativeDetail::class, 'cell_accumulative_id');
    }

    /**
     * Get the employee count accessor.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->details()->distinct('employee_id')->count('employee_id');
    }

    /**
     * Get the currency display name.
     */
    public function getCurrencyDisplayAttribute(): string
    {
        return match ($this->currency) {
            'ZWG' => 'Zimbabwe Gold (ZWG)',
            'USD' => 'United States Dollar (USD)',
            default => $this->currency,
        };
    }

    /**
     * Get the total income across all brackets.
     */
    public function getTotalIncomeAttribute(): float
    {
        return $this->details()->sum('ytd_income_in_bracket');
    }

    /**
     * Get the total tax across all brackets.
     */
    public function getTotalTaxAttribute(): float
    {
        return $this->details()->sum('ytd_tax_in_bracket');
    }

    /**
     * Check if user can access this report.
     */
    public function canAccess(User $user): bool
    {
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        return $this->payroll->cost_center_id === $user->cost_center_id;
    }

    /**
     * Scope a query to only include reports for a specific tax year.
     */
    public function scopeForTaxYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    /**
     * Scope a query to only include reports for a specific currency.
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope a query to only include reports for a specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }
}
