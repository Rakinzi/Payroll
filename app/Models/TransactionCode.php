<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionCode extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code_number',
        'code_name',
        'code_category',
        'is_benefit',
        'code_amount',
        'minimum_threshold',
        'maximum_threshold',
        'code_percentage',
        'is_editable',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_benefit' => 'boolean',
        'is_editable' => 'boolean',
        'is_active' => 'boolean',
        'code_amount' => 'decimal:2',
        'minimum_threshold' => 'decimal:2',
        'maximum_threshold' => 'decimal:2',
        'code_percentage' => 'decimal:4',
    ];

    /**
     * Code categories
     */
    const CATEGORY_EARNING = 'Earning';
    const CATEGORY_DEDUCTION = 'Deduction';
    const CATEGORY_CONTRIBUTION = 'Contribution';

    /**
     * Get NEC grades using this transaction code.
     */
    public function necGrades(): HasMany
    {
        return $this->hasMany(NECGrade::class, 't_code_id');
    }

    /**
     * Scope a query to only include active transaction codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include editable transaction codes.
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('code_category', $category);
    }

    /**
     * Scope a query to only include benefits.
     */
    public function scopeBenefits($query)
    {
        return $query->where('is_benefit', true);
    }

    /**
     * Check if this is a system-defined code.
     */
    public function isSystem(): bool
    {
        return !$this->is_editable;
    }

    /**
     * Calculate the transaction amount based on base amount or employee salary.
     *
     * @param float $baseAmount The base amount to calculate from (e.g., basic salary)
     * @param float $employeeSalary The employee's total salary
     * @return float The calculated amount
     */
    public function calculateAmount(float $baseAmount, float $employeeSalary): float
    {
        // If it's a fixed amount, return it directly
        if ($this->code_amount && $this->code_amount > 0) {
            return $this->code_amount;
        }

        // If it's a percentage-based calculation
        if ($this->code_percentage && $this->code_percentage > 0) {
            $calculatedAmount = $baseAmount * ($this->code_percentage / 100);

            // Apply threshold limits if set
            if ($this->minimum_threshold && $calculatedAmount < $this->minimum_threshold) {
                return $this->minimum_threshold;
            }

            if ($this->maximum_threshold && $calculatedAmount > $this->maximum_threshold) {
                return $this->maximum_threshold;
            }

            return $calculatedAmount;
        }

        return 0;
    }

    /**
     * Determine if this transaction should apply to an employee based on thresholds.
     *
     * @param Employee $employee The employee to check
     * @return bool True if the transaction should apply
     */
    public function shouldApplyTransaction(Employee $employee): bool
    {
        // If no thresholds are set, transaction applies to all
        if (!$this->minimum_threshold && !$this->maximum_threshold) {
            return true;
        }

        $salary = $employee->basic_salary ?? 0;

        // Check minimum threshold
        if ($this->minimum_threshold && $salary < $this->minimum_threshold) {
            return false;
        }

        // Check maximum threshold
        if ($this->maximum_threshold && $salary > $this->maximum_threshold) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted code number with prefix.
     *
     * @return string
     */
    public function getFormattedCodeAttribute(): string
    {
        return 'TC-' . str_pad($this->code_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the available categories.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_EARNING,
            self::CATEGORY_DEDUCTION,
            self::CATEGORY_CONTRIBUTION,
        ];
    }
}
