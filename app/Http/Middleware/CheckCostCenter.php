<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCostCenter
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that users can only access data within their assigned cost center.
     * Super admins (center_id = null) can access all centers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // If no user is authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Super admin can access all centers
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if the request contains a center_id parameter (route or query)
        $requestedCenterId = $request->route('center_id') ?? $request->input('center_id');

        // If a specific center is requested, verify access
        if ($requestedCenterId && !$user->canAccessCenter($requestedCenterId)) {
            abort(403, 'You do not have permission to access this cost center.');
        }

        // Store the current center in session for convenience
        session(['current_center_id' => $user->center_id]);

        return $next($request);
    }
}
