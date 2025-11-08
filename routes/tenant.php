<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\OrganizationalDataController;
use App\Http\Controllers\Api\TaxCreditController as ApiTaxCreditController;
use App\Http\Controllers\Api\TransactionCodeController as ApiTransactionCodeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyBankDetailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DischargedEmployeesController;
use App\Http\Controllers\EmployeeBankDetailController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TaxBandController;
use App\Http\Controllers\TaxCreditController;
use App\Http\Controllers\TransactionCodeController;
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

        // API Routes
        Route::prefix('api')->group(function () {
            Route::get('/organizational-data', [OrganizationalDataController::class, 'index']);

            // Company CRUD
            Route::post('/companies', [OrganizationalDataController::class, 'storeCompany']);
            Route::put('/companies/{id}', [OrganizationalDataController::class, 'updateCompany']);
            Route::delete('/companies/{id}', [OrganizationalDataController::class, 'destroyCompany']);

            // Tax Credit CRUD
            Route::post('/tax-credits', [OrganizationalDataController::class, 'storeTaxCredit']);
            Route::put('/tax-credits/{id}', [OrganizationalDataController::class, 'updateTaxCredit']);
            Route::delete('/tax-credits/{id}', [OrganizationalDataController::class, 'destroyTaxCredit']);

            // Vehicle Benefit Band CRUD
            Route::post('/vehicle-benefit-bands', [OrganizationalDataController::class, 'storeVehicleBenefitBand']);
            Route::put('/vehicle-benefit-bands/{id}', [OrganizationalDataController::class, 'updateVehicleBenefitBand']);
            Route::delete('/vehicle-benefit-bands/{id}', [OrganizationalDataController::class, 'destroyVehicleBenefitBand']);

            // Company Bank Detail CRUD
            Route::post('/company-bank-details', [OrganizationalDataController::class, 'storeCompanyBankDetail']);
            Route::put('/company-bank-details/{id}', [OrganizationalDataController::class, 'updateCompanyBankDetail']);
            Route::delete('/company-bank-details/{id}', [OrganizationalDataController::class, 'destroyCompanyBankDetail']);

            // Transaction Codes API (admin only)
            Route::middleware('permission:access all centers')->prefix('transaction-codes')->group(function () {
                Route::get('/', [ApiTransactionCodeController::class, 'index']);
                Route::post('/', [ApiTransactionCodeController::class, 'store']);
                Route::get('/{transactionCode}', [ApiTransactionCodeController::class, 'show']);
                Route::put('/{transactionCode}', [ApiTransactionCodeController::class, 'update']);
                Route::delete('/{transactionCode}', [ApiTransactionCodeController::class, 'destroy']);
            });

            // Tax Credits API (admin only)
            Route::middleware('permission:access all centers')->prefix('tax-credits')->group(function () {
                Route::get('/', [ApiTaxCreditController::class, 'index']);
                Route::post('/', [ApiTaxCreditController::class, 'store']);
                Route::get('/{taxCredit}', [ApiTaxCreditController::class, 'show']);
                Route::put('/{taxCredit}', [ApiTaxCreditController::class, 'update']);
                Route::delete('/{taxCredit}', [ApiTaxCreditController::class, 'destroy']);
            });
        });

        // Employee Management Routes
        Route::prefix('employees')->name('employees.')->group(function () {
            // List and view employees
            Route::middleware('permission:view employees')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->name('index');
                Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
            });

            // Create employees
            Route::middleware('permission:create employees')->group(function () {
                Route::get('/create', [EmployeeController::class, 'create'])->name('create');
                Route::post('/', [EmployeeController::class, 'store'])->name('store');
            });

            // Edit employees
            Route::middleware('permission:edit employees')->group(function () {
                Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
                Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
            });

            // Delete employees
            Route::middleware('permission:delete employees')->group(function () {
                Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
            });

            // Terminate and restore employees
            Route::middleware('permission:edit employees')->group(function () {
                Route::post('/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('terminate');
                Route::post('/{employee}/restore', [EmployeeController::class, 'restore'])->name('restore');
            });

            // Banking details routes
            Route::middleware('permission:view employees')->prefix('{employee}/bank-details')->name('bank-details.')->group(function () {
                Route::get('/', [EmployeeBankDetailController::class, 'index'])->name('index');
                Route::post('/', [EmployeeBankDetailController::class, 'store'])->name('store');
                Route::put('/{bankDetail}', [EmployeeBankDetailController::class, 'update'])->name('update');
                Route::delete('/{bankDetail}', [EmployeeBankDetailController::class, 'destroy'])->name('destroy');
            });
        });

        // Transaction Code Management Routes
        Route::prefix('transaction-codes')->name('transaction-codes.')->group(function () {
            // List and view transaction codes
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [TransactionCodeController::class, 'index'])->name('index');
                Route::get('/{transactionCode}', [TransactionCodeController::class, 'show'])->name('show');
            });

            // Create transaction codes (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/create', [TransactionCodeController::class, 'create'])->name('create');
                Route::post('/', [TransactionCodeController::class, 'store'])->name('store');
            });

            // Edit transaction codes (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/{transactionCode}/edit', [TransactionCodeController::class, 'edit'])->name('edit');
                Route::put('/{transactionCode}', [TransactionCodeController::class, 'update'])->name('update');
            });

            // Delete transaction codes (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::delete('/{transactionCode}', [TransactionCodeController::class, 'destroy'])->name('destroy');
            });
        });

        // Tax Credit Management Routes
        Route::prefix('tax-credits')->name('tax-credits.')->group(function () {
            // List and view tax credits (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [TaxCreditController::class, 'index'])->name('index');
                Route::get('/{taxCredit}', [TaxCreditController::class, 'show'])->name('show');
            });

            // Create tax credits (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/create', [TaxCreditController::class, 'create'])->name('create');
                Route::post('/', [TaxCreditController::class, 'store'])->name('store');
            });

            // Edit tax credits (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/{taxCredit}/edit', [TaxCreditController::class, 'edit'])->name('edit');
                Route::put('/{taxCredit}', [TaxCreditController::class, 'update'])->name('update');
            });

            // Delete tax credits (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::delete('/{taxCredit}', [TaxCreditController::class, 'destroy'])->name('destroy');
            });
        });

        // Tax Bands Management Routes
        Route::prefix('tax-bands')->name('tax-bands.')->group(function () {
            // List all tax bands (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [TaxBandController::class, 'index'])->name('index');
            });

            // Create tax band (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::post('/{bandType}', [TaxBandController::class, 'store'])->name('store');
            });

            // Update tax band (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::put('/{bandType}/{id}', [TaxBandController::class, 'update'])->name('update');
            });

            // Delete tax band (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::delete('/{bandType}/{id}', [TaxBandController::class, 'destroy'])->name('destroy');
            });
        });

        // Discharged Employees Management Routes
        Route::prefix('discharged-employees')->name('discharged-employees.')->group(function () {
            // List discharged employees
            Route::middleware('permission:view employees')->group(function () {
                Route::get('/', [DischargedEmployeesController::class, 'index'])->name('index');
                Route::get('/{dischargedEmployee}', [DischargedEmployeesController::class, 'show'])->name('show');
            });

            // Reinstate employee
            Route::middleware('permission:edit employees')->group(function () {
                Route::post('/{dischargedEmployee}/reinstate', [DischargedEmployeesController::class, 'reinstate'])->name('reinstate');
            });

            // Permanently delete (admin only)
            Route::middleware('permission:delete employees')->group(function () {
                Route::delete('/{dischargedEmployee}', [DischargedEmployeesController::class, 'destroy'])->name('destroy');
            });
        });

        // Admin Management Routes (Super Admin only)
        Route::prefix('admins')->name('admins.')->group(function () {
            // List admins
            Route::get('/', [AdminController::class, 'index'])->name('index');

            // View admin details
            Route::get('/{admin}', [AdminController::class, 'show'])->name('show');

            // Create admin
            Route::post('/', [AdminController::class, 'store'])->name('store');

            // Update admin
            Route::put('/{admin}', [AdminController::class, 'update'])->name('update');

            // Delete admin
            Route::delete('/{admin}', [AdminController::class, 'destroy'])->name('destroy');

            // Reset admin password
            Route::post('/{admin}/reset-password', [AdminController::class, 'resetPassword'])->name('reset-password');
        });

        // Company Bank Details Management Routes
        Route::prefix('company-bank-details')->name('company-bank-details.')->group(function () {
            // List bank details (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [CompanyBankDetailController::class, 'index'])->name('index');
            });

            // Create bank detail (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::post('/', [CompanyBankDetailController::class, 'store'])->name('store');
            });

            // Update bank detail (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::put('/{companyBankDetail}', [CompanyBankDetailController::class, 'update'])->name('update');
            });

            // Delete bank detail (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::delete('/{companyBankDetail}', [CompanyBankDetailController::class, 'destroy'])->name('destroy');
            });

            // Set default bank account (admin only)
            Route::middleware('permission:access all centers')->group(function () {
                Route::post('/{companyBankDetail}/set-default', [CompanyBankDetailController::class, 'setDefault'])->name('set-default');
            });
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

        // Organizational Data Routes (admin only)
        Route::middleware('permission:access all centers')->group(function () {
            Route::get('/organizational-data', function () {
                return Inertia::render('organizational-data');
            })->name('organizational-data');
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
