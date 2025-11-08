<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetirementWarning extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'warning_threshold_months',
        'total_warnings',
        'generated_at',
    ];

    protected $casts = [
        'warning_threshold_months' => 'integer',
        'total_warnings' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the payroll that owns the retirement warning.
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
     * Get the detail records for this retirement warning.
     */
    public function details(): HasMany
    {
        return $this->hasMany(RetirementWarningDetail::class, 'warning_id');
    }

    /**
     * Get the approaching count.
     */
    public function getApproachingCountAttribute(): int
    {
        return $this->details()->where('warning_status', 'approaching')->count();
    }

    /**
     * Get the imminent count.
     */
    public function getImminentCountAttribute(): int
    {
        return $this->details()->where('warning_status', 'imminent')->count();
    }

    /**
     * Get the overdue count.
     */
    public function getOverdueCountAttribute(): int
    {
        return $this->details()->where('warning_status', 'overdue')->count();
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
     * Scope a query to only include warnings with a specific threshold.
     */
    public function scopeWithThreshold($query, int $months)
    {
        return $query->where('warning_threshold_months', $months);
    }

    /**
     * Scope a query to only include warnings for a specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope a query to order by generation date.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('generated_at', 'desc');
    }
}
