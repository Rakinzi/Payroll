<?php

namespace App\Http\Controllers;

use App\Models\DefaultTransaction;
use App\Models\AccountingPeriod;
use App\Models\TransactionCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DefaultTransactionController extends Controller
{
    /**
     * Display default transactions for current period.
     */
    public function index()
    {
        $currentPeriod = AccountingPeriod::current()->first();

        if (!$currentPeriod) {
            return Inertia::render('Payroll/DefaultTransactions/Index', [
                'currentPeriod' => null,
                'transactions' => [],
                'transactionCodes' => [],
                'message' => 'No active period found',
            ]);
        }

        $transactions = DefaultTransaction::with(['transactionCode'])
            ->forPeriod($currentPeriod->period_id)
            ->forCenter(Auth::user()->center_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $transactionCodes = TransactionCode::orderBy('code_number')->get();

        return Inertia::render('Payroll/DefaultTransactions/Index', [
            'currentPeriod' => $currentPeriod,
            'transactions' => $transactions,
            'transactionCodes' => $transactionCodes,
            'userCenterId' => Auth::user()->center_id,
        ]);
    }

    /**
     * Store multiple default transactions.
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
            'transactions' => 'required|array|min:1',
            'transactions.*.code_id' => 'required|exists:transaction_codes,code_id',
            'transactions.*.transaction_effect' => 'required|in:+,-',
            'transactions.*.employee_amount' => 'required|numeric|min:0',
            'transactions.*.employer_amount' => 'nullable|numeric|min:0',
            'transactions.*.hours_worked' => 'nullable|numeric|min:0',
            'transactions.*.transaction_currency' => 'required|in:ZWG,USD',
        ]);

        // Verify current period
        $currentPeriod = AccountingPeriod::current()->first();
        if (!$currentPeriod || $request->period_id != $currentPeriod->period_id) {
            return back()->withErrors(['period' => 'Can only modify current period transactions']);
        }

        // Authorization check
        if (Auth::user()->center_id == 0) {
            return back()->withErrors(['auth' => 'Admin users cannot create default transactions']);
        }

        DB::beginTransaction();
        try {
            // Clear existing transactions for this period and center
            DefaultTransaction::forPeriod($request->period_id)
                ->forCenter(Auth::user()->center_id)
                ->delete();

            // Create new transactions
            $created = 0;
            $skipped = 0;

            foreach ($request->transactions as $transactionData) {
                $transaction = new DefaultTransaction($transactionData);
                $transaction->period_id = $request->period_id;
                $transaction->center_id = Auth::user()->center_id;

                if ($transaction->validateUniqueness()) {
                    $transaction->save();
                    $created++;
                } else {
                    $skipped++;
                }
            }

            DB::commit();

            $message = "Transactions saved successfully. Created: {$created}";
            if ($skipped > 0) {
                $message .= ", Skipped (duplicates): {$skipped}";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save transactions: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a default transaction.
     */
    public function destroy(DefaultTransaction $transaction)
    {
        $this->authorize('delete', $transaction);

        try {
            $transaction->delete();
            return back()->with('success', 'Transaction deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear all default transactions for current period.
     */
    public function clearAll(Request $request)
    {
        $request->validate([
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
        ]);

        $currentPeriod = AccountingPeriod::current()->first();
        if (!$currentPeriod || $request->period_id != $currentPeriod->period_id) {
            return back()->withErrors(['period' => 'Can only clear current period transactions']);
        }

        try {
            $count = DefaultTransaction::forPeriod($request->period_id)
                ->forCenter(Auth::user()->center_id)
                ->delete();

            return back()->with('success', "Cleared {$count} transactions successfully");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to clear transactions: ' . $e->getMessage()]);
        }
    }

    /**
     * Get transaction codes (AJAX).
     */
    public function getTransactionCodes()
    {
        $codes = TransactionCode::orderBy('code_number')
            ->select('code_id', 'code_number', 'code_name', 'transaction_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $codes,
        ]);
    }
}
