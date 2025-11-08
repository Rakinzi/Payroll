<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRequisition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'period_start',
        'period_end',
        'total_active_employees',
        'total_terminated',
        'total_hired',
        'turnover_rate',
        'generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_active_employees' => 'integer',
        'total_terminated' => 'integer',
        'total_hired' => 'integer',
        'turnover_rate' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the payroll that owns the employee requisition.
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
     * Get the net change in employees.
     */
    public function getNetChangeAttribute(): int
    {
        return $this->total_hired - $this->total_terminated;
    }

    /**
     * Get the net change percentage.
     */
    public function getNetChangePercentageAttribute(): float
    {
        if ($this->total_active_employees == 0) {
            return 0;
        }

        return ($this->net_change / $this->total_active_employees) * 100;
    }

    /**
     * Get the hiring rate.
     */
    public function getHiringRateAttribute(): float
    {
        if ($this->total_active_employees == 0) {
            return 0;
        }

        return ($this->total_hired / $this->total_active_employees) * 100;
    }

    /**
     * Get the termination rate.
     */
    public function getTerminationRateAttribute(): float
    {
        if ($this->total_active_employees == 0) {
            return 0;
        }

        return ($this->total_terminated / $this->total_active_employees) * 100;
    }

    /**
     * Get the period display.
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->period_start->format('M Y') . ' - ' . $this->period_end->format('M Y');
    }

    /**
     * Get the staffing health status.
     */
    public function getStaffingHealthAttribute(): string
    {
        if ($this->turnover_rate > 20) {
            return 'critical';
        }

        if ($this->turnover_rate > 10) {
            return 'warning';
        }

        return 'healthy';
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
     * Scope a query to only include requisitions for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    /**
     * Scope a query to only include requisitions for a specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope a query to order by turnover rate.
     */
    public function scopeOrderByTurnover($query, string $direction = 'desc')
    {
        return $query->orderBy('turnover_rate', $direction);
    }

    /**
     * Scope a query to order by generation date.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('generated_at', 'desc');
    }
}
