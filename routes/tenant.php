<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\OrganizationalDataController;
use App\Http\Controllers\Api\TaxCreditController as ApiTaxCreditController;
use App\Http\Controllers\Api\TransactionCodeController as ApiTransactionCodeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyBankDetailController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CurrencySetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DischargedEmployeesController;
use App\Http\Controllers\EmployeeBankDetailController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SecurityLogController;
use App\Http\Controllers\SpreadsheetImportController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TaxBandController;
use App\Http\Controllers\TaxCreditController;
use App\Http\Controllers\TransactionCodeController;
use App\Http\Controllers\VehicleBenefitBandController;
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

        // Activity Log Routes (Super Admin only)
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            });
        });

        // Security Log Routes (Super Admin only)
        Route::prefix('security-logs')->name('security-logs.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                Route::get('/', [SecurityLogController::class, 'index'])->name('index');
            });
        });

        // Currency Setup Routes (Admin only)
        Route::prefix('currency-setup')->name('currency-setup.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // Main index page
                Route::get('/', [CurrencySetupController::class, 'index'])->name('index');

                // Currency Splits
                Route::post('/splits', [CurrencySetupController::class, 'storeSplit'])->name('splits.store');
                Route::put('/splits/{currencySplit}', [CurrencySetupController::class, 'updateSplit'])->name('splits.update');
                Route::delete('/splits/{currencySplit}', [CurrencySetupController::class, 'destroySplit'])->name('splits.destroy');

                // Exchange Rates
                Route::post('/rates', [CurrencySetupController::class, 'storeRate'])->name('rates.store');
                Route::put('/rates/{exchangeRate}', [CurrencySetupController::class, 'updateRate'])->name('rates.update');
                Route::delete('/rates/{exchangeRate}', [CurrencySetupController::class, 'destroyRate'])->name('rates.destroy');

                // API endpoints for currency conversion
                Route::get('/current-rate', [CurrencySetupController::class, 'getCurrentRate'])->name('current-rate');
                Route::post('/convert', [CurrencySetupController::class, 'convertCurrency'])->name('convert');
            });
        });

        // Company Details Routes (Admin only)
        Route::prefix('companies')->name('companies.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // View company details
                Route::get('/details', [CompanyController::class, 'show'])->name('show');

                // Update company details
                Route::put('/{company}', [CompanyController::class, 'update'])->name('update');

                // Upload and delete logo
                Route::post('/{company}/logo', [CompanyController::class, 'uploadLogo'])->name('upload-logo');
                Route::delete('/{company}/logo', [CompanyController::class, 'deleteLogo'])->name('delete-logo');
            });
        });

        // Vehicle Benefits Bands Routes (Admin only)
        Route::prefix('vehicle-benefits')->name('vehicle-benefits.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // List vehicle benefit bands
                Route::get('/', [VehicleBenefitBandController::class, 'index'])->name('index');

                // Create vehicle benefit band
                Route::post('/', [VehicleBenefitBandController::class, 'store'])->name('store');

                // Update vehicle benefit band
                Route::put('/{vehicleBenefit}', [VehicleBenefitBandController::class, 'update'])->name('update');

                // Delete vehicle benefit band
                Route::delete('/{vehicleBenefit}', [VehicleBenefitBandController::class, 'destroy'])->name('destroy');

                // Calculate benefit for capacity
                Route::post('/calculate', [VehicleBenefitBandController::class, 'calculateBenefit'])->name('calculate');
            });
        });

        // Payroll Management Routes (Admin only)
        Route::prefix('payrolls')->name('payrolls.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // List and view payrolls
                Route::get('/', [PayrollController::class, 'index'])->name('index');
                Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');

                // Create payroll
                Route::post('/', [PayrollController::class, 'store'])->name('store');

                // Update payroll
                Route::put('/{payroll}', [PayrollController::class, 'update'])->name('update');

                // Delete payroll
                Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->name('destroy');

                // Toggle payroll status
                Route::post('/{payroll}/toggle-status', [PayrollController::class, 'toggleStatus'])->name('toggle-status');

                // Employee assignment
                Route::post('/{payroll}/assign-employees', [PayrollController::class, 'assignEmployees'])->name('assign-employees');
                Route::delete('/{payroll}/employees/{employee}', [PayrollController::class, 'removeEmployee'])->name('remove-employee');
            });
        });

        // Accounting Period Management Routes
        Route::prefix('accounting-periods')->name('accounting-periods.')->group(function () {
            // List and view accounting periods (all users can view)
            Route::get('/', [\App\Http\Controllers\AccountingPeriodController::class, 'index'])->name('index');
            Route::get('/{period}', [\App\Http\Controllers\AccountingPeriodController::class, 'show'])->name('show');

            // Period operations (center users and admins)
            Route::post('/{period}/run', [\App\Http\Controllers\AccountingPeriodController::class, 'run'])->name('run');
            Route::post('/{period}/refresh', [\App\Http\Controllers\AccountingPeriodController::class, 'refresh'])->name('refresh');
            Route::post('/{period}/close', [\App\Http\Controllers\AccountingPeriodController::class, 'close'])->name('close');

            // AJAX endpoints
            Route::get('/{period}/status', [\App\Http\Controllers\AccountingPeriodController::class, 'status'])->name('status');
            Route::post('/{period}/currency', [\App\Http\Controllers\AccountingPeriodController::class, 'updateCurrency'])->name('update-currency');
            Route::get('/{period}/summary', [\App\Http\Controllers\AccountingPeriodController::class, 'summary'])->name('summary');

            // Admin-only operations
            Route::middleware('permission:access all centers')->group(function () {
                Route::post('/generate', [\App\Http\Controllers\AccountingPeriodController::class, 'generatePeriods'])->name('generate');
                Route::get('/{period}/export', [\App\Http\Controllers\AccountingPeriodController::class, 'export'])->name('export');
            });
        });

        // Spreadsheet Import/Export Routes (Admin only)
        Route::prefix('spreadsheet-import')->name('spreadsheet-import.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // List import sessions
                Route::get('/', [SpreadsheetImportController::class, 'index'])->name('index');

                // Upload file
                Route::post('/upload', [SpreadsheetImportController::class, 'upload'])->name('upload');

                // Preview import
                Route::get('/{session}/preview', [SpreadsheetImportController::class, 'preview'])->name('preview');

                // Process import
                Route::post('/{session}/process', [SpreadsheetImportController::class, 'process'])->name('process');

                // Get status (for polling)
                Route::get('/{session}/status', [SpreadsheetImportController::class, 'status'])->name('status');

                // Delete session
                Route::delete('/{session}', [SpreadsheetImportController::class, 'destroy'])->name('destroy');

                // Export data
                Route::post('/export', [SpreadsheetImportController::class, 'export'])->name('export');
            });
        });

        // Leave Management Routes
        Route::prefix('leave-applications')->name('leave-applications.')->group(function () {
            // List leave applications
            Route::get('/', [LeaveApplicationController::class, 'index'])->name('index');

            // Create leave application
            Route::get('/create', [LeaveApplicationController::class, 'create'])->name('create');
            Route::post('/', [LeaveApplicationController::class, 'store'])->name('store');

            // View, update, delete leave application
            Route::get('/{leaveApplication}', [LeaveApplicationController::class, 'show'])->name('show');
            Route::put('/{leaveApplication}', [LeaveApplicationController::class, 'update'])->name('update');
            Route::delete('/{leaveApplication}', [LeaveApplicationController::class, 'destroy'])->name('destroy');
        });

        // Payslip Management Routes (Admin only)
        Route::prefix('payslips')->name('payslips.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // List and view payslips
                Route::get('/', [PayslipController::class, 'index'])->name('index');
                Route::get('/create', [PayslipController::class, 'create'])->name('create');
                Route::post('/', [PayslipController::class, 'store'])->name('store');
                Route::get('/{payslip}', [PayslipController::class, 'show'])->name('show');

                // Transaction management
                Route::post('/{payslip}/transactions', [PayslipController::class, 'addTransaction'])->name('transactions.add');
                Route::delete('/{payslip}/transactions/{transaction}', [PayslipController::class, 'removeTransaction'])->name('transactions.remove');

                // Payslip actions
                Route::post('/{payslip}/finalize', [PayslipController::class, 'finalize'])->name('finalize');
                Route::post('/{payslip}/distribute', [PayslipController::class, 'distribute'])->name('distribute');

                // PDF operations
                Route::get('/{payslip}/preview', [PayslipController::class, 'preview'])->name('preview');
                Route::get('/{payslip}/download', [PayslipController::class, 'download'])->name('download');

                // Delete payslip
                Route::delete('/{payslip}', [PayslipController::class, 'destroy'])->name('destroy');
            });
        });

        // Reports Management Routes (Admin only)
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // Main reports dashboard
                Route::get('/', [ReportsController::class, 'index'])->name('index');

                // Cost Analysis
                Route::post('/cost-analysis/generate', [ReportsController::class, 'generateCostAnalysis'])->name('cost-analysis.generate');
                Route::get('/cost-analysis/{report}/download', [ReportsController::class, 'downloadCostAnalysis'])->name('cost-analysis.download');

                // ITF Forms
                Route::post('/itf-forms/generate', [ReportsController::class, 'generateItfForm'])->name('itf-forms.generate');
                Route::get('/itf-forms/{form}/download', [ReportsController::class, 'downloadItfForm'])->name('itf-forms.download');

                // Variance Analysis
                Route::post('/variance-analysis/generate', [ReportsController::class, 'generateVarianceAnalysis'])->name('variance-analysis.generate');
                Route::get('/variance-analysis/{analysis}/download', [ReportsController::class, 'downloadVarianceAnalysis'])->name('variance-analysis.download');

                // Third-Party Reports
                Route::post('/third-party/generate', [ReportsController::class, 'generateThirdPartyReport'])->name('third-party.generate');
                Route::get('/third-party/{report}/download', [ReportsController::class, 'downloadThirdPartyReport'])->name('third-party.download');
                Route::post('/third-party/{report}/submit', [ReportsController::class, 'submitThirdPartyReport'])->name('third-party.submit');

                // Scheduled Reports
                Route::post('/scheduled/create', [ReportsController::class, 'createScheduledReport'])->name('scheduled.create');
                Route::delete('/scheduled/{schedule}', [ReportsController::class, 'deleteScheduledReport'])->name('scheduled.delete');

                // Taxable Accumulatives
                Route::post('/taxable-accumulatives/generate', [ReportsController::class, 'generateTaxableAccumulatives'])->name('taxable-accumulatives.generate');
                Route::get('/taxable-accumulatives/{accumulative}/download', [ReportsController::class, 'downloadTaxableAccumulatives'])->name('taxable-accumulatives.download');

                // Tax Cell Accumulatives
                Route::post('/tax-cell-accumulatives/generate', [ReportsController::class, 'generateTaxCellAccumulatives'])->name('tax-cell-accumulatives.generate');
                Route::get('/tax-cell-accumulatives/{cellAccumulative}/download', [ReportsController::class, 'downloadTaxCellAccumulatives'])->name('tax-cell-accumulatives.download');

                // Retirement Warning
                Route::post('/retirement-warning/generate', [ReportsController::class, 'generateRetirementWarning'])->name('retirement-warning.generate');
                Route::get('/retirement-warning/{warning}/download', [ReportsController::class, 'downloadRetirementWarning'])->name('retirement-warning.download');

                // Employee Requisition
                Route::post('/employee-requisition/generate', [ReportsController::class, 'generateEmployeeRequisition'])->name('employee-requisition.generate');
                Route::get('/employee-requisition/{requisition}/download', [ReportsController::class, 'downloadEmployeeRequisition'])->name('employee-requisition.download');
            });
        });

        // Payroll Processing Routes (with permission middleware)
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

        // Leave Management Routes
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::middleware('permission:access all centers')->group(function () {
                // Leave Applications
                Route::get('/applications', [LeaveApplicationController::class, 'index'])->name('applications.index');
                Route::get('/applications/create', [LeaveApplicationController::class, 'create'])->name('applications.create');
                Route::post('/applications', [LeaveApplicationController::class, 'store'])->name('applications.store');
                Route::get('/applications/{leave}/edit', [LeaveApplicationController::class, 'edit'])->name('applications.edit');
                Route::put('/applications/{leave}', [LeaveApplicationController::class, 'update'])->name('applications.update');
                Route::delete('/applications/{leave}', [LeaveApplicationController::class, 'destroy'])->name('applications.destroy');
                Route::post('/applications/{leave}/approve', [LeaveApplicationController::class, 'approve'])->name('applications.approve');
                Route::post('/applications/{leave}/reject', [LeaveApplicationController::class, 'reject'])->name('applications.reject');

                // Leave Balances
                Route::get('/balances', [LeaveBalanceController::class, 'index'])->name('balances.index');
                Route::put('/balances/{balance}', [LeaveBalanceController::class, 'update'])->name('balances.update');

                // Leave Reports
                Route::get('/reports', [LeaveReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/period-summary', [LeaveReportController::class, 'periodSummary'])->name('reports.period-summary');
                Route::get('/reports/balances', [LeaveReportController::class, 'balances'])->name('reports.balances');
                Route::get('/reports/warnings', [LeaveReportController::class, 'warnings'])->name('reports.warnings');
                Route::get('/reports/annual-statement', [LeaveReportController::class, 'annualStatement'])->name('reports.annual-statement');
            });
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
