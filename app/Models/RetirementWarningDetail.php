<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetirementWarningDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'warning_id',
        'employee_id',
        'employee_name',
        'nat_id',
        'date_of_birth',
        'current_age',
        'hire_date',
        'years_of_service',
        'projected_retirement_date',
        'months_to_retirement',
        'warning_status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'current_age' => 'integer',
        'hire_date' => 'date',
        'years_of_service' => 'integer',
        'projected_retirement_date' => 'date',
        'months_to_retirement' => 'integer',
    ];

    /**
     * Get the retirement warning that owns this detail.
     */
    public function warning(): BelongsTo
    {
        return $this->belongsTo(RetirementWarning::class, 'warning_id');
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the warning status display.
     */
    public function getWarningStatusDisplayAttribute(): string
    {
        return match ($this->warning_status) {
            'approaching' => 'Approaching Retirement',
            'imminent' => 'Imminent Retirement',
            'overdue' => 'Overdue Retirement',
            default => ucfirst($this->warning_status),
        };
    }

    /**
     * Get the urgency level (1-3, 3 being most urgent).
     */
    public function getUrgencyLevelAttribute(): int
    {
        return match ($this->warning_status) {
            'overdue' => 3,
            'imminent' => 2,
            'approaching' => 1,
            default => 0,
        };
    }

    /**
     * Get the years to retirement.
     */
    public function getYearsToRetirementAttribute(): float
    {
        return round($this->months_to_retirement / 12, 1);
    }

    /**
     * Scope a query to only include details with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('warning_status', $status);
    }

    /**
     * Scope a query to order by urgency (most urgent first).
     */
    public function scopeOrderByUrgency($query)
    {
        return $query->orderByRaw("
            CASE warning_status
                WHEN 'overdue' THEN 1
                WHEN 'imminent' THEN 2
                WHEN 'approaching' THEN 3
                ELSE 4
            END
        ")->orderBy('months_to_retirement', 'asc');
    }

    /**
     * Scope a query to order by retirement date.
     */
    public function scopeOrderByRetirementDate($query, string $direction = 'asc')
    {
        return $query->orderBy('projected_retirement_date', $direction);
    }
}
