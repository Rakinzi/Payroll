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

Route::get('/', function () {
    return response()->json([
        'message' => 'Lorimak Payroll System',
        'version' => '2.0',
        'note' => 'Please access via your tenant domain (e.g., nhaka.lorimakpayport.com)',
    ]);
});
