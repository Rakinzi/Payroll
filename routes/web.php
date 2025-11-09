<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central App Routes
|--------------------------------------------------------------------------
|
| Here you can register routes for the central application.
| These routes are NOT tenant-specific and are accessed from central domains.
| All tenant-specific routes should be in routes/tenant.php
|
*/

// Central app routes (if any) go here
// For example: tenant management, billing, etc.

// Only show this message on actual central domains (not tenant domains)
Route::get('/', function () {
    // Check if we're on a central domain
    $centralDomains = config('tenancy.central_domains');
    $currentDomain = request()->getHost();

    if (in_array($currentDomain, $centralDomains)) {
        return response()->json([
            'message' => 'Lorimak Payroll System',
            'version' => '2.0',
            'note' => 'Please access via your tenant domain (e.g., nhaka.lorimakpayport.com, local.localhost)',
        ]);
    }

    // If not a central domain, let it fall through to tenant routes
    abort(404);
});
