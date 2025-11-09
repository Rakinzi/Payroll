<?php

namespace App\Http\Controllers;

use App\Models\CustomTransaction;
use App\Models\Employee;
use App\Models\TransactionCode;
use App\Models\AccountingPeriod;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomTransactionController extends Controller
{
    /**
     * Display a listing of custom transactions.
     */
    public function index(Request $request)
    {
        $payrollId = $request->get('payroll_id');
        if (!$payrollId) {
            $latestPayroll = Payroll::active()->latest()->first();
            $payrollId = $latestPayroll?->id;
        }

        $periodId = $request->get('period_id');
        if (!$periodId) {
            $currentPeriod = AccountingPeriod::current()->first();
            $periodId = $currentPeriod?->period_id;
        }

        $query = CustomTransaction::with(['employees', 'transactionCodes', 'period'])
            ->forCenter(Auth::user()->center_id);

        if ($periodId) {
            $query->forPeriod($periodId);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        $payrolls = Payroll::active()->orderBy('created_at', 'desc')->get();
        $periods = $payrollId
            ? AccountingPeriod::forPayroll($payrollId)->orderBy('period_start', 'desc')->get()
            : collect([]);

        $employees = Employee::where('center_id', Auth::user()->center_id)
            ->where('is_active', true)
            ->where('is_ex', false)
            ->orderBy('firstname')
            ->get();

        $transactionCodes = TransactionCode::orderBy('code_number')->get();

        return Inertia::render('Payroll/CustomTransactions/Index', [
            'transactions' => $transactions,
            'payrolls' => $payrolls,
            'periods' => $periods,
            'employees' => $employees,
            'transactionCodes' => $transactionCodes,
            'currentPayrollId' => $payrollId,
            'currentPeriodId' => $periodId,
            'userCenterId' => Auth::user()->center_id,
        ]);
    }

    /**
     * Store a newly created custom transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
            'worked_hours' => 'required|numeric|min:0',
            'base_hours' => 'required|numeric|min:1',
            'base_amount' => 'nullable|numeric|min:0',
            'use_basic' => 'required|boolean',
            'employees' => 'required|array|min:1',
            'employees.*' => 'string',
            'transaction_codes' => 'required|array|min:1',
            'transaction_codes.*' => 'exists:transaction_codes,code_id',
        ]);

        // Authorization check
        if (Auth::user()->center_id == 0) {
            return back()->withErrors(['auth' => 'Admin users cannot create custom transactions']);
        }

        DB::beginTransaction();
        try {
            $transaction = CustomTransaction::create([
                'center_id' => Auth::user()->center_id,
                'period_id' => $validated['period_id'],
                'worked_hours' => $validated['worked_hours'],
                'base_hours' => $validated['base_hours'],
                'base_amount' => $validated['base_amount'],
                'use_basic' => $validated['use_basic'],
            ]);

            // Assign employees and transaction codes
            $transaction->assignToEmployees($validated['employees']);
            $transaction->assignTransactionCodes($validated['transaction_codes']);

            DB::commit();

            return back()->with('success', 'Custom transaction created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified custom transaction.
     */
    public function show(CustomTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load(['employees', 'transactionCodes', 'period', 'center']);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Update the specified custom transaction.
     */
    public function update(Request $request, CustomTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'worked_hours' => 'required|numeric|min:0',
            'base_hours' => 'required|numeric|min:1',
            'base_amount' => 'nullable|numeric|min:0',
            'use_basic' => 'required|boolean',
            'employees' => 'required|array|min:1',
            'employees.*' => 'string',
            'transaction_codes' => 'required|array|min:1',
            'transaction_codes.*' => 'exists:transaction_codes,code_id',
        ]);

        DB::beginTransaction();
        try {
            $transaction->update([
                'worked_hours' => $validated['worked_hours'],
                'base_hours' => $validated['base_hours'],
                'base_amount' => $validated['base_amount'],
                'use_basic' => $validated['use_basic'],
            ]);

            $transaction->assignToEmployees($validated['employees']);
            $transaction->assignTransactionCodes($validated['transaction_codes']);

            DB::commit();

            return back()->with('success', 'Custom transaction updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified custom transaction.
     */
    public function destroy(CustomTransaction $transaction)
    {
        $this->authorize('delete', $transaction);

        try {
            $transaction->delete();
            return back()->with('success', 'Custom transaction deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Get employees assigned to a custom transaction (AJAX).
     */
    public function getEmployees(CustomTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        $employees = $transaction->employees()
            ->select('id', 'firstname', 'surname', 'emp_system_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    /**
     * Get transaction codes assigned to a custom transaction (AJAX).
     */
    public function getCodes(CustomTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        $codes = $transaction->transactionCodes()
            ->select('code_id', 'code_number', 'code_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $codes,
        ]);
    }

    /**
     * Calculate estimated amounts for custom transaction (AJAX).
     */
    public function calculateEstimate(Request $request)
    {
        $request->validate([
            'worked_hours' => 'required|numeric|min:0',
            'base_hours' => 'required|numeric|min:1',
            'base_amount' => 'nullable|numeric|min:0',
            'use_basic' => 'required|boolean',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $workRatio = $request->worked_hours / $request->base_hours;
        $cappedRatio = min($workRatio, 1.0);

        $estimate = [
            'work_ratio' => round($workRatio * 100, 2),
            'capped_ratio' => round($cappedRatio * 100, 2),
            'estimated_amount_usd' => null,
            'estimated_amount_zwl' => null,
        ];

        if ($request->employee_id) {
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                if ($request->use_basic) {
                    $estimate['estimated_amount_usd'] = round($cappedRatio * $employee->basic_salary_usd, 2);
                    $estimate['estimated_amount_zwl'] = round($cappedRatio * $employee->basic_salary, 2);
                } else {
                    $baseAmount = $request->base_amount ?? 0;
                    $estimate['estimated_amount_usd'] = round($cappedRatio * $baseAmount, 2);
                    // ZWG would need exchange rate
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $estimate,
        ]);
    }
}
