<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\CenterPeriodStatus;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollProcessor
{
    protected TaxCalculator $taxCalculator;

    public function __construct(TaxCalculator $taxCalculator)
    {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * Run period for a specific center.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @param string $currency
     * @return bool
     */
    public function runPeriod(AccountingPeriod $period, string $centerId, string $currency): bool
    {
        try {
            DB::beginTransaction();

            Log::info("Starting period run for period {$period->period_id}, center {$centerId}");

            // Validate prerequisites
            $this->validatePeriodRun($period, $centerId);

            // Get or create center status
            $centerStatus = $period->getOrCreateCenterStatus($centerId, $currency);

            // Check if already run
            if ($centerStatus->period_run_date) {
                throw new \Exception('Period has already been run for this center');
            }

            // Get active employees for this center
            $employees = Employee::where('center_id', $centerId)
                ->where('is_active', true)
                ->where('is_ex', false)
                ->whereNotNull('emp_role')
                ->get();

            if ($employees->isEmpty()) {
                throw new \Exception('No active employees found for this center');
            }

            // Process each employee
            foreach ($employees as $employee) {
                $this->processEmployee($period, $employee, $currency);
            }

            // Mark period as run
            $centerStatus->markAsRun($currency);

            DB::commit();

            Log::info("Successfully completed period run for period {$period->period_id}, center {$centerId}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to run period {$period->period_id} for center {$centerId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh/recalculate period for a specific center.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @param string $currency
     * @return bool
     */
    public function refreshPeriod(AccountingPeriod $period, string $centerId, string $currency): bool
    {
        try {
            DB::beginTransaction();

            Log::info("Starting period refresh for period {$period->period_id}, center {$centerId}");

            // Get center status
            $centerStatus = $period->getCenterStatus($centerId);

            if (!$centerStatus || !$centerStatus->period_run_date) {
                throw new \Exception('Period has not been run yet for this center');
            }

            if ($centerStatus->pay_run_date) {
                throw new \Exception('Period has already been closed and cannot be refreshed');
            }

            // Get existing payslips for this period and center
            $payslips = Payslip::whereHas('employee', function ($query) use ($centerId) {
                    $query->where('center_id', $centerId);
                })
                ->where('period_month', $this->getMonthNumber($period->month_name))
                ->where('period_year', $period->period_year)
                ->get();

            // Recalculate each payslip
            foreach ($payslips as $payslip) {
                $this->recalculatePayslip($payslip, $period, $currency);
            }

            // Update center status timestamp
            $centerStatus->update(['period_run_date' => now()]);

            DB::commit();

            Log::info("Successfully refreshed period {$period->period_id} for center {$centerId}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to refresh period {$period->period_id} for center {$centerId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Close period for a specific center.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @return bool
     */
    public function closePeriod(AccountingPeriod $period, string $centerId): bool
    {
        try {
            DB::beginTransaction();

            Log::info("Closing period {$period->period_id} for center {$centerId}");

            $centerStatus = $period->getCenterStatus($centerId);

            if (!$centerStatus || !$centerStatus->period_run_date) {
                throw new \Exception('Period has not been run yet for this center');
            }

            if ($centerStatus->is_closed_confirmed) {
                throw new \Exception('Period has already been closed for this center');
            }

            // Finalize all payslips for this period and center
            $this->finalizePayslips($period, $centerId);

            // Mark center status as closed
            $centerStatus->markAsClosed();

            DB::commit();

            Log::info("Successfully closed period {$period->period_id} for center {$centerId}");

            // TODO: Trigger encrypted payslip distribution
            // event(new PeriodClosed($period, $centerId));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to close period {$period->period_id} for center {$centerId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate period run prerequisites.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @return void
     * @throws \Exception
     */
    protected function validatePeriodRun(AccountingPeriod $period, string $centerId): void
    {
        // Validate center exists
        $center = CostCenter::find($centerId);
        if (!$center) {
            throw new \Exception('Cost center not found');
        }

        if (!$center->is_active) {
            throw new \Exception('Cost center is not active');
        }

        // Additional validations can be added here
        // - Check exchange rates are set
        // - Check tax bands are configured
        // - Check transaction codes are set up
    }

    /**
     * Process individual employee for period.
     *
     * @param AccountingPeriod $period
     * @param Employee $employee
     * @param string $currency
     * @return void
     */
    protected function processEmployee(AccountingPeriod $period, Employee $employee, string $currency): void
    {
        // Get employee salary information
        $basicSalaryUsd = $employee->basic_salary_usd ?? 0;
        $basicSalaryZwl = $employee->basic_salary ?? 0;

        // Apply currency split if multi-currency
        if ($currency === 'DEFAULT') {
            $usdPercentage = $employee->usd_percentage ?? 50;
            $zwlPercentage = $employee->zwl_percentage ?? 50;

            // Calculate split amounts (simplified - actual implementation would use exchange rates)
            $totalInUsd = $basicSalaryUsd > 0 ? $basicSalaryUsd : $basicSalaryZwl;
            $basicSalaryUsd = $totalInUsd * ($usdPercentage / 100);
            $basicSalaryZwl = $totalInUsd * ($zwlPercentage / 100);
        } elseif ($currency === 'USD') {
            $basicSalaryZwl = 0;
            $basicSalaryUsd = $basicSalaryUsd > 0 ? $basicSalaryUsd : $basicSalaryZwl;
        } elseif ($currency === 'ZWL') {
            $basicSalaryUsd = 0;
            $basicSalaryZwl = $basicSalaryZwl > 0 ? $basicSalaryZwl : $basicSalaryUsd;
        }

        // Calculate tax
        $taxCalculationUsd = $this->taxCalculator->calculateTax($employee, $basicSalaryUsd, 'USD');
        $taxCalculationZwl = $this->taxCalculator->calculateTax($employee, $basicSalaryZwl, 'ZWL');

        // Create payslip
        $payslip = $this->createPayslip($period, $employee, [
            'basic_salary_usd' => $basicSalaryUsd,
            'basic_salary_zwl' => $basicSalaryZwl,
            'tax_usd' => $taxCalculationUsd['tax_amount'],
            'tax_zwl' => $taxCalculationZwl['tax_amount'],
            'currency' => $currency,
        ]);

        // Process transactions (earnings, deductions, etc.)
        $this->processTransactions($payslip, $employee);
    }

    /**
     * Create payslip for employee.
     *
     * @param AccountingPeriod $period
     * @param Employee $employee
     * @param array $data
     * @return Payslip
     */
    protected function createPayslip(AccountingPeriod $period, Employee $employee, array $data): Payslip
    {
        $monthNumber = $this->getMonthNumber($period->month_name);

        return Payslip::create([
            'employee_id' => $employee->id,
            'payroll_id' => $period->payroll_id,
            'period_month' => $monthNumber,
            'period_year' => $period->period_year,
            'payment_date' => $period->period_end,
            'status' => 'draft',
            'gross_salary_zwg' => $data['basic_salary_zwl'],
            'gross_salary_usd' => $data['basic_salary_usd'],
            'total_deductions_zwg' => $data['tax_zwl'],
            'total_deductions_usd' => $data['tax_usd'],
            'net_salary_zwg' => $data['basic_salary_zwl'] - $data['tax_zwl'],
            'net_salary_usd' => $data['basic_salary_usd'] - $data['tax_usd'],
            'exchange_rate' => 1.0, // TODO: Get actual exchange rate
            'payslip_number' => Payslip::generatePayslipNumber(
                $employee->id,
                $monthNumber,
                $period->period_year
            ),
        ]);
    }

    /**
     * Process transactions for payslip.
     *
     * @param Payslip $payslip
     * @param Employee $employee
     * @return void
     */
    protected function processTransactions(Payslip $payslip, Employee $employee): void
    {
        // Add basic salary transaction
        $payslip->addTransaction([
            'description' => 'Basic Salary',
            'transaction_type' => 'earning',
            'amount_zwg' => $payslip->gross_salary_zwg,
            'amount_usd' => $payslip->gross_salary_usd,
            'is_taxable' => true,
            'is_recurring' => true,
        ]);

        // Add tax deduction transaction
        $payslip->addTransaction([
            'description' => 'PAYE Tax',
            'transaction_type' => 'deduction',
            'amount_zwg' => $payslip->total_deductions_zwg,
            'amount_usd' => $payslip->total_deductions_usd,
            'is_taxable' => false,
            'is_recurring' => true,
        ]);

        // TODO: Add other transaction types (NEC, medical aid, custom transactions, etc.)
    }

    /**
     * Recalculate existing payslip.
     *
     * @param Payslip $payslip
     * @param AccountingPeriod $period
     * @param string $currency
     * @return void
     */
    protected function recalculatePayslip(Payslip $payslip, AccountingPeriod $period, string $currency): void
    {
        $employee = $payslip->employee;

        // Recalculate salary and tax
        $this->processEmployee($period, $employee, $currency);

        // Refresh transactions
        $payslip->recalculateTotals();
    }

    /**
     * Finalize all payslips for period and center.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @return void
     */
    protected function finalizePayslips(AccountingPeriod $period, string $centerId): void
    {
        $monthNumber = $this->getMonthNumber($period->month_name);

        Payslip::whereHas('employee', function ($query) use ($centerId) {
                $query->where('center_id', $centerId);
            })
            ->where('period_month', $monthNumber)
            ->where('period_year', $period->period_year)
            ->where('status', 'draft')
            ->each(function ($payslip) {
                $payslip->finalize();
            });
    }

    /**
     * Get month number from month name.
     *
     * @param string $monthName
     * @return int
     */
    protected function getMonthNumber(string $monthName): int
    {
        $months = [
            'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
            'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
            'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
        ];

        return $months[$monthName] ?? 1;
    }
}
