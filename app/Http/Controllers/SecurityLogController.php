<?php

namespace App\Http\Controllers;

use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityLogController extends Controller
{
    /**
     * Display a listing of security logs.
     */
    public function index(Request $request)
    {
        $query = SecurityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by severity
        if ($request->filled('severity')) {
            $query->severity($request->severity);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->event($request->event);
        }

        // Filter high severity only
        if ($request->filled('high_severity') && $request->high_severity) {
            $query->highSeverity();
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        $securityLogs = $query->paginate(50);

        // Get statistics
        $statistics = [
            'total_events' => SecurityLog::count(),
            'high_severity_count' => SecurityLog::highSeverity()->count(),
            'today_events' => SecurityLog::whereDate('created_at', today())->count(),
            'failed_logins_today' => SecurityLog::event(SecurityLog::EVENT_FAILED_LOGIN)
                                              ->whereDate('created_at', today())
                                              ->count(),
        ];

        return Inertia::render('security-logs/index', [
            'securityLogs' => $securityLogs,
            'severityLevels' => SecurityLog::getSeverityLevels(),
            'eventTypes' => SecurityLog::getEventTypes(),
            'statistics' => $statistics,
            'filters' => $request->only(['severity', 'event', 'high_severity', 'start_date', 'end_date', 'search']),
        ]);
    }
}
