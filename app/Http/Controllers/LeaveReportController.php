<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveReportController extends Controller
{
    public function index()
    {
        return Inertia::render('leave/reports/index', [
            'payrolls' => Payroll::where('is_active', true)->select('id', 'payroll_name')->get(),
            'departments' => Department::orderBy('dept_name')->select('id', 'dept_name')->get(),
            'employees' => Employee::active()->select('id', 'firstname', 'surname', 'employee_code')->get(),
        ]);
    }

    public function periodSummary(Request $request)
    {
        $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'period' => 'required|string',
            'year' => 'required|integer',
        ]);

        $balances = LeaveBalance::with(['employee.department'])
            ->forPayroll($request->payroll_id)
            ->forPeriod($request->period)
            ->forYear($request->year)
            ->get()
            ->groupBy('employee.department.dept_name');

        $pdf = Pdf::loadView('leave.reports.period-summary', [
            'balances' => $balances,
            'period' => $request->period,
            'year' => $request->year,
            'payroll' => Payroll::find($request->payroll_id),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("leave_period_summary_{$request->period}_{$request->year}.pdf");
    }

    public function balances(Request $request)
    {
        $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'year' => 'required|integer',
        ]);

        $balances = LeaveBalance::with(['employee.department'])
            ->forPayroll($request->payroll_id)
            ->forYear($request->year)
            ->get();

        $pdf = Pdf::loadView('leave.reports.balances', [
            'balances' => $balances,
            'year' => $request->year,
            'payroll' => Payroll::find($request->payroll_id),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("leave_balances_{$request->year}.pdf");
    }

    public function warnings(Request $request)
    {
        $threshold = $request->get('threshold', 5);

        $lowBalances = LeaveBalance::with(['employee'])
            ->where('balance_cf', '<=', $threshold)
            ->whereHas('employee', fn($q) => $q->where('is_active', 1))
            ->get();

        $pdf = Pdf::loadView('leave.reports.warnings', [
            'lowBalances' => $lowBalances,
            'threshold' => $threshold,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("leave_warnings_threshold_{$threshold}.pdf");
    }

    public function annualStatement(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer',
        ]);

        $employee = Employee::find($request->employee_id);
        $balances = LeaveBalance::forEmployee($request->employee_id)
            ->forYear($request->year)
            ->orderBy('period')
            ->get();

        $applications = LeaveApplication::forEmployee($request->employee_id)
            ->whereYear('date_from', $request->year)
            ->where('status', 'Approved')
            ->get();

        $pdf = Pdf::loadView('leave.reports.annual-statement', [
            'employee' => $employee,
            'balances' => $balances,
            'applications' => $applications,
            'year' => $request->year,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("leave_annual_statement_{$employee->employee_code}_{$request->year}.pdf");
    }
}
