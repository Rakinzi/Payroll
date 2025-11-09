<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Payroll;
use Carbon\Carbon;

class LeaveAccrualService
{
    /**
     * Process leave accruals for a specific period and payroll.
     *
     * @param string $payrollId
     * @param int $month
     * @param int $year
     * @return array Statistics about accruals processed
     */
    public function processAccruals(string $payrollId, int $month, int $year): array
    {
        $period = Carbon::create($year, $month, 1)->format('F Y');
        $payroll = Payroll::findOrFail($payrollId);

        // Get all active employees for this payroll
        $employees = Employee::where('payroll_id', $payrollId)
            ->where('is_active', true)
            ->get();

        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped_terminated' => 0,
            'errors' => 0,
        ];

        foreach ($employees as $employee) {
            try {
                // Check if employee should accrue leave
                if (!$this->shouldAccrueLeave($employee, $year, $month)) {
                    $stats['skipped_terminated']++;
                    continue;
                }

                // Get or create leave balance for this period
                $balance = $this->processEmployeeAccrual($employee, $payrollId, $period, $year, $month);

                if ($balance->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                $stats['processed']++;

            } catch (\Exception $e) {
                \Log::error("Leave accrual error for employee {$employee->id}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Check if employee should accrue leave for the period.
     *
     * @param Employee $employee
     * @param int $year
     * @param int $month
     * @return bool
     */
    protected function shouldAccrueLeave(Employee $employee, int $year, int $month): bool
    {
        // Don't accrue if employee has no accrual rate set
        if (empty($employee->leave_accrual_rate)) {
            return false;
        }

        // Check if employee was terminated before or during this period
        if ($employee->termination_date) {
            $terminationDate = Carbon::parse($employee->termination_date);
            $periodDate = Carbon::create($year, $month, 1);

            // If terminated before the start of this period, don't accrue
            if ($terminationDate->lt($periodDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process leave accrual for a specific employee.
     *
     * @param Employee $employee
     * @param string $payrollId
     * @param string $period
     * @param int $year
     * @param int $month
     * @return LeaveBalance
     */
    protected function processEmployeeAccrual(
        Employee $employee,
        string $payrollId,
        string $period,
        int $year,
        int $month
    ): LeaveBalance {
        // Get previous period's balance (if exists)
        $previousBalance = $this->getPreviousBalance($employee->id, $payrollId, $year, $month);

        // Check if balance already exists for this period
        $balance = LeaveBalance::firstOrNew([
            'employee_id' => $employee->id,
            'payroll_id' => $payrollId,
            'period' => $period,
            'year' => $year,
        ]);

        // Determine balance_bf (brought forward)
        if (!$balance->exists) {
            // New record - use previous CF or entitlement
            $balance->balance_bf = $previousBalance?->balance_cf ?? $employee->leave_entitlement ?? 0;
        } else {
            // Existing record - preserve manual adjustments by not overwriting balance_bf
            // unless it was never set
            if ($balance->balance_bf === null) {
                $balance->balance_bf = $previousBalance?->balance_cf ?? $employee->leave_entitlement ?? 0;
            }
        }

        // Calculate accrual for this month
        $accrualRate = $employee->leave_accrual_rate ?? 0;

        // Only update days_accrued if not manually set (preserve manual adjustments)
        if ($balance->days_accrued === null || !$balance->exists) {
            $balance->days_accrued = $accrualRate;
        }

        // Preserve days_taken if already set (from leave applications)
        if ($balance->days_taken === null) {
            $balance->days_taken = 0;
        }

        // Preserve days_adjusted (manual adjustments from user)
        if ($balance->days_adjusted === null) {
            $balance->days_adjusted = 0;
        }

        // Calculate balance_cf (carried forward)
        $balance->balance_cf = $balance->balance_bf
            + $balance->days_accrued
            - $balance->days_taken
            + $balance->days_adjusted;

        $balance->save();

        return $balance;
    }

    /**
     * Get previous period's leave balance.
     *
     * @param string $employeeId
     * @param string $payrollId
     * @param int $currentYear
     * @param int $currentMonth
     * @return LeaveBalance|null
     */
    protected function getPreviousBalance(
        string $employeeId,
        string $payrollId,
        int $currentYear,
        int $currentMonth
    ): ?LeaveBalance {
        // Calculate previous period
        $previousDate = Carbon::create($currentYear, $currentMonth, 1)->subMonth();
        $previousPeriod = $previousDate->format('F Y');
        $previousYear = $previousDate->year;

        return LeaveBalance::where('employee_id', $employeeId)
            ->where('payroll_id', $payrollId)
            ->where('period', $previousPeriod)
            ->where('year', $previousYear)
            ->first();
    }

    /**
     * Update leave balance when leave is taken.
     *
     * @param string $employeeId
     * @param string $payrollId
     * @param float $daysTaken
     * @param int $year
     * @param int $month
     * @return bool
     */
    public function deductLeaveDays(
        string $employeeId,
        string $payrollId,
        float $daysTaken,
        int $year,
        int $month
    ): bool {
        $period = Carbon::create($year, $month, 1)->format('F Y');

        $balance = LeaveBalance::firstOrCreate([
            'employee_id' => $employeeId,
            'payroll_id' => $payrollId,
            'period' => $period,
            'year' => $year,
        ]);

        $balance->days_taken = ($balance->days_taken ?? 0) + $daysTaken;
        $balance->balance_cf = $balance->balance_bf
            + ($balance->days_accrued ?? 0)
            - $balance->days_taken
            + ($balance->days_adjusted ?? 0);

        return $balance->save();
    }
}
