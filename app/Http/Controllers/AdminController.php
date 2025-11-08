<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Ensure only super admins can access admin management.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin()) {
                abort(403, 'Access denied. Super admin privileges required.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of admin users.
     */
    public function index(Request $request)
    {
        $query = User::admins()
            ->with(['employee', 'costCenter'])
            ->withCount('roles');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('firstname', 'LIKE', "%{$search}%")
                            ->orWhere('surname', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filter by cost center
        if ($request->filled('cost_center')) {
            if ($request->cost_center === 'super') {
                $query->whereNull('center_id');
            } else {
                $query->where('center_id', $request->cost_center);
            }
        }

        $admins = $query->orderBy('name')->paginate(25);

        // Add computed attributes
        $admins->getCollection()->transform(function ($admin) {
            $admin->is_super_admin = $admin->isSuperAdmin();
            $admin->is_cost_center_admin = $admin->isCostCenterAdmin();
            return $admin;
        });

        $costCenters = CostCenter::orderBy('center_name')->get();

        return Inertia::render('admins/index', [
            'admins' => $admins,
            'costCenters' => $costCenters,
            'filters' => $request->only(['search', 'cost_center']),
            'currentUserId' => auth()->id(),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'center_id' => 'nullable|exists:cost_centers,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'center_id' => $validated['center_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'is_active' => true,
        ]);

        // Assign admin role
        $user->assignRole('admin');

        return redirect()->route('admins.index')
            ->with('success', "Admin account created successfully for {$user->name}");
    }

    /**
     * Display the specified admin user.
     */
    public function show(User $admin)
    {
        // Ensure target user is an admin
        if (!$admin->isAdmin()) {
            abort(404, 'User is not an admin');
        }

        $admin->load(['employee', 'costCenter', 'roles', 'permissions']);

        $admin->is_super_admin = $admin->isSuperAdmin();
        $admin->is_cost_center_admin = $admin->isCostCenterAdmin();

        return Inertia::render('admins/show', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, User $admin)
    {
        // Ensure target user is an admin
        if (!$admin->isAdmin()) {
            abort(404, 'User is not an admin');
        }

        // Prevent modifying super admin to cost center admin and vice versa
        // unless explicitly changing center_id
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$admin->id}",
            'center_id' => 'nullable|exists:cost_centers,id',
            'employee_id' => 'nullable|exists:employees,id',
            'is_active' => 'boolean',
        ]);

        $admin->update($validated);

        return redirect()->route('admins.index')
            ->with('success', "Admin account updated successfully for {$admin->name}");
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $admin)
    {
        // Ensure target user is an admin
        if (!$admin->isAdmin()) {
            abort(404, 'User is not an admin');
        }

        // Prevent deletion of self
        if ($admin->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Cannot delete your own admin account');
        }

        $adminName = $admin->name;
        $admin->delete();

        return redirect()->route('admins.index')
            ->with('success', "Admin account deleted successfully for {$adminName}");
    }

    /**
     * Reset admin password.
     */
    public function resetPassword(Request $request, User $admin)
    {
        // Ensure target user is an admin
        if (!$admin->isAdmin()) {
            abort(404, 'User is not an admin');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()
            ->with('success', "Password reset successfully for {$admin->name}");
    }
}
