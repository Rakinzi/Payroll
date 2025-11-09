<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AccountingPeriod extends Model
{
    protected $table = 'payroll_accounting_periods';
    protected $primaryKey = 'period_id';

    protected $fillable = [
        'payroll_id',
        'month_name',
        'period_year',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'period_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status',
        'is_current',
        'is_future',
        'is_past',
        'completion_percentage',
    ];

    /**
     * Get the payroll this period belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    /**
     * Get all center statuses for this period.
     */
    public function centerStatuses(): HasMany
    {
        return $this->hasMany(CenterPeriodStatus::class, 'period_id', 'period_id');
    }

    /**
     * Get all payslips for this period.
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'period_id', 'period_id');
    }

    /**
     * Check if period is current.
     */
    public function getIsCurrentAttribute(): bool
    {
        $now = now();
        return $this->period_start <= $now && $this->period_end >= $now;
    }

    /**
     * Check if period is in the future.
     */
    public function getIsFutureAttribute(): bool
    {
        return $this->period_start > now();
    }

    /**
     * Check if period is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->period_end < now();
    }

    /**
     * Get period status (Current, Future, Past).
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_current) {
            return 'Current';
        }
        if ($this->is_future) {
            return 'Future';
        }
        return 'Past';
    }

    /**
     * Get completion percentage across all centers.
     */
    public function getCompletionPercentageAttribute(): float
    {
        $totalCenters = CostCenter::active()->count();
        if ($totalCenters === 0) {
            return 0;
        }

        $completedCenters = $this->centerStatuses()
            ->whereNotNull('period_run_date')
            ->count();

        return round(($completedCenters / $totalCenters) * 100, 2);
    }

    /**
     * Scope to current periods.
     */
    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('period_start', '<=', $now)
            ->where('period_end', '>=', $now);
    }

    /**
     * Scope to periods for specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }

    /**
     * Scope to completed periods.
     */
    public function scopeCompleted($query)
    {
        return $query->whereHas('centerStatuses', function ($q) {
            $q->whereNotNull('pay_run_date')
                ->where('is_closed_confirmed', true);
        });
    }

    /**
     * Scope to periods by year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('period_year', $year);
    }

    /**
     * Check if period can be run by user.
     */
    public function canBeRunBy(User $user): bool
    {
        // Admin can always run
        if ($user->hasRole('admin')) {
            return true;
        }

        $centerStatus = $this->centerStatuses()
            ->where('center_id', $user->center_id)
            ->first();

        return $centerStatus && is_null($centerStatus->period_run_date);
    }

    /**
     * Check if period can be refreshed by user.
     */
    public function canBeRefreshedBy(User $user): bool
    {
        // Admin can always refresh
        if ($user->hasRole('admin')) {
            return true;
        }

        $centerStatus = $this->centerStatuses()
            ->where('center_id', $user->center_id)
            ->first();

        return $centerStatus &&
            !is_null($centerStatus->period_run_date) &&
            is_null($centerStatus->pay_run_date);
    }

    /**
     * Check if period can be closed by user.
     */
    public function canBeClosedBy(User $user): bool
    {
        return $this->canBeRefreshedBy($user);
    }

    /**
     * Get center status for specific center.
     */
    public function getCenterStatus(string $centerId): ?CenterPeriodStatus
    {
        return $this->centerStatuses()
            ->where('center_id', $centerId)
            ->first();
    }

    /**
     * Get or create center status.
     */
    public function getOrCreateCenterStatus(string $centerId, string $currency = 'DEFAULT'): CenterPeriodStatus
    {
        return $this->centerStatuses()->firstOrCreate(
            ['center_id' => $centerId],
            ['period_currency' => $currency]
        );
    }

    /**
     * Check if all centers have completed this period.
     */
    public function isFullyCompleted(): bool
    {
        $totalCenters = CostCenter::active()->count();
        $completedCenters = $this->centerStatuses()
            ->whereNotNull('pay_run_date')
            ->where('is_closed_confirmed', true)
            ->count();

        return $totalCenters > 0 && $totalCenters === $completedCenters;
    }

    /**
     * Get period display name (e.g., "January 2025").
     */
    public function getPeriodDisplayAttribute(): string
    {
        return $this->month_name . ' ' . $this->period_year;
    }

    /**
     * Generate accounting periods for a year.
     */
    public static function generatePeriodsForPayroll(Payroll $payroll, int $year): int
    {
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $count = 0;
        foreach ($months as $index => $month) {
            $monthNumber = $index + 1;
            $periodStart = Carbon::create($year, $monthNumber, 1)->startOfMonth();
            $periodEnd = Carbon::create($year, $monthNumber, 1)->endOfMonth();

            // Check if period already exists
            $exists = self::where('payroll_id', $payroll->id)
                ->where('month_name', $month)
                ->where('period_year', $year)
                ->exists();

            if (!$exists) {
                self::create([
                    'payroll_id' => $payroll->id,
                    'month_name' => $month,
                    'period_year' => $year,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'month_name' => 'required|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ];
    }
}
