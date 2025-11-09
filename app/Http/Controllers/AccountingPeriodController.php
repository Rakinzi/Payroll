<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPayrollPeriod;
use App\Models\AccountingPeriod;
use App\Models\CostCenter;
use App\Models\Payroll;
use App\Services\PayrollProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AccountingPeriodController extends Controller
{
    /**
     * Display a listing of accounting periods.
     */
    public function index(Request $request)
    {
        // Get payroll ID from request or use the latest payroll
        $payrollId = $request->get('payroll_id');

        if (!$payrollId) {
            $latestPayroll = Payroll::active()->latest()->first();
            $payrollId = $latestPayroll?->id;
        }

        // Get periods for the selected payroll
        $periodsQuery = AccountingPeriod::with(['centerStatuses.center', 'payroll'])
            ->when($payrollId, function ($query) use ($payrollId) {
                $query->forPayroll($payrollId);
            })
            ->orderBy('period_start', 'desc');

        // Filter by year if provided
        if ($request->has('year')) {
            $periodsQuery->forYear($request->get('year'));
        }

        $periods = $periodsQuery->paginate(12);

        // Get all active payrolls for dropdown
        $payrolls = Payroll::active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payroll) {
                return [
                    'id' => $payroll->id,
                    'name' => $payroll->payroll_name,
                    'period_type' => $payroll->period_type,
                    'created_at' => $payroll->created_at->format('Y-m-d'),
                ];
            });

        // Get available years
        $years = range(date('Y') - 2, date('Y') + 2);

        // Get cost centers for admin view
        $costCenters = CostCenter::active()->get();

        return Inertia::render('Payroll/Periods/Index', [
            'periods' => $periods,
            'payrolls' => $payrolls,
            'currentPayrollId' => $payrollId,
            'years' => $years,
            'currentYear' => $request->get('year', date('Y')),
            'costCenters' => $costCenters,
            'userCenterId' => Auth::user()->center_id,
        ]);
    }

    /**
     * Show details for a specific accounting period.
     */
    public function show(AccountingPeriod $period)
    {
        $period->load(['centerStatuses.center', 'payroll', 'payslips.employee']);

        return Inertia::render('Payroll/Periods/Show', [
            'period' => $period,
        ]);
    }

    /**
     * Run/process a period for the user's center.
     */
    public function run(Request $request, AccountingPeriod $period)
    {
        $validated = $request->validate([
            'currency' => 'required|in:ZWG,USD,DEFAULT',
        ]);

        // Authorization check
        if (!$period->canBeRunBy(Auth::user())) {
            return back()->with('error', 'You do not have permission to run this period or it has already been run.');
        }

        // Get user's center
        $centerId = Auth::user()->center_id;

        // Dispatch job for processing
        ProcessPayrollPeriod::dispatch(
            $period,
            $centerId,
            $validated['currency'],
            'run',
            Auth::id()
        );

        return back()->with('success', 'Period processing has been queued. You will be notified when complete.');
    }

    /**
     * Refresh/recalculate a period for the user's center.
     */
    public function refresh(Request $request, AccountingPeriod $period)
    {
        $validated = $request->validate([
            'currency' => 'required|in:ZWG,USD,DEFAULT',
        ]);

        // Authorization check
        if (!$period->canBeRefreshedBy(Auth::user())) {
            return back()->with('error', 'You do not have permission to refresh this period or it cannot be refreshed.');
        }

        // Get user's center
        $centerId = Auth::user()->center_id;

        // Dispatch job for processing
        ProcessPayrollPeriod::dispatch(
            $period,
            $centerId,
            $validated['currency'],
            'refresh',
            Auth::id()
        );

        return back()->with('success', 'Period refresh has been queued. You will be notified when complete.');
    }

    /**
     * Close a period for the user's center.
     */
    public function close(Request $request, AccountingPeriod $period)
    {
        // Authorization check
        if (!$period->canBeClosedBy(Auth::user())) {
            return back()->with('error', 'You do not have permission to close this period or it cannot be closed.');
        }

        try {
            // Get user's center
            $centerId = Auth::user()->center_id;

            // Create instance of PayrollProcessor and close the period
            $processor = app(PayrollProcessor::class);
            $processor->closePeriod($period, $centerId);

            return back()->with('success', 'Period closed successfully. Payslips are now ready for distribution.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to close period: ' . $e->getMessage());
        }
    }

    /**
     * Reopen a closed period for the user's center.
     */
    public function reopen(Request $request, AccountingPeriod $period)
    {
        // Authorization check - only users who can close can also reopen
        if (!$period->canBeClosedBy(Auth::user())) {
            return back()->with('error', 'You do not have permission to reopen this period.');
        }

        try {
            // Get user's center
            $centerId = Auth::user()->center_id;

            // Get center status
            $centerStatus = $period->getCenterStatus($centerId);

            if (!$centerStatus) {
                return back()->with('error', 'Period status not found for your center.');
            }

            if (!$centerStatus->is_closed_confirmed) {
                return back()->with('info', 'Period is already open.');
            }

            // Reopen the period
            $centerStatus->reopen();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => ActivityLog::ACTION_UPDATE,
                'description' => "Reopened accounting period: {$period->month_name} {$period->year} for center {$centerId}",
                'model_type' => 'AccountingPeriod',
                'model_id' => $period->period_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('success', 'Period reopened successfully. You can now make edits to payslips.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reopen period: ' . $e->getMessage());
        }
    }

    /**
     * Get status information for a period (AJAX endpoint).
     */
    public function status(AccountingPeriod $period)
    {
        $period->load(['centerStatuses.center']);

        $centerStatuses = $period->centerStatuses->map(function ($status) {
            return [
                'center_id' => $status->center_id,
                'center_name' => $status->center->center_name,
                'period_currency' => $status->period_currency,
                'period_run_date' => $status->period_run_date?->format('Y-m-d H:i:s'),
                'pay_run_date' => $status->pay_run_date?->format('Y-m-d H:i:s'),
                'is_completed' => $status->is_completed,
                'can_be_run' => $status->can_be_run,
                'can_be_refreshed' => $status->can_be_refreshed,
                'can_be_closed' => $status->can_be_closed,
                'status_display' => $status->status_display,
            ];
        });

        return response()->json([
            'status' => $period->status,
            'is_current' => $period->is_current,
            'is_future' => $period->is_future,
            'is_past' => $period->is_past,
            'completion_percentage' => $period->completion_percentage,
            'is_fully_completed' => $period->isFullyCompleted(),
            'center_statuses' => $centerStatuses,
        ]);
    }

    /**
     * Update period currency for user's center (AJAX endpoint).
     */
    public function updateCurrency(Request $request, AccountingPeriod $period)
    {
        $validated = $request->validate([
            'currency' => 'required|in:ZWG,USD,DEFAULT',
        ]);

        $centerId = Auth::user()->center_id;
        $centerStatus = $period->getOrCreateCenterStatus($centerId, $validated['currency']);

        // Only update if period hasn't been run yet
        if ($centerStatus->can_be_run) {
            $centerStatus->update(['period_currency' => $validated['currency']]);

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cannot update currency after period has been run',
        ], 422);
    }

    /**
     * Generate periods for a payroll year.
     */
    public function generatePeriods(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $payroll = Payroll::findOrFail($validated['payroll_id']);
        $count = AccountingPeriod::generatePeriodsForPayroll($payroll, $validated['year']);

        return back()->with('success', "Generated {$count} accounting periods for {$validated['year']}");
    }

    /**
     * Export period data (for reports).
     */
    public function export(AccountingPeriod $period)
    {
        // TODO: Implement period data export
        // This could generate Excel/PDF reports of payslips, summaries, etc.

        return back()->with('info', 'Export functionality coming soon');
    }

    /**
     * Get period summary statistics.
     */
    public function summary(AccountingPeriod $period)
    {
        $period->load(['centerStatuses', 'payslips']);

        $summary = [
            'total_employees' => $period->payslips->count(),
            'total_gross_zwg' => $period->payslips->sum('gross_salary_zwg'),
            'total_gross_usd' => $period->payslips->sum('gross_salary_usd'),
            'total_deductions_zwg' => $period->payslips->sum('total_deductions_zwg'),
            'total_deductions_usd' => $period->payslips->sum('total_deductions_usd'),
            'total_net_zwg' => $period->payslips->sum('net_salary_zwg'),
            'total_net_usd' => $period->payslips->sum('net_salary_usd'),
            'centers_completed' => $period->centerStatuses->where('is_completed', true)->count(),
            'centers_total' => CostCenter::active()->count(),
        ];

        return response()->json($summary);
    }
}
