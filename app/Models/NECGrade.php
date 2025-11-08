<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NECGrade extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'nec_grades';

    protected $fillable = [
        'grade_name',
        't_code_id',
        'contribution',
        'employee_contr_amount',
        'employer_contr_amount',
        'employee_contr_percentage',
        'employer_contr_percentage',
        'min_threshold',
        'max_threshold',
        'is_automatic',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_automatic' => 'boolean',
        'is_active' => 'boolean',
        'employee_contr_amount' => 'decimal:2',
        'employer_contr_amount' => 'decimal:2',
        'employee_contr_percentage' => 'decimal:4',
        'employer_contr_percentage' => 'decimal:4',
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
    ];

    /**
     * Contribution types
     */
    const CONTRIBUTION_AMOUNT = 'Amount';
    const CONTRIBUTION_PERCENTAGE = 'Percentage';

    /**
     * Get the transaction code for this grade.
     */
    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class, 't_code_id');
    }

    /**
     * Get all employees tagged to this grade.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'nec_grades_employees', 'grade_id', 'employee_id')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active grades.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include automatic grades.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Calculate employee contribution.
     */
    public function calculateEmployeeContribution(float $salary): float
    {
        if ($this->contribution === self::CONTRIBUTION_AMOUNT) {
            return $this->employee_contr_amount ?? 0;
        }

        return $salary * (($this->employee_contr_percentage ?? 0) / 100);
    }

    /**
     * Calculate employer contribution.
     */
    public function calculateEmployerContribution(float $salary): float
    {
        if ($this->contribution === self::CONTRIBUTION_AMOUNT) {
            return $this->employer_contr_amount ?? 0;
        }

        return $salary * (($this->employer_contr_percentage ?? 0) / 100);
    }

    /**
     * Check if salary is within threshold.
     */
    public function isWithinThreshold(float $salary): bool
    {
        if ($this->min_threshold && $salary < $this->min_threshold) {
            return false;
        }

        if ($this->max_threshold && $salary > $this->max_threshold) {
            return false;
        }

        return true;
    }
}
