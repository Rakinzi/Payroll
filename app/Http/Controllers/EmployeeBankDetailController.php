<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeBankDetailController extends Controller
{
    /**
     * Display bank details for an employee.
     */
    public function index(Employee $employee)
    {
        $bankDetails = $employee->bankDetails()->with('employee')->get();

        // Add masked account numbers to the response
        $bankDetails->each(function ($detail) {
            $detail->masked_account = $detail->masked_account_number;
        });

        return Inertia::render('employees/banking-details', [
            'employee' => $employee,
            'bankDetails' => $bankDetails,
        ]);
    }

    /**
     * Store a new bank detail for an employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:20',
            'account_number' => 'required|string|min:5|max:30',
            'account_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:Current,Savings,FCA',
            'account_currency' => 'required|in:USD,ZWL,ZiG',
            'capacity' => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, remove other defaults for this employee
        if ($validated['is_default'] ?? false) {
            EmployeeBankDetail::where('employee_id', $validated['employee_id'])
                             ->update(['is_default' => false]);
        }

        $bankDetail = EmployeeBankDetail::create($validated);

        return back()->with('success', 'Bank details added successfully');
    }

    /**
     * Update a bank detail.
     */
    public function update(Request $request, EmployeeBankDetail $bankDetail)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:20',
            'account_number' => 'required|string|min:5|max:30',
            'account_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:Current,Savings,FCA',
            'account_currency' => 'required|in:USD,ZWL,ZiG',
            'capacity' => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, remove other defaults for this employee
        if ($validated['is_default'] ?? false) {
            EmployeeBankDetail::where('employee_id', $bankDetail->employee_id)
                             ->where('id', '!=', $bankDetail->id)
                             ->update(['is_default' => false]);
        }

        $bankDetail->update($validated);

        return back()->with('success', 'Bank details updated successfully');
    }

    /**
     * Remove a bank detail (soft delete).
     */
    public function destroy(EmployeeBankDetail $bankDetail)
    {
        $bankDetail->delete();

        return back()->with('success', 'Bank details deleted successfully');
    }
}
