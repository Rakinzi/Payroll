<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->action($request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('action', 'LIKE', "%{$search}%");
            });
        }

        $activityLogs = $query->paginate(50);

        return Inertia::render('activity-logs/index', [
            'activityLogs' => $activityLogs,
            'actionTypes' => ActivityLog::getActionTypes(),
            'filters' => $request->only(['action', 'user_id', 'start_date', 'end_date', 'search']),
        ]);
    }
}
