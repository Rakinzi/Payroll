<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'admin_id',
        'leave_type',
        'leave_source',
        'date_from',
        'date_to',
        'comments',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['total_days', 'leave_type_color'];

    /**
     * Get the employee who applied for leave.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the admin who processed the leave.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope query to specific employee.
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope query by leave type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('leave_type', $type);
    }

    /**
     * Scope query by leave source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('leave_source', $source);
    }

    /**
     * Scope query by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->where(function ($q) use ($from, $to) {
            $q->whereBetween('date_from', [$from, $to])
                ->orWhereBetween('date_to', [$from, $to])
                ->orWhere(function ($q2) use ($from, $to) {
                    $q2->where('date_from', '<=', $from)
                        ->where('date_to', '>=', $to);
                });
        });
    }

    /**
     * Get total leave days (excluding weekends and public holidays).
     */
    public function getTotalDaysAttribute(): int
    {
        return $this->calculateNetLeaveDays($this->date_from, $this->date_to);
    }

    /**
     * Get leave type badge color.
     */
    public function getLeaveTypeColorAttribute(): string
    {
        return match ($this->leave_type) {
            'Sick' => 'destructive',
            'Annual' => 'default',
            'Maternity' => 'secondary',
            'Study' => 'outline',
            default => 'secondary'
        };
    }

    /**
     * Calculate net leave days (gross - weekends - public holidays).
     */
    public function calculateNetLeaveDays(Carbon $from, Carbon $to): int
    {
        $grossDays = $this->calculateGrossDays($from, $to);
        $weekends = $this->countWeekends($from, $to);
        $holidays = $this->countPublicHolidays($from, $to);

        return max(0, $grossDays - $weekends - $holidays);
    }

    /**
     * Calculate gross leave days (including all days).
     */
    private function calculateGrossDays(Carbon $from, Carbon $to): int
    {
        return $from->diffInDays($to) + 1;
    }

    /**
     * Count weekend days in the range.
     */
    private function countWeekends(Carbon $from, Carbon $to): int
    {
        $weekends = 0;
        $current = $from->copy();

        while ($current->lte($to)) {
            if ($current->isWeekend()) {
                $weekends++;
            }
            $current->addDay();
        }

        return $weekends;
    }

    /**
     * Count public holidays in the range.
     */
    private function countPublicHolidays(Carbon $from, Carbon $to): int
    {
        $holidays = 0;
        $year = $from->year;

        // Get all public holidays for the year
        $publicHolidays = $this->getPublicHolidays($year);

        // If date range spans multiple years, get next year's holidays too
        if ($to->year > $year) {
            $publicHolidays = array_merge($publicHolidays, $this->getPublicHolidays($to->year));
        }

        $current = $from->copy();

        while ($current->lte($to)) {
            foreach ($publicHolidays as $holiday) {
                // Count holiday only if it falls on a weekday
                if ($current->isSameDay($holiday) && !$current->isSunday()) {
                    $holidays++;
                    break;
                }
            }
            $current->addDay();
        }

        return $holidays;
    }

    /**
     * Get public holidays for a given year.
     */
    private function getPublicHolidays(int $year): array
    {
        return [
            Carbon::create($year, 1, 1),   // New Year's Day
            Carbon::create($year, 2, 21),  // Robert Mugabe National Youth Day
            Carbon::create($year, 4, 18),  // Independence Day
            Carbon::create($year, 5, 1),   // Workers' Day
            Carbon::create($year, 5, 25),  // Africa Day
            $this->getHeroesDay($year),    // Heroes' Day (Second Monday of August)
            $this->getHeroesDay($year)->copy()->addDay(), // Defence Forces Day (Day after Heroes' Day)
            Carbon::create($year, 12, 22), // Unity Day
            Carbon::create($year, 12, 25), // Christmas Day
            Carbon::create($year, 12, 26), // Boxing Day
        ];
    }

    /**
     * Get Heroes' Day (Second Monday of August).
     */
    private function getHeroesDay(int $year): Carbon
    {
        $august = Carbon::create($year, 8, 1);

        // Find first Monday
        $firstMonday = $august->copy()->next(Carbon::MONDAY);

        // Get second Monday
        return $firstMonday->copy()->next(Carbon::MONDAY);
    }

    /**
     * Validation rules for leave applications.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:Ordinary,Sick,Study,Maternity,Annual,Forced,Special,Unpaid,Other',
            'leave_source' => 'required|in:Normal Leave,Leave Bank',
            'date_from' => 'required|date|before_or_equal:date_to',
            'date_to' => 'required|date|after_or_equal:date_from',
            'comments' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get supported leave types.
     */
    public static function getSupportedLeaveTypes(): array
    {
        return [
            'Ordinary' => 'Ordinary Leave',
            'Sick' => 'Sick Leave',
            'Study' => 'Study Leave',
            'Maternity' => 'Maternity Leave',
            'Annual' => 'Annual Leave',
            'Forced' => 'Forced Leave',
            'Special' => 'Special Leave',
            'Unpaid' => 'Unpaid Leave',
            'Other' => 'Other',
        ];
    }

    /**
     * Get supported leave sources.
     */
    public static function getSupportedLeaveSources(): array
    {
        return [
            'Normal Leave' => 'Normal Leave',
            'Leave Bank' => 'Leave Bank',
        ];
    }
}
