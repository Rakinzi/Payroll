<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $payrollId = $request->get('payroll_id') ?: Payroll::where('is_active', true)->first()?->id;
        $period = $request->get('period') ?: date('F Y');
        $year = $request->get('year') ?: date('Y');

        $query = LeaveBalance::with(['employee.department', 'payroll'])
            ->forPayroll($payrollId)
            ->forPeriod($period)
            ->forYear($year);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaveBalances = $query->paginate(15);

        return Inertia::render('leave/balances/index', [
            'leaveBalances' => $leaveBalances,
            'payrolls' => Payroll::where('is_active', true)->select('id', 'payroll_name')->get(),
            'employees' => Employee::active()->select('id', 'firstname', 'surname', 'emp_system_id')->get(),
            'filters' => $request->only(['payroll_id', 'period', 'year', 'employee_id']),
        ]);
    }

    public function update(Request $request, LeaveBalance $balance)
    {
        $request->validate([
            'balance_bf' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $balance->adjustBalance($request->balance_bf, auth()->user(), $request->reason);

        return back()->with('success', 'Leave balance updated successfully.');
    }
}
