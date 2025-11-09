<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomTransaction extends Model
{
    protected $table = 'custom_transactions_tbl';
    protected $primaryKey = 'custom_id';

    protected $fillable = [
        'center_id',
        'period_id',
        'worked_hours',
        'base_hours',
        'base_amount',
        'use_basic',
    ];

    protected $casts = [
        'worked_hours' => 'decimal:2',
        'base_hours' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'use_basic' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'employee_count',
        'transaction_count',
        'amount_type',
        'formatted_base_amount',
        'work_ratio',
    ];

    /**
     * Get the cost center.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id', 'id');
    }

    /**
     * Get the accounting period.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id', 'period_id');
    }

    /**
     * Get assigned employees.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'custom_transactions_employees_tbl',
            'custom_id',
            'employee_id'
        )->withTimestamps();
    }

    /**
     * Get assigned transaction codes.
     */
    public function transactionCodes(): BelongsToMany
    {
        return $this->belongsToMany(
            TransactionCode::class,
            'custom_transactions_tag_tbl',
            'custom_id',
            'code_id'
        )->withTimestamps();
    }

    /**
     * Get employee count.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Get transaction code count.
     */
    public function getTransactionCountAttribute(): int
    {
        return $this->transactionCodes()->count();
    }

    /**
     * Get amount type description.
     */
    public function getAmountTypeAttribute(): string
    {
        return $this->use_basic ? 'Basic Salary' : 'Custom Amount';
    }

    /**
     * Get formatted base amount.
     */
    public function getFormattedBaseAmountAttribute(): string
    {
        if ($this->use_basic) {
            return 'N/A';
        }

        return $this->base_amount
               ? number_format($this->base_amount, 2) . ' USD'
               : '0.00 USD';
    }

    /**
     * Get work ratio.
     */
    public function getWorkRatioAttribute(): float
    {
        if ($this->base_hours == 0) {
            return 0;
        }

        return round(($this->worked_hours / $this->base_hours) * 100, 2);
    }

    /**
     * Scope to filter by period.
     */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope to filter by center.
     */
    public function scopeForCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Calculate amount for specific employee.
     */
    public function calculateAmountForEmployee(
        Employee $employee,
        string $currency,
        float $exchangeRate = 1.0
    ): float {
        $worked_hours = $this->worked_hours;
        $base_hours = $this->base_hours;

        // Check if this is a shift allowance (don't cap hours)
        $shiftAllowanceCodes = ['SHIFT ALLOWANCE', 'SHIFT'];
        $isShiftAllowance = $this->transactionCodes()
                                ->whereIn('code_name', $shiftAllowanceCodes)
                                ->exists();

        if (!$isShiftAllowance) {
            $worked_hours = min($worked_hours, $base_hours);
        }

        $work_ratio = $base_hours > 0 ? $worked_hours / $base_hours : 0;

        // Determine base amount
        if ($this->use_basic) {
            $base_amount = $currency === 'ZWL'
                         ? $employee->basic_salary
                         : $employee->basic_salary_usd;
        } else {
            $base_amount = $this->base_amount ?? 0;

            // Convert to target currency if needed
            if ($currency === 'ZWL') {
                $base_amount *= $exchangeRate;
            }
        }

        return round($work_ratio * $base_amount, 2);
    }

    /**
     * Assign employees to this custom transaction.
     */
    public function assignToEmployees(array $employeeIds): void
    {
        if (in_array('all', $employeeIds)) {
            // Assign to all active employees in center
            $employees = Employee::where('center_id', $this->center_id)
                               ->where('is_active', true)
                               ->where('is_ex', false)
                               ->whereNotNull('emp_role')
                               ->get();

            $this->employees()->sync($employees->pluck('id'));
        } else {
            $this->employees()->sync($employeeIds);
        }
    }

    /**
     * Assign transaction codes to this custom transaction.
     */
    public function assignTransactionCodes(array $codeIds): void
    {
        $this->transactionCodes()->sync($codeIds);
    }

    /**
     * Check if can be modified by user.
     */
    public function canBeModifiedBy(User $user): bool
    {
        return $user->hasRole('admin') || $this->center_id === $user->center_id;
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'center_id' => 'required|exists:cost_centers,id',
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
            'worked_hours' => 'required|numeric|min:0',
            'base_hours' => 'required|numeric|min:1',
            'base_amount' => 'nullable|numeric|min:0',
            'use_basic' => 'required|boolean',
            'employees' => 'required|array|min:1',
            'employees.*' => 'string', // Allow 'all' or UUIDs
            'transaction_codes' => 'required|array|min:1',
            'transaction_codes.*' => 'exists:transaction_codes,code_id',
        ];
    }
}
