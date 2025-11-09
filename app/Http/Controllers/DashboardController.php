<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get statistics based on user's access
        $stats = $this->getDashboardStats($user);

        return Inertia::render('dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get dashboard statistics based on user permissions.
     */
    private function getDashboardStats($user): array
    {
        $stats = [];

        // Super admin sees all data
        if ($user->isSuperAdmin()) {
            $stats['total_cost_centers'] = CostCenter::active()->count();
            $stats['total_employees'] = Employee::active()->count();
            $stats['total_ex_employees'] = Employee::exEmployees()->count();
            $stats['total_users'] = \App\Models\User::active()->count();
        } else {
            // Regular users see only their center's data
            $centerId = $user->center_id;

            $stats['total_employees'] = Employee::active()
                ->inCenter($centerId)
                ->count();

            $stats['total_ex_employees'] = Employee::exEmployees()
                ->inCenter($centerId)
                ->count();

            $stats['cost_center_name'] = $user->costCenter->center_name ?? 'N/A';
        }

        // Recent activity or other relevant stats can be added here
        return $stats;
    }
}
