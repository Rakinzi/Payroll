<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostAnalysisCache extends Model
{
    use HasUuids;

    protected $table = 'cost_analysis_cache';

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'report_type',
        'period_start',
        'period_end',
        'currency',
        'total_costs',
        'report_data',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_costs' => 'decimal:2',
        'report_data' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['report_type_display', 'period_display'];

    /**
     * Get the payroll this report belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Get the user who generated this report.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get all breakdown details for this report.
     */
    public function breakdownDetails(): HasMany
    {
        return $this->hasMany(CostBreakdownDetail::class, 'cache_id');
    }

    /**
     * Scope query by payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope query by report type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope query for active (non-expired) reports.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get report type display.
     */
    public function getReportTypeDisplayAttribute(): string
    {
        return match ($this->report_type) {
            'department' => 'Cost by Department',
            'designation' => 'Cost by Designation',
            'codes' => 'Cost by Transaction Codes',
            'leave' => 'Cost by Leave',
            default => 'Unknown'
        };
    }

    /**
     * Get period display.
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->period_start->format('d M Y') . ' - ' . $this->period_end->format('d M Y');
    }

    /**
     * Check if report has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if report can be accessed by user.
     */
    public function canAccess(User $user): bool
    {
        // Admins can access all reports
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        // Users can access reports they generated
        return $this->generated_by === $user->id;
    }

    /**
     * Get supported report types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            'department' => 'Cost by Department',
            'designation' => 'Cost by Designation',
            'codes' => 'Cost by Transaction Codes',
            'leave' => 'Cost by Leave',
        ];
    }
}
