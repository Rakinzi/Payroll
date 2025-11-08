<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DischargedEmployeesController extends Controller
{
    /**
     * Display a listing of discharged employees.
     */
    public function index(Request $request)
    {
        $query = Employee::discharged()
            ->with(['department', 'position', 'costCenter']);

        // Apply filters
        if ($request->filled('reason')) {
            $query->where('employment_status', $request->reason);
        }

        if ($request->filled('date_from')) {
            $query->where('is_ex_on', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('is_ex_on', '<=', $request->date_to);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'LIKE', "%{$search}%")
                    ->orWhere('surname', 'LIKE', "%{$search}%")
                    ->orWhere('emp_email', 'LIKE', "%{$search}%");
            });
        }

        $dischargedEmployees = $query->orderBy('is_ex_on', 'desc')
            ->paginate(25);

        // Add computed attributes
        $dischargedEmployees->getCollection()->transform(function ($employee) {
            $employee->days_since_discharge = $employee->days_since_discharge;
            $employee->discharge_reason = $employee->discharge_reason;
            $employee->is_discharged = $employee->is_discharged;
            return $employee;
        });

        return Inertia::render('discharged-employees/index', [
            'dischargedEmployees' => $dischargedEmployees,
            'filters' => $request->only(['reason', 'date_from', 'date_to', 'search']),
            'dischargeReasons' => Employee::getDischargeReasons(),
        ]);
    }

    /**
     * Display the specified discharged employee.
     */
    public function show(Employee $dischargedEmployee)
    {
        // Ensure employee is discharged
        if (!$dischargedEmployee->is_discharged) {
            abort(404, 'Employee is not discharged');
        }

        $dischargedEmployee->load(['department', 'position', 'costCenter', 'occupation', 'paypoint']);

        // Add computed attributes
        $dischargedEmployee->days_since_discharge = $dischargedEmployee->days_since_discharge;
        $dischargedEmployee->discharge_reason = $dischargedEmployee->discharge_reason;
        $dischargedEmployee->full_name = $dischargedEmployee->full_name;

        return Inertia::render('discharged-employees/show', [
            'employee' => $dischargedEmployee,
            'dischargeReasons' => Employee::getDischargeReasons(),
        ]);
    }

    /**
     * Reinstate a discharged employee.
     */
    public function reinstate(Request $request, Employee $dischargedEmployee)
    {
        // Ensure employee is discharged
        if (!$dischargedEmployee->is_discharged) {
            return redirect()->back()
                ->with('error', 'Employee is not discharged');
        }

        $validated = $request->validate([
            'employment_status' => 'required|in:active',
            'reinstated_date' => 'required|date|after_or_equal:today',
        ]);

        $dischargedEmployee->update([
            'is_ex' => false,
            'is_ex_by' => null,
            'is_ex_on' => null,
            'employment_status' => 'active',
            'discharge_notes' => null,
            'reinstated_date' => $validated['reinstated_date'],
        ]);

        return redirect()->route('discharged-employees.index')
            ->with('success', "Employee {$dischargedEmployee->full_name} reinstated successfully");
    }

    /**
     * Permanently delete a discharged employee (hard delete).
     */
    public function destroy(Employee $dischargedEmployee)
    {
        // Ensure employee is discharged
        if (!$dischargedEmployee->is_discharged) {
            return redirect()->back()
                ->with('error', 'Can only permanently delete discharged employees');
        }

        // TODO: Check if employee has payroll history
        // In production, you should check if the employee has any:
        // - Payroll records
        // - Leave records
        // - Other business-critical data
        // If so, prevent deletion or archive instead

        $employeeName = $dischargedEmployee->full_name;
        $dischargedEmployee->forceDelete();

        return redirect()->route('discharged-employees.index')
            ->with('success', "Employee {$employeeName} permanently deleted");
    }
}
