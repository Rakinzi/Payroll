<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class LeaveCalculationService
{
    /**
     * Official Zimbabwe Public Holidays (excluding observances like Father's/Mother's Day)
     * These are the gazetted public holidays in Zimbabwe
     */
    private const OFFICIAL_ZW_HOLIDAYS = [
        'New Year\'s Day',
        'New Year Holiday', // When Jan 1 falls on weekend
        'Robert Gabriel Mugabe National Youth Day',
        'Good Friday',
        'Easter Saturday',
        'Easter Monday',
        'Independence Day',
        'Workers\' Day',
        'Africa Day',
        'Heroes\' Day',
        'Defence Forces Day',
        'Unity Day',
        'Christmas Day',
        'Boxing Day',
        'Family Day', // Dec 26 when it's a public holiday
    ];

    /**
     * Calculate working days between two dates based on company policy
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $workingDaysPolicy '5_day', '6_day', or '7_day'
     * @param bool $excludeSaturdays
     * @param bool $excludeSundays
     * @param bool $excludePublicHolidays
     * @param array<string> $customHolidays Additional company-specific holiday dates (Y-m-d format)
     * @return int Number of working days
     */
    public function calculateWorkingDays(
        Carbon $startDate,
        Carbon $endDate,
        string $workingDaysPolicy = '5_day',
        bool $excludeSaturdays = true,
        bool $excludeSundays = true,
        bool $excludePublicHolidays = true,
        array $customHolidays = []
    ): int {
        $start = $startDate->copy()->startOf('day');
        $end = $endDate->copy()->startOf('day');

        // Total days including start and end date
        $totalDays = $start->diffInDays($end) + 1;

        // For 7-day work week (everyday), just return total days
        if ($workingDaysPolicy === '7_day') {
            return $totalDays;
        }

        // Calculate days to exclude
        $excludedDays = 0;

        // Exclude weekends based on policy
        if ($excludeSaturdays || $excludeSundays) {
            $excludedDays += $this->countWeekendDays($start, $end, $excludeSaturdays, $excludeSundays);
        }

        // Exclude public holidays
        if ($excludePublicHolidays) {
            $publicHolidays = $this->getPublicHolidays($start, $end, $excludeSaturdays, $excludeSundays);
            $excludedDays += count($publicHolidays);
        }

        // Exclude custom company holidays
        if (!empty($customHolidays)) {
            $customHolidayDays = $this->countCustomHolidays(
                $start,
                $end,
                $customHolidays,
                $excludeSaturdays,
                $excludeSundays
            );
            $excludedDays += $customHolidayDays;
        }

        return max(0, $totalDays - $excludedDays);
    }

    /**
     * Count weekend days in the date range
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool $excludeSaturdays
     * @param bool $excludeSundays
     * @return int
     */
    private function countWeekendDays(
        Carbon $start,
        Carbon $end,
        bool $excludeSaturdays,
        bool $excludeSundays
    ): int {
        $weekendDays = 0;
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeek;

            if ($excludeSaturdays && $dayOfWeek === Carbon::SATURDAY) {
                $weekendDays++;
            }

            if ($excludeSundays && $dayOfWeek === Carbon::SUNDAY) {
                $weekendDays++;
            }
        }

        return $weekendDays;
    }

    /**
     * Get official public holidays in the date range (excluding weekends if configured)
     * Handles rollover when holidays fall on weekends
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool $excludeSaturdays
     * @param bool $excludeSundays
     * @return array<string> Array of holiday dates (Y-m-d format)
     */
    private function getPublicHolidays(
        Carbon $start,
        Carbon $end,
        bool $excludeSaturdays,
        bool $excludeSundays
    ): array {
        $holidays = [];
        $years = range($start->year, $end->year);

        foreach ($years as $year) {
            $yearHolidays = $this->getZimbabwePublicHolidays($year);

            foreach ($yearHolidays as $holiday) {
                $holidayDate = Carbon::parse($holiday['date']);

                // Only include holidays within the date range
                if ($holidayDate->between($start, $end)) {
                    $dayOfWeek = $holidayDate->dayOfWeek;

                    // Handle holiday rollover
                    if ($holidayDate->isSunday() && $excludeSundays) {
                        // If Sunday is excluded, holiday moves to Monday
                        $observedDate = $holidayDate->copy()->addDay();
                        if ($observedDate->lte($end)) {
                            $holidays[] = $observedDate->format('Y-m-d');
                        }
                    } elseif ($holidayDate->isSaturday() && $excludeSaturdays) {
                        // If Saturday is excluded, holiday moves to Monday
                        $observedDate = $holidayDate->copy()->addDays(2);
                        if ($observedDate->lte($end)) {
                            $holidays[] = $observedDate->format('Y-m-d');
                        }
                    } else {
                        // Holiday falls on a working day (or weekend is not excluded)
                        // Only count if it's not already a weekend we're excluding
                        $isExcludedWeekend = ($dayOfWeek === Carbon::SATURDAY && $excludeSaturdays) ||
                                            ($dayOfWeek === Carbon::SUNDAY && $excludeSundays);

                        if (!$isExcludedWeekend) {
                            $holidays[] = $holidayDate->format('Y-m-d');
                        }
                    }
                }
            }
        }

        return array_unique($holidays);
    }

    /**
     * Get Zimbabwe official public holidays for a given year
     * Filters out observances (Father's Day, Mother's Day, etc.)
     *
     * @param int $year
     * @return array<array{name: string, date: string, type: string}>
     */
    private function getZimbabwePublicHolidays(int $year): array
    {
        $holidays = [];

        // Fixed date holidays
        $fixedHolidays = [
            ['name' => 'New Year\'s Day', 'date' => "$year-01-01"],
            ['name' => 'Robert Gabriel Mugabe National Youth Day', 'date' => "$year-02-21"],
            ['name' => 'Independence Day', 'date' => "$year-04-18"],
            ['name' => 'Workers\' Day', 'date' => "$year-05-01"],
            ['name' => 'Africa Day', 'date' => "$year-05-25"],
            ['name' => 'Heroes\' Day', 'date' => "$year-08-11"], // 2nd Monday in August (approximately)
            ['name' => 'Defence Forces Day', 'date' => "$year-08-12"], // Day after Heroes' Day
            ['name' => 'Unity Day', 'date' => "$year-12-22"],
            ['name' => 'Christmas Day', 'date' => "$year-12-25"],
            ['name' => 'Boxing Day', 'date' => "$year-12-26"],
        ];

        foreach ($fixedHolidays as $holiday) {
            $holidays[] = [
                'name' => $holiday['name'],
                'date' => $holiday['date'],
                'type' => 'public',
            ];
        }

        // Easter-based holidays (moveable)
        $easter = $this->getEasterDate($year);
        $holidays[] = [
            'name' => 'Good Friday',
            'date' => $easter->copy()->subDays(2)->format('Y-m-d'),
            'type' => 'public',
        ];
        $holidays[] = [
            'name' => 'Easter Saturday',
            'date' => $easter->copy()->subDay()->format('Y-m-d'),
            'type' => 'public',
        ];
        $holidays[] = [
            'name' => 'Easter Monday',
            'date' => $easter->copy()->addDay()->format('Y-m-d'),
            'type' => 'public',
        ];

        return $holidays;
    }

    /**
     * Calculate Easter Sunday for a given year using Computus algorithm
     *
     * @param int $year
     * @return Carbon
     */
    private function getEasterDate(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    /**
     * Count custom company holidays that fall within the date range
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array<string> $customHolidays
     * @param bool $excludeSaturdays
     * @param bool $excludeSundays
     * @return int
     */
    private function countCustomHolidays(
        Carbon $start,
        Carbon $end,
        array $customHolidays,
        bool $excludeSaturdays,
        bool $excludeSundays
    ): int {
        $count = 0;

        foreach ($customHolidays as $holidayDate) {
            $date = Carbon::parse($holidayDate);

            if ($date->between($start, $end)) {
                $dayOfWeek = $date->dayOfWeek;

                // Only count if it's not already a weekend we're excluding
                $isExcludedWeekend = ($dayOfWeek === Carbon::SATURDAY && $excludeSaturdays) ||
                                    ($dayOfWeek === Carbon::SUNDAY && $excludeSundays);

                if (!$isExcludedWeekend) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get detailed breakdown of leave days calculation
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $workingDaysPolicy
     * @param bool $excludeSaturdays
     * @param bool $excludeSundays
     * @param bool $excludePublicHolidays
     * @param array<string> $customHolidays
     * @return array{total_days: int, working_days: int, weekend_days: int, public_holidays: int, custom_holidays: int, excluded_dates: array}
     */
    public function getLeaveBreakdown(
        Carbon $startDate,
        Carbon $endDate,
        string $workingDaysPolicy = '5_day',
        bool $excludeSaturdays = true,
        bool $excludeSundays = true,
        bool $excludePublicHolidays = true,
        array $customHolidays = []
    ): array {
        $start = $startDate->copy()->startOf('day');
        $end = $endDate->copy()->startOf('day');

        $totalDays = $start->diffInDays($end) + 1;
        $weekendDays = 0;
        $publicHolidayDays = 0;
        $customHolidayDays = 0;
        $excludedDates = [];

        if ($workingDaysPolicy !== '7_day') {
            // Count weekends
            if ($excludeSaturdays || $excludeSundays) {
                $weekendDays = $this->countWeekendDays($start, $end, $excludeSaturdays, $excludeSundays);

                // Get weekend dates
                $period = CarbonPeriod::create($start, $end);
                foreach ($period as $date) {
                    $dayOfWeek = $date->dayOfWeek;
                    if (($excludeSaturdays && $dayOfWeek === Carbon::SATURDAY) ||
                        ($excludeSundays && $dayOfWeek === Carbon::SUNDAY)) {
                        $excludedDates[] = [
                            'date' => $date->format('Y-m-d'),
                            'type' => 'weekend',
                            'name' => $date->format('l'),
                        ];
                    }
                }
            }

            // Count public holidays
            if ($excludePublicHolidays) {
                $publicHolidays = $this->getPublicHolidays($start, $end, $excludeSaturdays, $excludeSundays);
                $publicHolidayDays = count($publicHolidays);

                foreach ($publicHolidays as $holiday) {
                    $excludedDates[] = [
                        'date' => $holiday,
                        'type' => 'public_holiday',
                        'name' => 'Public Holiday',
                    ];
                }
            }

            // Count custom holidays
            if (!empty($customHolidays)) {
                $customHolidayDays = $this->countCustomHolidays(
                    $start,
                    $end,
                    $customHolidays,
                    $excludeSaturdays,
                    $excludeSundays
                );

                foreach ($customHolidays as $holiday) {
                    $date = Carbon::parse($holiday);
                    if ($date->between($start, $end)) {
                        $dayOfWeek = $date->dayOfWeek;
                        $isExcludedWeekend = ($dayOfWeek === Carbon::SATURDAY && $excludeSaturdays) ||
                                            ($dayOfWeek === Carbon::SUNDAY && $excludeSundays);

                        if (!$isExcludedWeekend) {
                            $excludedDates[] = [
                                'date' => $holiday,
                                'type' => 'custom_holiday',
                                'name' => 'Company Holiday',
                            ];
                        }
                    }
                }
            }
        }

        $workingDays = $totalDays - $weekendDays - $publicHolidayDays - $customHolidayDays;

        return [
            'total_days' => $totalDays,
            'working_days' => max(0, $workingDays),
            'weekend_days' => $weekendDays,
            'public_holidays' => $publicHolidayDays,
            'custom_holidays' => $customHolidayDays,
            'excluded_dates' => $excludedDates,
        ];
    }
}
