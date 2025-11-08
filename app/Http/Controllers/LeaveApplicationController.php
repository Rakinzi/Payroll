<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveApplication::with(['employee.department', 'admin'])->latest('date_leave_applied');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        $leaveApplications = $query->paginate(15);

        return Inertia::render('leave/applications/index', [
            'leaveApplications' => $leaveApplications,
            'employees' => Employee::active()->select('id', 'firstname', 'surname', 'employee_code')->get(),
            'leaveTypes' => LeaveApplication::getSupportedLeaveTypes(),
            'filters' => $request->only(['employee_id', 'leave_type', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        return Inertia::render('leave/applications/create', [
            'employees' => Employee::active()->select('id', 'firstname', 'surname', 'employee_code')->get(),
            'leaveTypes' => LeaveApplication::getSupportedLeaveTypes(),
            'leaveSources' => LeaveApplication::getSupportedLeaveSources(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(LeaveApplication::rules());

        $leave = LeaveApplication::create([
            ...$validated,
            'admin_id' => auth()->id(),
            'status' => 'Pending',
            'date_leave_applied' => now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'description' => "Created leave application for {$leave->employee->full_name}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leave->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('leave.applications.index')->with('success', 'Leave application submitted successfully.');
    }

    public function edit(LeaveApplication $leave)
    {
        return Inertia::render('leave/applications/edit', [
            'leave' => $leave,
            'employees' => Employee::active()->select('id', 'firstname', 'surname', 'employee_code')->get(),
            'leaveTypes' => LeaveApplication::getSupportedLeaveTypes(),
            'leaveSources' => LeaveApplication::getSupportedLeaveSources(),
        ]);
    }

    public function update(Request $request, LeaveApplication $leave)
    {
        $validated = $request->validate(LeaveApplication::rules(true));
        $leave->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Updated leave application for {$leave->employee->full_name}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leave->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('leave.applications.index')->with('success', 'Leave application updated successfully.');
    }

    public function destroy(LeaveApplication $leave)
    {
        $employeeName = $leave->employee->full_name;
        $leave->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted leave application for {$employeeName}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leave->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('leave.applications.index')->with('success', 'Leave application deleted successfully.');
    }

    public function approve(Request $request, LeaveApplication $leave)
    {
        $leave->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Leave application approved successfully.');
    }

    public function reject(Request $request, LeaveApplication $leave)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $leave->update([
            'status' => 'Rejected',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'comments' => $leave->comments . "\n\nRejection Reason: " . $request->reason,
        ]);

        return back()->with('success', 'Leave application rejected.');
    }
}
