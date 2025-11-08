<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    /**
     * Display all payrolls.
     */
    public function index(Request $request)
    {
        $query = Payroll::query();

        // Filter by active status
        if ($request->filled('active_only') && $request->active_only) {
            $query->active();
        }

        // Filter by payroll type
        if ($request->filled('payroll_type')) {
            $query->type($request->payroll_type);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('payroll_name', 'like', '%' . $request->search . '%');
        }

        $payrolls = $query->latest()
            ->get()
            ->map(function ($payroll) {
                return [
                    'id' => $payroll->id,
                    'payroll_name' => $payroll->payroll_name,
                    'payroll_type' => $payroll->payroll_type,
                    'payroll_period' => $payroll->payroll_period,
                    'period_type' => $payroll->period_type,
                    'start_date' => $payroll->start_date->format('Y-m-d'),
                    'tax_method' => $payroll->tax_method,
                    'payroll_currency' => $payroll->payroll_currency,
                    'currency_display' => $payroll->currency_display,
                    'description' => $payroll->description,
                    'is_active' => $payroll->is_active,
                    'active_employee_count' => $payroll->active_employee_count,
                    'created_at' => $payroll->created_at->toISOString(),
                    'updated_at' => $payroll->updated_at->toISOString(),
                ];
            });

        // Get all active employees for assignment
        $employees = Employee::with(['position:id,position_name', 'department:id,department_name'])
            ->where('is_active', true)
            ->where('is_ex', false)
            ->select('id', 'emp_system_id', 'firstname', 'surname', 'othername', 'position_id', 'department_id')
            ->orderBy('firstname')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'emp_system_id' => $employee->emp_system_id,
                    'firstname' => $employee->firstname,
                    'surname' => $employee->surname,
                    'othername' => $employee->othername,
                    'position' => $employee->position ? [
                        'position_name' => $employee->position->position_name,
                    ] : null,
                    'department' => $employee->department ? [
                        'department_name' => $employee->department->department_name,
                    ] : null,
                ];
            });

        // Format supported periods for frontend
        $supportedPeriods = collect(Payroll::getSupportedPeriods())->map(function ($label, $value) {
            return [
                'value' => $value,
                'label' => $label,
            ];
        })->values();

        return Inertia::render('payrolls/index', [
            'payrolls' => [
                'data' => $payrolls,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $payrolls->count(),
                'total' => $payrolls->count(),
            ],
            'employees' => $employees,
            'supportedTypes' => Payroll::getSupportedTypes(),
            'supportedPeriods' => $supportedPeriods,
            'supportedTaxMethods' => Payroll::getSupportedTaxMethods(),
            'supportedCurrencies' => Payroll::getSupportedCurrencies(),
            'filters' => $request->only(['active_only', 'payroll_type', 'search']),
        ]);
    }

    /**
     * Display specific payroll with employees.
     */
    public function show(Payroll $payroll)
    {
        $payroll->load(['employees' => function ($query) {
            $query->select('employees.id', 'emp_system_id', 'firstname', 'surname', 'othername', 'position_id', 'department_id')
                ->where('is_ex', false)
                ->with(['position:id,position_name', 'department:id,department_name']);
        }]);

        // Get all active employees
        $employees = Employee::with(['position:id,position_name', 'department:id,department_name'])
            ->where('is_active', true)
            ->where('is_ex', false)
            ->select('id', 'emp_system_id', 'firstname', 'surname', 'othername', 'position_id', 'department_id')
            ->orderBy('firstname')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'emp_system_id' => $employee->emp_system_id,
                    'firstname' => $employee->firstname,
                    'surname' => $employee->surname,
                    'othername' => $employee->othername,
                    'position' => $employee->position ? [
                        'position_name' => $employee->position->position_name,
                    ] : null,
                    'department' => $employee->department ? [
                        'department_name' => $employee->department->department_name,
                    ] : null,
                ];
            });

        return Inertia::render('payrolls/show', [
            'payroll' => [
                'id' => $payroll->id,
                'payroll_name' => $payroll->payroll_name,
                'payroll_type' => $payroll->payroll_type,
                'payroll_period' => $payroll->payroll_period,
                'period_type' => $payroll->period_type,
                'start_date' => $payroll->start_date->format('Y-m-d'),
                'tax_method' => $payroll->tax_method,
                'payroll_currency' => $payroll->payroll_currency,
                'currency_display' => $payroll->currency_display,
                'description' => $payroll->description,
                'is_active' => $payroll->is_active,
                'active_employee_count' => $payroll->employees->count(),
                'employees' => $payroll->employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'emp_system_id' => $employee->emp_system_id,
                        'firstname' => $employee->firstname,
                        'surname' => $employee->surname,
                        'othername' => $employee->othername,
                        'position' => $employee->position ? [
                            'position_name' => $employee->position->position_name,
                        ] : null,
                        'department' => $employee->department ? [
                            'department_name' => $employee->department->department_name,
                        ] : null,
                    ];
                }),
                'created_at' => $payroll->created_at->toISOString(),
                'updated_at' => $payroll->updated_at->toISOString(),
            ],
            'employees' => $employees,
        ]);
    }

    /**
     * Store a new payroll.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Payroll::rules());

        $payroll = Payroll::create($validated);

        // Log creation
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'description' => "Created payroll: {$payroll->payroll_name}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll created successfully');
    }

    /**
     * Update payroll.
     */
    public function update(Request $request, Payroll $payroll)
    {
        $rules = Payroll::rules(true);

        // Make payroll_name unique except for current payroll
        $rules['payroll_name'] = 'required|string|max:255|unique:payrolls,payroll_name,' . $payroll->id;

        $validated = $request->validate($rules);

        $payroll->update($validated);

        // Log update
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Updated payroll: {$payroll->payroll_name}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll updated successfully');
    }

    /**
     * Delete payroll.
     */
    public function destroy(Payroll $payroll)
    {
        $payrollName = $payroll->payroll_name;

        $payroll->delete();

        // Log deletion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted payroll: {$payrollName}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll deleted successfully');
    }

    /**
     * Assign employees to payroll.
     */
    public function assignEmployees(Request $request, Payroll $payroll)
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $count = $payroll->assignEmployees($request->employee_ids);

        // Log assignment
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Assigned {$count} employee(s) to payroll: {$payroll->payroll_name}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', "{$count} employee(s) assigned to payroll successfully");
    }

    /**
     * Remove employee from payroll.
     */
    public function removeEmployee(Request $request, Payroll $payroll, string $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $payroll->removeEmployee($employeeId);

        // Log removal
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Removed employee {$employee->full_name} from payroll: {$payroll->payroll_name}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', 'Employee removed from payroll successfully');
    }

    /**
     * Toggle payroll active status.
     */
    public function toggleStatus(Payroll $payroll)
    {
        $payroll->update(['is_active' => !$payroll->is_active]);

        $status = $payroll->is_active ? 'activated' : 'deactivated';

        // Log status change
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Payroll {$status}: {$payroll->payroll_name}",
            'model_type' => 'Payroll',
            'model_id' => $payroll->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', "Payroll {$status} successfully");
    }
}
