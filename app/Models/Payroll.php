<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'payroll_name',
        'payroll_type',
        'payroll_period',
        'start_date',
        'tax_method',
        'payroll_currency',
        'description',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_active' => 'boolean',
        'payroll_period' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_PERIOD = 'Period';
    const TYPE_DAILY = 'Daily';
    const TYPE_HOURLY = 'Hourly';

    const PERIOD_MONTHLY = 12;
    const PERIOD_BIWEEKLY = 26;
    const PERIOD_WEEKLY = 52;

    /**
     * Get employees assigned to this payroll.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'payroll_employees', 'payroll_id', 'employee_id')
            ->withPivot('assigned_date', 'is_active')
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * Get all employees (including inactive).
     */
    public function allEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'payroll_employees', 'payroll_id', 'employee_id')
            ->withPivot('assigned_date', 'is_active')
            ->withTimestamps();
    }

    /**
     * Scope query to active payrolls.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to monthly payrolls.
     */
    public function scopeMonthly($query)
    {
        return $query->where('payroll_period', self::PERIOD_MONTHLY);
    }

    /**
     * Scope query by payroll type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('payroll_type', $type);
    }

    /**
     * Get period type display name.
     */
    public function getPeriodTypeAttribute(): string
    {
        return match ($this->payroll_period) {
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_BIWEEKLY => 'Bi-weekly',
            self::PERIOD_WEEKLY => 'Weekly',
            default => 'Custom',
        };
    }

    /**
     * Get currency display name.
     */
    public function getCurrencyDisplayAttribute(): string
    {
        return str_replace('ZWG', 'ZWG', $this->payroll_currency);
    }

    /**
     * Get active employee count.
     */
    public function getActiveEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_name' => $isUpdate ? 'required|string|max:255' : 'required|string|max:255|unique:payrolls,payroll_name',
            'payroll_type' => 'required|in:Period,Daily,Hourly',
            'payroll_period' => 'required|integer|in:12,26,52',
            'start_date' => 'required|date',
            'tax_method' => 'required|string|max:100',
            'payroll_currency' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Assign employee to this payroll.
     */
    public function assignEmployee(string $employeeId, ?\DateTime $assignedDate = null): void
    {
        // Remove employee from any other active payroll assignments
        PayrollEmployee::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Assign to this payroll
        $this->employees()->syncWithoutDetaching([
            $employeeId => [
                'assigned_date' => $assignedDate ?? now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Remove employee from this payroll.
     */
    public function removeEmployee(string $employeeId): void
    {
        PayrollEmployee::where('payroll_id', $this->id)
            ->where('employee_id', $employeeId)
            ->update(['is_active' => false]);
    }

    /**
     * Bulk assign employees to this payroll.
     */
    public function assignEmployees(array $employeeIds): int
    {
        $count = 0;
        foreach ($employeeIds as $employeeId) {
            $this->assignEmployee($employeeId);
            $count++;
        }
        return $count;
    }

    /**
     * Get supported payroll types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            self::TYPE_PERIOD,
            self::TYPE_DAILY,
            self::TYPE_HOURLY,
        ];
    }

    /**
     * Get supported periods.
     */
    public static function getSupportedPeriods(): array
    {
        return [
            self::PERIOD_MONTHLY => 'Monthly (12 periods)',
            self::PERIOD_BIWEEKLY => 'Bi-weekly (26 periods)',
            self::PERIOD_WEEKLY => 'Weekly (52 periods)',
        ];
    }

    /**
     * Get supported tax methods.
     */
    public static function getSupportedTaxMethods(): array
    {
        return [
            'FDS Average',
            'Standard PAYE',
        ];
    }

    /**
     * Get supported currencies.
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            'USD + ZWG',
            'USD Only',
            'ZWG Only',
        ];
    }
}
