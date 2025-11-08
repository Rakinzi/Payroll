<?php

namespace App\Http\Controllers;

use App\Models\TaxCredit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxCreditController extends Controller
{
    /**
     * Display a listing of tax credits.
     */
    public function index(Request $request)
    {
        $query = TaxCredit::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('credit_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency', $request->get('currency'));
        }

        // Filter by period
        if ($request->has('period')) {
            $query->where('period', $request->get('period'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        $taxCredits = $query->orderBy('credit_name', 'asc')->paginate(25);

        return Inertia::render('tax-credits/index', [
            'taxCredits' => $taxCredits,
            'filters' => $request->only(['search', 'currency', 'period', 'is_active']),
            'currencies' => TaxCredit::getCurrencies(),
            'periods' => TaxCredit::getPeriods(),
        ]);
    }

    /**
     * Show the form for creating a new tax credit.
     */
    public function create()
    {
        return Inertia::render('tax-credits/create', [
            'currencies' => TaxCredit::getCurrencies(),
            'periods' => TaxCredit::getPeriods(),
        ]);
    }

    /**
     * Store a newly created tax credit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'credit_name' => 'required|string|max:255|unique:tax_credits,credit_name',
            'credit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $taxCredit = TaxCredit::create($validated);

        return redirect()->route('tax-credits.index')
            ->with('success', "Tax credit {$taxCredit->credit_name} created successfully");
    }

    /**
     * Display the specified tax credit.
     */
    public function show(TaxCredit $taxCredit)
    {
        return Inertia::render('tax-credits/show', [
            'taxCredit' => $taxCredit,
        ]);
    }

    /**
     * Show the form for editing the specified tax credit.
     */
    public function edit(TaxCredit $taxCredit)
    {
        return Inertia::render('tax-credits/edit', [
            'taxCredit' => $taxCredit,
            'currencies' => TaxCredit::getCurrencies(),
            'periods' => TaxCredit::getPeriods(),
        ]);
    }

    /**
     * Update the specified tax credit.
     */
    public function update(Request $request, TaxCredit $taxCredit)
    {
        // Allow updating amount, description, and status
        // But not credit_name, currency, or period (as per document requirements)
        $validated = $request->validate([
            'credit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $taxCredit->update($validated);

        return redirect()->route('tax-credits.index')
            ->with('success', "Tax credit {$taxCredit->credit_name} updated successfully");
    }

    /**
     * Remove the specified tax credit (soft delete).
     */
    public function destroy(TaxCredit $taxCredit)
    {
        // Check if credit is being used in payroll calculations
        // This is a placeholder - implement actual check based on your payroll system
        if ($this->isCreditInUse($taxCredit)) {
            return redirect()->route('tax-credits.index')
                ->with('error', "Tax credit {$taxCredit->credit_name} is currently in use and cannot be deleted");
        }

        $taxCredit->delete();

        return redirect()->route('tax-credits.index')
            ->with('success', "Tax credit {$taxCredit->credit_name} deleted successfully");
    }

    /**
     * Check if tax credit is currently in use.
     *
     * @param TaxCredit $taxCredit
     * @return bool
     */
    private function isCreditInUse(TaxCredit $taxCredit): bool
    {
        // TODO: Implement actual check when payroll system is integrated
        // Check if credit is referenced in:
        // - Current payroll period calculations
        // - Employee tax computations
        // - Historical payroll records (if applicable)
        return false;
    }
}
