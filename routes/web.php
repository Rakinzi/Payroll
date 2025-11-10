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

// Central landing page - only accessible when no tenant is found
// This route has a lower priority than tenant routes
Route::get('/', function () {
    return response()->json([
        'message' => 'Lorimak Payroll System',
        'version' => '2.0',
        'note' => 'Please access via your tenant domain.',
        'example' => 'http://local.localhost:8000',
        'tenants' => \App\Models\Tenant::with('domains')->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->system_name,
                'domains' => $tenant->domains->pluck('domain'),
            ];
        }),
    ]);
})->withoutMiddleware([\Spatie\Multitenancy\Http\Middleware\NeedsTenant::class]);
