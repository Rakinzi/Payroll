<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\NECGrade;
use App\Models\Occupation;
use App\Models\Paypoint;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'costCenter',
            'department',
            'position',
            'occupation',
            'paypoint',
            'necGrade',
        ]);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                  ->orWhere('surname', 'LIKE', "%{$search}%")
                  ->orWhere('emp_system_id', 'LIKE', "%{$search}%")
                  ->orWhere('emp_email', 'LIKE', "%{$search}%")
                  ->orWhere('nat_id', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->get('status') === 'active') {
                $query->active();
            } elseif ($request->get('status') === 'inactive') {
                $query->exEmployees();
            }
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(25);

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'status', 'department_id']),
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        return Inertia::render('employees/create', [
            'costCenters' => CostCenter::active()->get(),
            'departments' => Department::active()->get(),
            'positions' => Position::active()->get(),
            'occupations' => Occupation::active()->get(),
            'paypoints' => Paypoint::active()->get(),
            'necGrades' => NECGrade::active()->get(),
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Employee ID - optional, auto-generated if not provided
            'emp_system_id' => 'nullable|string|max:50|unique:employees,emp_system_id',

            // Personal Information (Required)
            'firstname' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'emp_email' => 'required|email|unique:employees,emp_email',
            'center_id' => 'required|exists:cost_centers,id',

            // Personal Information (Optional)
            'title' => 'nullable|string|max:50',
            'othername' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'nat_id' => 'nullable|string|max:50|unique:employees,nat_id',
            'nassa_number' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',

            // Contact Information
            'home_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'personal_email_address' => 'nullable|email|max:255',

            // Identification
            'passport' => 'nullable|string|max:50',
            'driver_license' => 'nullable|string|max:50',

            // Employment Information
            'hire_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'occupation_id' => 'nullable|exists:occupations,id',
            'paypoint_id' => 'nullable|exists:paypoints,id',
            'average_working_days' => 'nullable|integer|min:1|max:31',
            'working_hours' => 'nullable|numeric|min:0|max:24',
            'payment_basis' => 'nullable|in:monthly,hourly,daily',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',

            // Compensation & Benefits
            'basic_salary' => 'nullable|numeric|min:0',
            'basic_salary_usd' => 'nullable|numeric|min:0',
            'leave_entitlement' => 'nullable|numeric|min:0|max:365',
            'leave_accrual' => 'nullable|numeric|min:0',

            // Tax Configuration
            'tax_directives' => 'nullable|string|max:255',
            'disability_status' => 'boolean',
            'dependents' => 'nullable|integer|min:0',
            'vehicle_engine_capacity' => 'nullable|integer|min:0',

            // Currency Splitting
            'zwl_percentage' => 'nullable|numeric|min:0|max:100',
            'usd_percentage' => 'nullable|numeric|min:0|max:100',

            // NEC Integration
            'nec_grade_id' => 'nullable|exists:nec_grades,id',

            // Role
            'emp_role' => 'nullable|string|max:50',

            // Status
            'is_active' => 'boolean',
        ]);

        $employee = Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->emp_system_id} created successfully");
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        $employee->load([
            'costCenter',
            'department',
            'position',
            'occupation',
            'paypoint',
            'necGrade',
            'bankDetails',
        ]);

        return Inertia::render('employees/show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('employees/edit', [
            'employee' => $employee,
            'costCenters' => CostCenter::active()->get(),
            'departments' => Department::active()->get(),
            'positions' => Position::active()->get(),
            'occupations' => Occupation::active()->get(),
            'paypoints' => Paypoint::active()->get(),
            'necGrades' => NECGrade::active()->get(),
        ]);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            // Employee ID - optional, auto-generated if empty
            'emp_system_id' => "nullable|string|max:50|unique:employees,emp_system_id,{$employee->id}",

            // Personal Information (Required)
            'firstname' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'emp_email' => "required|email|unique:employees,emp_email,{$employee->id}",
            'center_id' => 'required|exists:cost_centers,id',

            // Personal Information (Optional)
            'title' => 'nullable|string|max:50',
            'othername' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'nat_id' => "nullable|string|max:50|unique:employees,nat_id,{$employee->id}",
            'nassa_number' => 'nullable|string|max:50',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',

            // Contact Information
            'home_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'personal_email_address' => 'nullable|email|max:255',

            // Identification
            'passport' => 'nullable|string|max:50',
            'driver_license' => 'nullable|string|max:50',

            // Employment Information
            'hire_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'occupation_id' => 'nullable|exists:occupations,id',
            'paypoint_id' => 'nullable|exists:paypoints,id',
            'average_working_days' => 'nullable|integer|min:1|max:31',
            'working_hours' => 'nullable|numeric|min:0|max:24',
            'payment_basis' => 'nullable|in:monthly,hourly,daily',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',

            // Compensation & Benefits
            'basic_salary' => 'nullable|numeric|min:0',
            'basic_salary_usd' => 'nullable|numeric|min:0',
            'leave_entitlement' => 'nullable|numeric|min:0|max:365',
            'leave_accrual' => 'nullable|numeric|min:0',

            // Tax Configuration
            'tax_directives' => 'nullable|string|max:255',
            'disability_status' => 'boolean',
            'dependents' => 'nullable|integer|min:0',
            'vehicle_engine_capacity' => 'nullable|integer|min:0',

            // Currency Splitting
            'zwl_percentage' => 'nullable|numeric|min:0|max:100',
            'usd_percentage' => 'nullable|numeric|min:0|max:100',

            // NEC Integration
            'nec_grade_id' => 'nullable|exists:nec_grades,id',

            // Role
            'emp_role' => 'nullable|string|max:50',

            // Status
            'is_active' => 'boolean',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->emp_system_id} updated successfully");
    }

    /**
     * Remove the specified employee (soft delete).
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->emp_system_id} deleted successfully");
    }

    /**
     * Terminate an employee.
     */
    public function terminate(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'is_ex_on' => 'required|date',
            'employment_status' => 'required|in:END CONTRACT,RESIGNED,DISMISSED,DECEASED,SUSPENDED',
            'discharge_notes' => 'nullable|string',
        ]);

        $employee->update([
            'is_ex' => true,
            'is_ex_on' => $validated['is_ex_on'],
            'employment_status' => $validated['employment_status'],
            'discharge_notes' => $validated['discharge_notes'] ?? null,
            'is_active' => false,
        ]);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->emp_system_id} terminated successfully");
    }

    /**
     * Restore a terminated employee.
     */
    public function restore(Employee $employee)
    {
        $employee->update([
            'is_ex' => false,
            'is_ex_on' => null,
            'employment_status' => 'active',
            'discharge_notes' => null,
            'is_active' => true,
        ]);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->emp_system_id} restored successfully");
    }
}
