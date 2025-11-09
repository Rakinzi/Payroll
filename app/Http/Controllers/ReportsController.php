<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CostAnalysisCache;
use App\Models\EmployeeRequisition;
use App\Models\ItfForm;
use App\Models\Payroll;
use App\Models\RetirementWarning;
use App\Models\ScheduledReport;
use App\Models\TaxableAccumulative;
use App\Models\TaxCellAccumulative;
use App\Models\ThirdPartyReport;
use App\Models\VarianceAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    /**
     * Display main reports dashboard.
     */
    public function index(Request $request)
    {
        $payrolls = Payroll::where('is_active', true)
            ->select('id', 'payroll_name', 'payroll_type', 'payroll_currency')
            ->orderBy('payroll_name')
            ->get();

        $recentReports = collect()
            ->merge(CostAnalysisCache::with('payroll:id,payroll_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'type' => 'cost_analysis',
                    'name' => $r->report_type_display,
                    'payroll' => $r->payroll->payroll_name,
                    'generated_at' => $r->generated_at->toISOString(),
                ]))
            ->merge(ItfForm::with('payroll:id,payroll_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'type' => 'itf_form',
                    'name' => $r->form_display,
                    'payroll' => $r->payroll->payroll_name,
                    'generated_at' => $r->generated_at->toISOString(),
                ]))
            ->sortByDesc('generated_at')
            ->take(10)
            ->values();

        $scheduledReports = ScheduledReport::with(['payroll:id,payroll_name', 'user:id,name'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('is_global', true);
            })
            ->where('is_active', true)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'report_type' => $schedule->report_type,
                    'payroll' => [
                        'id' => $schedule->payroll->id,
                        'payroll_name' => $schedule->payroll->payroll_name,
                    ],
                    'frequency' => $schedule->frequency,
                    'frequency_display' => $schedule->frequency_display,
                    'next_run_at' => $schedule->next_run_at?->toISOString(),
                    'is_global' => $schedule->is_global,
                ];
            });

        return Inertia::render('reports/index', [
            'payrolls' => $payrolls,
            'recentReports' => $recentReports,
            'scheduledReports' => $scheduledReports,
            'reportTypes' => [
                'cost_analysis' => 'Cost Analysis Reports',
                'compliance' => 'Compliance Reports',
                'variance' => 'Variance Analysis',
                'third_party' => 'Third Party Reports',
            ],
        ]);
    }

    /**
     * Generate cost analysis report.
     */
    public function generateCostAnalysis(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'report_type' => 'required|in:department,designation,codes,leave',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'currency' => 'required|in:ZWG,USD',
        ]);

        try {
            // For now, create a basic report structure
            // In production, this would query actual payroll data
            $report = CostAnalysisCache::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'report_type' => $validated['report_type'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'currency' => $validated['currency'],
                'total_costs' => 0, // Would be calculated from actual data
                'generated_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated cost analysis report: {$report->report_type_display}",
                'model_type' => 'CostAnalysisCache',
                'model_id' => $report->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'report_id' => $report->id,
                'download_url' => route('reports.cost-analysis.download', $report->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download cost analysis report as PDF.
     */
    public function downloadCostAnalysis(CostAnalysisCache $report)
    {
        if (!$report->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.cost-analysis', [
            'report' => $report,
            'payroll' => $report->payroll,
            'breakdownDetails' => $report->breakdownDetails,
        ])
        ->setPaper('a4', 'portrait');

        return $pdf->download("cost_analysis_{$report->id}.pdf");
    }

    /**
     * Generate ITF form.
     */
    public function generateItfForm(Request $request)
    {
        $validated = $request->validate(ItfForm::rules());

        try {
            $form = ItfForm::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'form_type' => $validated['form_type'],
                'tax_year' => $validated['tax_year'],
                'currency' => $validated['currency'],
                'total_gross_income' => 0,
                'total_taxable_income' => 0,
                'total_tax_deducted' => 0,
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated ITF form: {$form->form_display}",
                'model_type' => 'ItfForm',
                'model_id' => $form->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'form_id' => $form->id,
                'download_url' => route('reports.itf-forms.download', $form->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download ITF form as PDF.
     */
    public function downloadItfForm(ItfForm $form)
    {
        if (!$form->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.itf-form', [
            'form' => $form,
            'payroll' => $form->payroll,
            'details' => $form->details,
        ])
        ->setPaper('a4', 'portrait');

        return $pdf->download("itf_{$form->form_type}_{$form->tax_year}.pdf");
    }

    /**
     * Generate variance analysis.
     */
    public function generateVarianceAnalysis(Request $request)
    {
        $validated = $request->validate(VarianceAnalysis::rules());

        try {
            $analysis = VarianceAnalysis::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'analysis_type' => $validated['analysis_type'],
                'baseline_period' => $validated['baseline_period'],
                'comparison_period' => $validated['comparison_period'],
                'total_variance_zwg' => 0,
                'total_variance_usd' => 0,
                'variance_percentage' => 0,
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated variance analysis: {$analysis->analysis_display}",
                'model_type' => 'VarianceAnalysis',
                'model_id' => $analysis->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'analysis_id' => $analysis->id,
                'download_url' => route('reports.variance-analysis.download', $analysis->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download variance analysis as PDF.
     */
    public function downloadVarianceAnalysis(VarianceAnalysis $analysis)
    {
        if (!$analysis->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.variance-analysis', [
            'analysis' => $analysis,
            'payroll' => $analysis->payroll,
            'details' => $analysis->details,
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download("variance_analysis_{$analysis->id}.pdf");
    }

    /**
     * Generate third-party report.
     */
    public function generateThirdPartyReport(Request $request)
    {
        $validated = $request->validate(ThirdPartyReport::rules());

        try {
            $report = ThirdPartyReport::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'report_type' => $validated['report_type'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'currency' => $validated['currency'],
                'total_amount' => 0,
                'submission_status' => 'draft',
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated third-party report: {$report->report_type_display}",
                'model_type' => 'ThirdPartyReport',
                'model_id' => $report->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'report_id' => $report->id,
                'download_url' => route('reports.third-party.download', $report->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download third-party report as PDF.
     */
    public function downloadThirdPartyReport(ThirdPartyReport $report)
    {
        if (!$report->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.third-party', [
            'report' => $report,
            'payroll' => $report->payroll,
            'details' => $report->details,
        ])
        ->setPaper('a4', 'portrait');

        return $pdf->download("{$report->report_type}_{$report->id}.pdf");
    }

    /**
     * Submit third-party report.
     */
    public function submitThirdPartyReport(Request $request, ThirdPartyReport $report)
    {
        if (!$report->can_submit) {
            return back()->withErrors(['error' => 'Report cannot be submitted']);
        }

        $reference = 'REF-' . strtoupper(uniqid());
        $report->markAsSubmitted($reference);

        // Log submission
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_UPDATE,
            'description' => "Submitted third-party report: {$report->report_type_display} (Ref: {$reference})",
            'model_type' => 'ThirdPartyReport',
            'model_id' => $report->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Report submitted successfully. Reference: {$reference}");
    }

    /**
     * Create scheduled report.
     */
    public function createScheduledReport(Request $request)
    {
        $validated = $request->validate(ScheduledReport::rules());

        $schedule = ScheduledReport::create([
            'user_id' => auth()->id(),
            'payroll_id' => $validated['payroll_id'],
            'report_type' => $validated['report_type'],
            'parameters' => $validated['parameters'] ?? [],
            'frequency' => $validated['frequency'],
            'email_recipients' => $validated['email_recipients'] ?? null,
            'is_active' => true,
            'is_global' => false,
            'next_run_at' => now()->addDay(), // Set initial run
        ]);

        return back()->with('success', 'Scheduled report created successfully');
    }

    /**
     * Delete scheduled report.
     */
    public function deleteScheduledReport(ScheduledReport $schedule)
    {
        if ($schedule->user_id !== auth()->id() && !auth()->user()->hasPermissionTo('access all centers')) {
            abort(403);
        }

        $schedule->delete();

        return back()->with('success', 'Scheduled report deleted successfully');
    }

    /**
     * Generate taxable accumulatives report.
     */
    public function generateTaxableAccumulatives(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'tax_year' => 'required|integer|min:2020|max:2100',
            'currency' => 'required|in:ZWG,USD',
        ]);

        try {
            // Create the accumulative report
            $accumulative = TaxableAccumulative::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'tax_year' => $validated['tax_year'],
                'currency' => $validated['currency'],
                'total_taxable_income' => 0,
                'total_tax_paid' => 0,
                'total_outstanding_tax' => 0,
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated taxable accumulatives report for {$validated['tax_year']}",
                'model_type' => 'TaxableAccumulative',
                'model_id' => $accumulative->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'accumulative_id' => $accumulative->id,
                'download_url' => route('reports.taxable-accumulatives.download', $accumulative->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download taxable accumulatives report as PDF.
     */
    public function downloadTaxableAccumulatives(TaxableAccumulative $accumulative)
    {
        if (!$accumulative->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.taxable-accumulatives', [
            'accumulative' => $accumulative,
            'payroll' => $accumulative->payroll,
            'details' => $accumulative->details()->orderByTaxableIncome()->get(),
        ])
        ->setPaper('a4', 'portrait');

        return $pdf->download("taxable_accumulatives_{$accumulative->tax_year}_{$accumulative->currency}.pdf");
    }

    /**
     * Generate tax cell accumulatives report.
     */
    public function generateTaxCellAccumulatives(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'tax_year' => 'required|integer|min:2020|max:2100',
            'currency' => 'required|in:ZWG,USD',
        ]);

        try {
            // Create the tax cell accumulative report
            $cellAccumulative = TaxCellAccumulative::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'tax_year' => $validated['tax_year'],
                'currency' => $validated['currency'],
                'tax_bracket_summary' => [], // Would be calculated from actual data
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated tax cell accumulatives report for {$validated['tax_year']}",
                'model_type' => 'TaxCellAccumulative',
                'model_id' => $cellAccumulative->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'cell_accumulative_id' => $cellAccumulative->id,
                'download_url' => route('reports.tax-cell-accumulatives.download', $cellAccumulative->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download tax cell accumulatives report as PDF.
     */
    public function downloadTaxCellAccumulatives(TaxCellAccumulative $cellAccumulative)
    {
        if (!$cellAccumulative->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.tax-cell-accumulatives', [
            'cellAccumulative' => $cellAccumulative,
            'payroll' => $cellAccumulative->payroll,
            'details' => $cellAccumulative->details()->orderByRate()->get(),
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download("tax_cell_accumulatives_{$cellAccumulative->tax_year}_{$cellAccumulative->currency}.pdf");
    }

    /**
     * Generate retirement warning report.
     */
    public function generateRetirementWarning(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'warning_threshold_months' => 'required|integer|min:1|max:60',
        ]);

        try {
            // Create the retirement warning report
            $warning = RetirementWarning::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'warning_threshold_months' => $validated['warning_threshold_months'],
                'total_warnings' => 0,
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated retirement warning report ({$validated['warning_threshold_months']} months threshold)",
                'model_type' => 'RetirementWarning',
                'model_id' => $warning->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'warning_id' => $warning->id,
                'download_url' => route('reports.retirement-warning.download', $warning->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download retirement warning report as PDF.
     */
    public function downloadRetirementWarning(RetirementWarning $warning)
    {
        if (!$warning->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.retirement-warning', [
            'warning' => $warning,
            'payroll' => $warning->payroll,
            'details' => $warning->details()->orderByUrgency()->get(),
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download("retirement_warning_{$warning->id}.pdf");
    }

    /**
     * Generate employee requisition report.
     */
    public function generateEmployeeRequisition(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        try {
            // Create the employee requisition report
            $requisition = EmployeeRequisition::create([
                'payroll_id' => $validated['payroll_id'],
                'generated_by' => auth()->id(),
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'total_active_employees' => 0,
                'total_terminated' => 0,
                'total_hired' => 0,
                'turnover_rate' => 0,
                'generated_at' => now(),
            ]);

            // Log generation
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => ActivityLog::ACTION_CREATE,
                'description' => "Generated employee requisition report for {$requisition->period_display}",
                'model_type' => 'EmployeeRequisition',
                'model_id' => $requisition->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'requisition_id' => $requisition->id,
                'download_url' => route('reports.employee-requisition.download', $requisition->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download employee requisition report as PDF.
     */
    public function downloadEmployeeRequisition(EmployeeRequisition $requisition)
    {
        if (!$requisition->canAccess(auth()->user())) {
            abort(403);
        }

        $pdf = Pdf::loadView('reports.employee-requisition', [
            'requisition' => $requisition,
            'payroll' => $requisition->payroll,
        ])
        ->setPaper('a4', 'portrait');

        return $pdf->download("employee_requisition_{$requisition->id}.pdf");
    }

    /**
     * Generate payroll summary report.
     */
    public function generatePayrollSummary(Request $request)
    {
        $validated = $request->validate([
            'payroll_id' => 'required|exists:payrolls,id',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2100',
        ]);

        $payroll = Payroll::findOrFail($validated['payroll_id']);

        // Get all payslips for this period
        $payslips = Payslip::with(['employee.department', 'transactions'])
            ->where('payroll_id', $validated['payroll_id'])
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->get();

        // Calculate totals
        $summary = [
            'total_employees' => $payslips->count(),
            'gross_salary_zwg' => $payslips->sum('gross_salary_zwg'),
            'gross_salary_usd' => $payslips->sum('gross_salary_usd'),
            'total_deductions_zwg' => $payslips->sum('total_deductions_zwg'),
            'total_deductions_usd' => $payslips->sum('total_deductions_usd'),
            'net_salary_zwg' => $payslips->sum('net_salary_zwg'),
            'net_salary_usd' => $payslips->sum('net_salary_usd'),
        ];

        // Group by department
        $departmentBreakdown = $payslips->groupBy(function ($payslip) {
            return $payslip->employee->department?->department_name ?? 'Unassigned';
        })->map(function ($deptPayslips) {
            return [
                'count' => $deptPayslips->count(),
                'gross_zwg' => $deptPayslips->sum('gross_salary_zwg'),
                'gross_usd' => $deptPayslips->sum('gross_salary_usd'),
                'net_zwg' => $deptPayslips->sum('net_salary_zwg'),
                'net_usd' => $deptPayslips->sum('net_salary_usd'),
            ];
        });

        // Transaction code breakdown
        $transactionBreakdown = [];
        foreach ($payslips as $payslip) {
            foreach ($payslip->transactions as $transaction) {
                $key = $transaction->description;
                if (!isset($transactionBreakdown[$key])) {
                    $transactionBreakdown[$key] = [
                        'type' => $transaction->transaction_type,
                        'amount_zwg' => 0,
                        'amount_usd' => 0,
                    ];
                }
                $transactionBreakdown[$key]['amount_zwg'] += $transaction->amount_zwg;
                $transactionBreakdown[$key]['amount_usd'] += $transaction->amount_usd;
            }
        }

        $monthName = date('F', mktime(0, 0, 0, $validated['period_month'], 1));

        $pdf = Pdf::loadView('reports.payroll-summary', [
            'payroll' => $payroll,
            'summary' => $summary,
            'departmentBreakdown' => $departmentBreakdown,
            'transactionBreakdown' => $transactionBreakdown,
            'period' => "{$monthName} {$validated['period_year']}",
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download("payroll_summary_{$monthName}_{$validated['period_year']}.pdf");
    }
}
