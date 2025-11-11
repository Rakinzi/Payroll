<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'period',
        'year',
        'balance_bf',
        'balance_cf',
        'days_accrued',
        'days_taken',
        'days_adjusted',
    ];

    protected $casts = [
        'year' => 'integer',
        'balance_bf' => 'decimal:3',
        'balance_cf' => 'decimal:3',
        'days_accrued' => 'decimal:3',
        'days_taken' => 'decimal:3',
        'days_adjusted' => 'decimal:3',
    ];

    /**
     * Get the employee that owns the leave balance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll that owns the leave balance.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the adjustments for this leave balance.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(LeaveBalanceAdjustment::class);
    }

    /**
     * Get the net balance (CF + adjustments).
     */
    public function getNetBalanceAttribute(): float
    {
        return $this->balance_cf + $this->days_adjusted;
    }

    /**
     * Get the utilization percentage.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        $entitlement = $this->employee->leave_entitlement ?? 0;
        return $entitlement > 0 ? (($entitlement - $this->net_balance) / $entitlement) * 100 : 0;
    }

    /**
     * Get utilization status.
     */
    public function getUtilizationStatusAttribute(): string
    {
        $percentage = $this->utilization_percentage;

        if ($percentage >= 90) {
            return 'critical';
        } elseif ($percentage >= 75) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Scope a query to only include balances for a specific employee.
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include balances for a specific payroll.
     */
    public function scopeForPayroll($query, ?string $payrollId)
    {
        if ($payrollId === null) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope a query to only include balances for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to only include balances for a specific period.
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope a query to only include low balances.
     */
    public function scopeLowBalance($query, float $threshold = 5)
    {
        return $query->where('balance_cf', '<=', $threshold);
    }

    /**
     * Adjust the balance.
     */
    public function adjustBalance(float $newBalanceBf, User $adjustedBy, string $reason = null): bool
    {
        $oldBalanceBf = $this->balance_bf;
        $oldBalanceCf = $this->balance_cf;

        // Calculate new CF based on new BF
        $newBalanceCf = $newBalanceBf + $this->days_accrued - $this->days_taken;

        $this->update([
            'balance_bf' => $newBalanceBf,
            'balance_cf' => $newBalanceCf,
        ]);

        // Log the adjustment
        LeaveBalanceAdjustment::create([
            'leave_balance_id' => $this->id,
            'old_balance_bf' => $oldBalanceBf,
            'new_balance_bf' => $newBalanceBf,
            'old_balance_cf' => $oldBalanceCf,
            'new_balance_cf' => $newBalanceCf,
            'adjusted_by' => $adjustedBy->id,
            'adjustment_reason' => $reason ?? 'Manual adjustment',
        ]);

        // Activity log
        ActivityLog::create([
            'user_id' => $adjustedBy->id,
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Adjusted leave balance for {$this->employee->full_name}: BF {$oldBalanceBf} → {$newBalanceBf}, CF {$oldBalanceCf} → {$newBalanceCf}",
            'model_type' => 'LeaveBalance',
            'model_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Calculate and set accrued days.
     */
    public function calculateAccrual(): void
    {
        $accrualRate = $this->employee->leave_accrual_rate ?? 0;

        $this->update([
            'days_accrued' => $accrualRate,
            'balance_cf' => $this->balance_bf + $accrualRate - $this->days_taken,
        ]);
    }

    /**
     * Carry forward balance to next period.
     */
    public function carryForward(string $nextPeriod): ?LeaveBalance
    {
        // Extract year from next period (e.g., "January 2025" -> 2025)
        $nextYear = (int) substr($nextPeriod, -4);

        return static::create([
            'employee_id' => $this->employee_id,
            'payroll_id' => $this->payroll_id,
            'period' => $nextPeriod,
            'year' => $nextYear,
            'balance_bf' => $this->balance_cf,
            'balance_cf' => $this->balance_cf + ($this->employee->leave_accrual_rate ?? 0),
            'days_accrued' => $this->employee->leave_accrual_rate ?? 0,
            'days_taken' => 0,
            'days_adjusted' => 0,
        ]);
    }

    /**
     * Check if balance is critical (low).
     */
    public function isCritical(float $threshold = 5): bool
    {
        return $this->balance_cf <= $threshold;
    }

    /**
     * Validation rules for leave balances.
     */
    public static function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'payroll_id' => 'required|exists:payrolls,id',
            'period' => 'required|string|max:50',
            'year' => 'required|integer|min:2020|max:2100',
            'balance_bf' => 'required|numeric|min:0',
            'balance_cf' => 'required|numeric|min:0',
            'days_accrued' => 'nullable|numeric|min:0',
            'days_taken' => 'nullable|numeric|min:0',
            'days_adjusted' => 'nullable|numeric',
        ];
    }
}
