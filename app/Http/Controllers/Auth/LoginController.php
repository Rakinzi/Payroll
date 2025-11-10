<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Display the login page.
     */
    public function showLoginForm()
    {
        $costCenters = CostCenter::active()->get(['id', 'center_name']);

        return Inertia::render('auth/login', [
            'costCenters' => $costCenters,
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'center_id' => ['nullable', 'uuid', 'exists:cost_centers,id'],
        ]);

        // Determine which center to search in (handle empty string as null)
        $centerId = $request->center_id ?: null;

        // Find user
        $user = User::where('email', $request->email)
            ->active()
            ->when($centerId, function ($query, $centerId) {
                return $query->where('center_id', $centerId);
            })
            ->when(!$centerId, function ($query) {
                // If no center selected, look for super admin
                return $query->whereNull('center_id');
            })
            ->with(['employee', 'costCenter'])
            ->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact the administrator.'],
            ]);
        }

        // Update login information
        $user->updateLoginInfo($request->ip());

        // Log the user in
        Auth::login($user, $request->filled('remember'));

        // Regenerate session
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
