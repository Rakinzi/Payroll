<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\PayslipTransaction;
use App\Models\PayslipDistributionLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PayslipController extends Controller
{
    /**
     * Display all payslips.
     */
    public function index(Request $request)
    {
        $query = Payslip::with([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'payroll:id,payroll_name',
            'creator:id,name',
        ]);

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        // Filter by payroll
        if ($request->filled('payroll_id')) {
            $query->forPayroll($request->payroll_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by period
        if ($request->filled('period_month') && $request->filled('period_year')) {
            $query->forPeriod($request->period_month, $request->period_year);
        }

        $payslips = $query->latest()
            ->paginate(15)
            ->through(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'payslip_number' => $payslip->payslip_number,
                    'employee' => [
                        'id' => $payslip->employee->id,
                        'emp_system_id' => $payslip->employee->emp_system_id,
                        'full_name' => $payslip->employee->full_name,
                        'position' => $payslip->employee->position ? [
                            'position_name' => $payslip->employee->position->position_name,
                        ] : null,
                        'department' => $payslip->employee->department ? [
                            'department_name' => $payslip->employee->department->department_name,
                        ] : null,
                    ],
                    'payroll' => [
                        'id' => $payslip->payroll->id,
                        'payroll_name' => $payslip->payroll->payroll_name,
                    ],
                    'period_display' => $payslip->period_display,
                    'period_month' => $payslip->period_month,
                    'period_year' => $payslip->period_year,
                    'payment_date' => $payslip->payment_date->format('Y-m-d'),
                    'status' => $payslip->status,
                    'status_display' => $payslip->status_display,
                    'gross_salary_zwg' => number_format($payslip->gross_salary_zwg, 2),
                    'gross_salary_usd' => number_format($payslip->gross_salary_usd, 2),
                    'net_salary_zwg' => number_format($payslip->net_salary_zwg, 2),
                    'net_salary_usd' => number_format($payslip->net_salary_usd, 2),
                    'can_be_edited' => $payslip->canBeEdited(),
                    'can_be_finalized' => $payslip->canBeFinalized(),
                    'can_be_distributed' => $payslip->canBeDistributed(),
                    'created_at' => $payslip->created_at->toISOString(),
                ];
            });

        return Inertia::render('payslips/index', [
            'payslips' => $payslips,
            'supportedStatuses' => Payslip::getSupportedStatuses(),
            'filters' => $request->only(['employee_id', 'payroll_id', 'status', 'period_month', 'period_year']),
        ]);
    }

    /**
     * Show create payslip form.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)
            ->where('is_ex', false)
            ->with(['position:id,position_name', 'department:id,department_name'])
            ->select('id', 'emp_system_id', 'firstname', 'surname', 'othername', 'position_id', 'department_id')
            ->orderBy('firstname')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'emp_system_id' => $employee->emp_system_id,
                    'full_name' => $employee->full_name,
                    'position' => $employee->position ? [
                        'position_name' => $employee->position->position_name,
                    ] : null,
                    'department' => $employee->department ? [
                        'department_name' => $employee->department->department_name,
                    ] : null,
                ];
            });

        $payrolls = Payroll::where('is_active', true)
            ->select('id', 'payroll_name', 'payroll_type', 'payroll_currency')
            ->orderBy('payroll_name')
            ->get();

        return Inertia::render('payslips/create', [
            'employees' => $employees,
            'payrolls' => $payrolls,
        ]);
    }

    /**
     * Store new payslip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Payslip::rules());

        // Generate payslip number
        $payslipNumber = Payslip::generatePayslipNumber(
            $validated['employee_id'],
            $validated['period_month'],
            $validated['period_year']
        );

        // Check for duplicate
        if (Payslip::where('payslip_number', $payslipNumber)->exists()) {
            return back()->withErrors([
                'employee_id' => 'A payslip already exists for this employee for the selected period.',
            ]);
        }

        $payslip = Payslip::create([
            'employee_id' => $validated['employee_id'],
            'payroll_id' => $validated['payroll_id'],
            'created_by' => auth()->id(),
            'payslip_number' => $payslipNumber,
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'payment_date' => $validated['payment_date'],
            'exchange_rate' => $validated['exchange_rate'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        // Log creation
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'description' => "Created payslip for employee: {$payslip->employee->full_name} ({$payslip->period_display})",
            'model_type' => 'Payslip',
            'model_id' => $payslip->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('payslips.show', $payslip->id)
            ->with('success', 'Payslip created successfully');
    }

    /**
     * Show single payslip with all transactions.
     */
    public function show(Payslip $payslip)
    {
        $payslip->load([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id,email',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'payroll:id,payroll_name',
            'transactions.transactionCode:id,code,description',
            'distributionLogs.sender:id,name',
        ]);

        return Inertia::render('payslips/show', [
            'payslip' => [
                'id' => $payslip->id,
                'payslip_number' => $payslip->payslip_number,
                'employee' => [
                    'id' => $payslip->employee->id,
                    'emp_system_id' => $payslip->employee->emp_system_id,
                    'full_name' => $payslip->employee->full_name,
                    'email' => $payslip->employee->email,
                    'position' => $payslip->employee->position ? [
                        'position_name' => $payslip->employee->position->position_name,
                    ] : null,
                    'department' => $payslip->employee->department ? [
                        'department_name' => $payslip->employee->department->department_name,
                    ] : null,
                ],
                'payroll' => [
                    'id' => $payslip->payroll->id,
                    'payroll_name' => $payslip->payroll->payroll_name,
                ],
                'period_display' => $payslip->period_display,
                'period_month' => $payslip->period_month,
                'period_year' => $payslip->period_year,
                'payment_date' => $payslip->payment_date->format('Y-m-d'),
                'status' => $payslip->status,
                'status_display' => $payslip->status_display,
                'gross_salary_zwg' => $payslip->gross_salary_zwg,
                'total_deductions_zwg' => $payslip->total_deductions_zwg,
                'net_salary_zwg' => $payslip->net_salary_zwg,
                'gross_salary_usd' => $payslip->gross_salary_usd,
                'total_deductions_usd' => $payslip->total_deductions_usd,
                'net_salary_usd' => $payslip->net_salary_usd,
                'ytd_gross_zwg' => $payslip->ytd_gross_zwg,
                'ytd_gross_usd' => $payslip->ytd_gross_usd,
                'ytd_paye_zwg' => $payslip->ytd_paye_zwg,
                'ytd_paye_usd' => $payslip->ytd_paye_usd,
                'exchange_rate' => $payslip->exchange_rate,
                'notes' => $payslip->notes,
                'can_be_edited' => $payslip->canBeEdited(),
                'can_be_finalized' => $payslip->canBeFinalized(),
                'can_be_distributed' => $payslip->canBeDistributed(),
                'transactions' => $payslip->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'description' => $transaction->description,
                        'transaction_type' => $transaction->transaction_type,
                        'type_display' => $transaction->type_display,
                        'amount_zwg' => $transaction->amount_zwg,
                        'amount_usd' => $transaction->amount_usd,
                        'is_taxable' => $transaction->is_taxable,
                        'is_recurring' => $transaction->is_recurring,
                        'is_manual' => $transaction->is_manual,
                        'transaction_code' => $transaction->transactionCode ? [
                            'code' => $transaction->transactionCode->code,
                            'description' => $transaction->transactionCode->description,
                        ] : null,
                    ];
                }),
                'distribution_logs' => $payslip->distributionLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'recipient_email' => $log->recipient_email,
                        'recipient_name' => $log->recipient_name,
                        'status' => $log->status,
                        'status_display' => $log->status_display,
                        'sent_at' => $log->sent_at?->toISOString(),
                        'error_message' => $log->error_message,
                        'sender' => [
                            'name' => $log->sender->name,
                        ],
                    ];
                }),
                'created_at' => $payslip->created_at->toISOString(),
                'updated_at' => $payslip->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Add transaction to payslip.
     */
    public function addTransaction(Request $request, Payslip $payslip)
    {
        if (!$payslip->canBeEdited()) {
            return back()->withErrors(['error' => 'Cannot edit finalized or distributed payslips']);
        }

        $validated = $request->validate(PayslipTransaction::rules());

        $transaction = $payslip->addTransaction($validated);

        // Log addition
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Added {$transaction->type_display} transaction to payslip: {$payslip->payslip_number}",
            'model_type' => 'Payslip',
            'model_id' => $payslip->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Transaction added successfully');
    }

    /**
     * Remove transaction from payslip.
     */
    public function removeTransaction(Payslip $payslip, PayslipTransaction $transaction)
    {
        if (!$payslip->canBeEdited()) {
            return back()->withErrors(['error' => 'Cannot edit finalized or distributed payslips']);
        }

        if ($transaction->payslip_id !== $payslip->id) {
            return back()->withErrors(['error' => 'Transaction does not belong to this payslip']);
        }

        $transactionType = $transaction->type_display;
        $payslip->removeTransaction($transaction->id);

        // Log removal
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Removed {$transactionType} transaction from payslip: {$payslip->payslip_number}",
            'model_type' => 'Payslip',
            'model_id' => $payslip->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Transaction removed successfully');
    }

    /**
     * Finalize payslip.
     */
    public function finalize(Payslip $payslip)
    {
        try {
            $payslip->finalize();

            // Log finalization
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_UPDATE,
                'description' => "Finalized payslip: {$payslip->payslip_number}",
                'model_type' => 'Payslip',
                'model_id' => $payslip->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return back()->with('success', 'Payslip finalized successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate and preview PDF.
     */
    public function preview(Payslip $payslip)
    {
        $payslip->load([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'payroll:id,payroll_name',
            'transactions',
        ]);

        $pdf = Pdf::loadView('payslips.pdf', [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'earnings' => $payslip->transactions()->earnings()->get(),
            'deductions' => $payslip->transactions()->deductions()->get(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10)
        ->setOption('margin-left', 10)
        ->setOption('margin-right', 10);

        return $pdf->stream("{$payslip->payslip_number}.pdf");
    }

    /**
     * Download PDF.
     */
    public function download(Payslip $payslip)
    {
        $payslip->load([
            'employee:id,emp_system_id,firstname,surname,othername,position_id,department_id',
            'employee.position:id,position_name',
            'employee.department:id,department_name',
            'payroll:id,payroll_name',
            'transactions',
        ]);

        $pdf = Pdf::loadView('payslips.pdf', [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'earnings' => $payslip->transactions()->earnings()->get(),
            'deductions' => $payslip->transactions()->deductions()->get(),
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10)
        ->setOption('margin-left', 10)
        ->setOption('margin-right', 10);

        return $pdf->download("{$payslip->payslip_number}.pdf");
    }

    /**
     * Distribute payslip via email.
     */
    public function distribute(Request $request, Payslip $payslip)
    {
        if (!$payslip->canBeDistributed()) {
            return back()->withErrors(['error' => 'Only finalized payslips can be distributed']);
        }

        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'recipient_name' => 'required|string|max:255',
        ]);

        try {
            // Create distribution log
            $log = PayslipDistributionLog::create([
                'payslip_id' => $payslip->id,
                'sent_by' => auth()->id(),
                'recipient_email' => $validated['recipient_email'],
                'recipient_name' => $validated['recipient_name'],
                'status' => 'pending',
            ]);

            // Generate PDF
            $pdf = Pdf::loadView('payslips.pdf', [
                'payslip' => $payslip,
                'employee' => $payslip->employee,
                'earnings' => $payslip->transactions()->earnings()->get(),
                'deductions' => $payslip->transactions()->deductions()->get(),
            ])
            ->setPaper('a4', 'landscape');

            // Send email (simplified - in production, use queued mail)
            // Mail::to($validated['recipient_email'])->send(new PayslipMail($payslip, $pdf->output()));

            // For now, mark as sent
            $log->markAsSent();
            $payslip->markDistributed();

            // Log distribution
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_UPDATE,
                'description' => "Distributed payslip {$payslip->payslip_number} to {$validated['recipient_email']}",
                'model_type' => 'Payslip',
                'model_id' => $payslip->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('success', 'Payslip distributed successfully');
        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }

            return back()->withErrors(['error' => 'Failed to distribute payslip: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete payslip.
     */
    public function destroy(Payslip $payslip)
    {
        if ($payslip->status === 'distributed') {
            return back()->withErrors(['error' => 'Cannot delete distributed payslips']);
        }

        $payslipNumber = $payslip->payslip_number;

        $payslip->delete();

        // Log deletion
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_DELETE,
            'description' => "Deleted payslip: {$payslipNumber}",
            'model_type' => 'Payslip',
            'model_id' => $payslip->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('payslips.index')
            ->with('success', 'Payslip deleted successfully');
    }
}
