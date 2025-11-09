<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterPeriodStatus extends Model
{
    protected $table = 'center_period_status';
    protected $primaryKey = 'status_id';

    protected $fillable = [
        'period_id',
        'center_id',
        'period_currency',
        'period_run_date',
        'pay_run_date',
        'is_closed_confirmed',
    ];

    protected $casts = [
        'period_run_date' => 'datetime',
        'pay_run_date' => 'datetime',
        'is_closed_confirmed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'is_completed',
        'can_be_run',
        'can_be_refreshed',
        'can_be_closed',
    ];

    /**
     * Get the period this status belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id', 'period_id');
    }

    /**
     * Get the cost center this status belongs to.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id', 'id');
    }

    /**
     * Check if period is completed for this center.
     */
    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->pay_run_date) && $this->is_closed_confirmed === true;
    }

    /**
     * Check if period can be run.
     */
    public function getCanBeRunAttribute(): bool
    {
        return is_null($this->period_run_date);
    }

    /**
     * Check if period can be refreshed/recalculated.
     */
    public function getCanBeRefreshedAttribute(): bool
    {
        return !is_null($this->period_run_date) && is_null($this->pay_run_date);
    }

    /**
     * Check if period can be closed.
     */
    public function getCanBeClosedAttribute(): bool
    {
        return $this->can_be_refreshed;
    }

    /**
     * Get status display text.
     */
    public function getStatusDisplayAttribute(): string
    {
        if ($this->is_completed) {
            return 'Completed';
        }
        if ($this->can_be_refreshed) {
            return 'Processed';
        }
        if ($this->can_be_run) {
            return 'Pending';
        }
        return 'Unknown';
    }

    /**
     * Mark period as run.
     */
    public function markAsRun(string $currency = 'DEFAULT'): bool
    {
        return $this->update([
            'period_currency' => $currency,
            'period_run_date' => now(),
        ]);
    }

    /**
     * Mark period as closed/paid.
     */
    public function markAsClosed(): bool
    {
        return $this->update([
            'pay_run_date' => now(),
            'is_closed_confirmed' => true,
        ]);
    }

    /**
     * Reset period status (undo run).
     */
    public function reset(): bool
    {
        return $this->update([
            'period_run_date' => null,
            'pay_run_date' => null,
            'is_closed_confirmed' => null,
        ]);
    }

    /**
     * Scope to completed statuses.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('pay_run_date')
            ->where('is_closed_confirmed', true);
    }

    /**
     * Scope to pending statuses.
     */
    public function scopePending($query)
    {
        return $query->whereNull('period_run_date');
    }

    /**
     * Scope to processed but not closed statuses.
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('period_run_date')
            ->whereNull('pay_run_date');
    }

    /**
     * Scope to specific center.
     */
    public function scopeForCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
            'center_id' => 'required|exists:cost_centers,id',
            'period_currency' => 'required|in:ZWL,USD,DEFAULT',
            'period_run_date' => 'nullable|date',
            'pay_run_date' => 'nullable|date|after:period_run_date',
            'is_closed_confirmed' => 'nullable|boolean',
        ];
    }
}
