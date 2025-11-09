<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'created_by',
        'payslip_number',
        'period_month',
        'period_year',
        'payment_date',
        'status',
        'gross_salary_zwg',
        'total_deductions_zwg',
        'net_salary_zwg',
        'gross_salary_usd',
        'total_deductions_usd',
        'net_salary_usd',
        'ytd_gross_zwg',
        'ytd_gross_usd',
        'ytd_paye_zwg',
        'ytd_paye_usd',
        'exchange_rate',
        'notes',
        'finalized_at',
        'distributed_at',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'payment_date' => 'date',
        'gross_salary_zwg' => 'decimal:2',
        'total_deductions_zwg' => 'decimal:2',
        'net_salary_zwg' => 'decimal:2',
        'gross_salary_usd' => 'decimal:2',
        'total_deductions_usd' => 'decimal:2',
        'net_salary_usd' => 'decimal:2',
        'ytd_gross_zwg' => 'decimal:2',
        'ytd_gross_usd' => 'decimal:2',
        'ytd_paye_zwg' => 'decimal:2',
        'ytd_paye_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'finalized_at' => 'datetime',
        'distributed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['period_display', 'status_display'];

    /**
     * Get the employee who owns this payslip.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the payroll this payslip belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Get the user who created this payslip.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all transactions for this payslip.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PayslipTransaction::class, 'payslip_id')->orderBy('display_order');
    }

    /**
     * Get all distribution logs for this payslip.
     */
    public function distributionLogs(): HasMany
    {
        return $this->hasMany(PayslipDistributionLog::class, 'payslip_id');
    }

    /**
     * Scope query to specific employee.
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope query to specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope query to specific period.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)
                    ->where('period_year', $year);
    }

    /**
     * Scope query by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query for finalized payslips only.
     */
    public function scopeFinalized($query)
    {
        return $query->whereNotNull('finalized_at');
    }

    /**
     * Scope query for distributed payslips only.
     */
    public function scopeDistributed($query)
    {
        return $query->whereNotNull('distributed_at');
    }

    /**
     * Get period display (e.g., "January 2025").
     */
    public function getPeriodDisplayAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return ($months[$this->period_month] ?? 'Unknown') . ' ' . $this->period_year;
    }

    /**
     * Get status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Add transaction to payslip.
     */
    public function addTransaction(array $data): PayslipTransaction
    {
        // Get next display order
        $maxOrder = $this->transactions()
            ->where('transaction_type', $data['transaction_type'])
            ->max('display_order');

        $transaction = $this->transactions()->create([
            'transaction_code_id' => $data['transaction_code_id'] ?? null,
            'description' => $data['description'],
            'transaction_type' => $data['transaction_type'],
            'amount_zwg' => $data['amount_zwg'] ?? 0,
            'amount_usd' => $data['amount_usd'] ?? 0,
            'is_taxable' => $data['is_taxable'] ?? false,
            'is_recurring' => $data['is_recurring'] ?? true,
            'is_manual' => $data['is_manual'] ?? false,
            'notes' => $data['notes'] ?? null,
            'display_order' => ($maxOrder ?? -1) + 1,
            // Calculation fields for short time, overtime, etc.
            'days' => $data['days'] ?? null,
            'hours' => $data['hours'] ?? null,
            'rate' => $data['rate'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'calculation_basis' => $data['calculation_basis'] ?? null,
            'is_calculated' => $data['is_calculated'] ?? false,
            'manual_override' => $data['manual_override'] ?? false,
            'calculation_metadata' => $data['calculation_metadata'] ?? null,
        ]);

        $this->recalculateTotals();

        return $transaction;
    }

    /**
     * Remove transaction from payslip.
     */
    public function removeTransaction(string $transactionId): void
    {
        $this->transactions()->where('id', $transactionId)->delete();
        $this->recalculateTotals();
    }

    /**
     * Recalculate all payslip totals.
     */
    public function recalculateTotals(): void
    {
        $earnings = $this->transactions()
            ->where('transaction_type', 'earning')
            ->get();

        $deductions = $this->transactions()
            ->where('transaction_type', 'deduction')
            ->get();

        // Calculate ZWG totals
        $this->gross_salary_zwg = $earnings->sum('amount_zwg');
        $this->total_deductions_zwg = $deductions->sum('amount_zwg');
        $this->net_salary_zwg = $this->gross_salary_zwg - $this->total_deductions_zwg;

        // Calculate USD totals
        $this->gross_salary_usd = $earnings->sum('amount_usd');
        $this->total_deductions_usd = $deductions->sum('amount_usd');
        $this->net_salary_usd = $this->gross_salary_usd - $this->total_deductions_usd;

        $this->save();
    }

    /**
     * Finalize the payslip (lock for editing).
     */
    public function finalize(): void
    {
        if ($this->status !== 'draft') {
            throw new \Exception('Only draft payslips can be finalized');
        }

        $this->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);
    }

    /**
     * Mark payslip as distributed.
     */
    public function markDistributed(): void
    {
        if ($this->status !== 'finalized') {
            throw new \Exception('Only finalized payslips can be marked as distributed');
        }

        $this->update([
            'status' => 'distributed',
            'distributed_at' => now(),
        ]);
    }

    /**
     * Cancel the payslip.
     */
    public function cancel(): void
    {
        if ($this->status === 'distributed') {
            throw new \Exception('Distributed payslips cannot be cancelled');
        }

        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if payslip can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if payslip can be finalized.
     */
    public function canBeFinalized(): bool
    {
        return $this->status === 'draft' && $this->transactions()->count() > 0;
    }

    /**
     * Check if payslip can be distributed.
     */
    public function canBeDistributed(): bool
    {
        return $this->status === 'finalized';
    }

    /**
     * Generate unique payslip number.
     */
    public static function generatePayslipNumber(string $employeeId, int $month, int $year): string
    {
        $employee = Employee::find($employeeId);
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);

        return "PS-{$employee->emp_system_id}-{$year}{$monthStr}";
    }

    /**
     * Get supported statuses.
     */
    public static function getSupportedStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'finalized' => 'Finalized',
            'distributed' => 'Distributed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Validation rules for payslips.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'payroll_id' => 'required|exists:payrolls,id',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'payment_date' => 'required|date',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
