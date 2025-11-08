<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckCostCenter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider and all
| of them will have the tenancy middleware applied automatically.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Welcome/Home page
    Route::get('/', function () {
        return Inertia::render('welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    })->name('home');

    // Authentication Routes (override Fortify default login)
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);
    });

    Route::post('logout', [LoginController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    // Protected Routes
    Route::middleware(['auth', 'verified', CheckCostCenter::class])->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Employee Management Routes (with permission middleware)
        Route::middleware('permission:view employees')->group(function () {
            Route::get('/employees', function () {
                return Inertia::render('employees/index');
            })->name('employees.index');
        });

        Route::middleware('permission:create employees')->group(function () {
            Route::get('/employees/create', function () {
                return Inertia::render('employees/create');
            })->name('employees.create');
        });

        // Payroll Routes (with permission middleware)
        Route::middleware('permission:view payroll')->prefix('payroll')->group(function () {
            Route::get('/', function () {
                return Inertia::render('payroll/index');
            })->name('payroll.index');
        });

        Route::middleware('permission:process payroll')->group(function () {
            Route::get('/payroll/run', function () {
                return Inertia::render('payroll/run');
            })->name('payroll.run');
        });

        // Reports Routes (with permission middleware)
        Route::middleware('permission:view reports')->prefix('reports')->group(function () {
            Route::get('/', function () {
                return Inertia::render('reports/index');
            })->name('reports.index');
        });

        // Super Admin Only Routes
        Route::middleware('permission:access all centers')->prefix('admin')->group(function () {
            Route::get('/cost-centers', function () {
                return Inertia::render('admin/cost-centers/index');
            })->name('admin.cost-centers.index');

            Route::get('/system-settings', function () {
                return Inertia::render('admin/system-settings');
            })->name('admin.system-settings');
        });
    });

    // Settings routes
    require __DIR__.'/settings.php';
});
