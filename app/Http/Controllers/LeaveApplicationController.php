<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    /**
     * Display all leave applications.
     */
    public function index(Request $request)
    {
        $query = LeaveApplication::with([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'admin:id,name',
        ]);

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        // Filter by leave type
        if ($request->filled('leave_type')) {
            $query->byType($request->leave_type);
        }

        // Filter by leave source
        if ($request->filled('leave_source')) {
            $query->bySource($request->leave_source);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        $leaveApplications = $query->latest()
            ->paginate(15)
            ->through(function ($leave) {
                return [
                    'id' => $leave->id,
                    'employee' => [
                        'id' => $leave->employee->id,
                        'emp_system_id' => $leave->employee->emp_system_id,
                        'full_name' => $leave->employee->full_name,
                        'position' => $leave->employee->position ? [
                            'position_name' => $leave->employee->position->position_name,
                        ] : null,
                        'department' => $leave->employee->department ? [
                            'department_name' => $leave->employee->department->department_name,
                        ] : null,
                    ],
                    'admin' => $leave->admin ? [
                        'name' => $leave->admin->name,
                    ] : null,
                    'leave_type' => $leave->leave_type,
                    'leave_source' => $leave->leave_source,
                    'date_from' => $leave->date_from->format('Y-m-d'),
                    'date_to' => $leave->date_to->format('Y-m-d'),
                    'total_days' => $leave->total_days,
                    'comments' => $leave->comments,
                    'leave_type_color' => $leave->leave_type_color,
                    'created_at' => $leave->created_at->toISOString(),
                ];
            });

        return Inertia::render('leave-applications/index', [
            'leaveApplications' => $leaveApplications,
            'supportedLeaveTypes' => LeaveApplication::getSupportedLeaveTypes(),
            'supportedLeaveSources' => LeaveApplication::getSupportedLeaveSources(),
            'filters' => $request->only(['employee_id', 'leave_type', 'leave_source', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show create leave application form.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)
            ->where('is_ex', false)
            ->with(['position:id,position_name', 'department:id,department_name'])
            ->select('id', 'emp_system_id', 'firstname', 'surname', 'othername', 'position_id', 'department_id')
            ->orderBy('firstname')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'emp_system_id' => $employee->emp_system_id,
                    'full_name' => $employee->full_name,
                    'position' => $employee->position ? [
                        'position_name' => $employee->position->position_name,
                    ] : null,
                    'department' => $employee->department ? [
                        'department_name' => $employee->department->department_name,
                    ] : null,
                ];
            });

        return Inertia::render('leave-applications/create', [
            'employees' => $employees,
            'supportedLeaveTypes' => LeaveApplication::getSupportedLeaveTypes(),
            'supportedLeaveSources' => LeaveApplication::getSupportedLeaveSources(),
        ]);
    }

    /**
     * Store new leave application.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(LeaveApplication::rules());

        $leaveApplication = LeaveApplication::create([
            'employee_id' => $validated['employee_id'],
            'admin_id' => auth()->id(),
            'leave_type' => $validated['leave_type'],
            'leave_source' => $validated['leave_source'],
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'comments' => $validated['comments'] ?? null,
        ]);

        // Log creation
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'description' => "Created leave application for employee: {$leaveApplication->employee->full_name}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leaveApplication->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('leave-applications.index')
            ->with('success', 'Leave application created successfully');
    }

    /**
     * Show single leave application.
     */
    public function show(LeaveApplication $leaveApplication)
    {
        $leaveApplication->load([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'admin:id,name',
        ]);

        return Inertia::render('leave-applications/show', [
            'leaveApplication' => [
                'id' => $leaveApplication->id,
                'employee' => [
                    'id' => $leaveApplication->employee->id,
                    'emp_system_id' => $leaveApplication->employee->emp_system_id,
                    'full_name' => $leaveApplication->employee->full_name,
                    'position' => $leaveApplication->employee->position ? [
                        'position_name' => $leaveApplication->employee->position->position_name,
                    ] : null,
                    'department' => $leaveApplication->employee->department ? [
                        'department_name' => $leaveApplication->employee->department->department_name,
                    ] : null,
                ],
                'admin' => $leaveApplication->admin ? [
                    'name' => $leaveApplication->admin->name,
                ] : null,
                'leave_type' => $leaveApplication->leave_type,
                'leave_source' => $leaveApplication->leave_source,
                'date_from' => $leaveApplication->date_from->format('Y-m-d'),
                'date_to' => $leaveApplication->date_to->format('Y-m-d'),
                'total_days' => $leaveApplication->total_days,
                'comments' => $leaveApplication->comments,
                'leave_type_color' => $leaveApplication->leave_type_color,
                'created_at' => $leaveApplication->created_at->toISOString(),
                'updated_at' => $leaveApplication->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update leave application.
     */
    public function update(Request $request, LeaveApplication $leaveApplication)
    {
        $validated = $request->validate(LeaveApplication::rules(true));

        $leaveApplication->update([
            'employee_id' => $validated['employee_id'],
            'admin_id' => auth()->id(),
            'leave_type' => $validated['leave_type'],
            'leave_source' => $validated['leave_source'],
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'comments' => $validated['comments'] ?? null,
        ]);

        // Log update
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Updated leave application for employee: {$leaveApplication->employee->full_name}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leaveApplication->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('leave-applications.index')
            ->with('success', 'Leave application updated successfully');
    }

    /**
     * Delete leave application.
     */
    public function destroy(LeaveApplication $leaveApplication)
    {
        $employeeName = $leaveApplication->employee->full_name;

        $leaveApplication->delete();

        // Log deletion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted leave application for employee: {$employeeName}",
            'model_type' => 'LeaveApplication',
            'model_id' => $leaveApplication->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('leave-applications.index')
            ->with('success', 'Leave application deleted successfully');
    }
}
