<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxCredit;
use Illuminate\Http\Request;

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
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->get('per_page', 25);
        $taxCredits = $query->orderBy('credit_name', 'asc')->paginate($perPage);

        // Add formatted_value to each tax credit
        $taxCredits->getCollection()->transform(function ($credit) {
            $credit->formatted_value = $credit->formatted_value;
            return $credit;
        });

        return response()->json($taxCredits);
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
        $taxCredit->formatted_value = $taxCredit->formatted_value;

        return response()->json($taxCredit, 201);
    }

    /**
     * Display the specified tax credit.
     */
    public function show(TaxCredit $taxCredit)
    {
        $taxCredit->formatted_value = $taxCredit->formatted_value;
        return response()->json($taxCredit);
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
        $taxCredit->formatted_value = $taxCredit->formatted_value;

        return response()->json($taxCredit);
    }

    /**
     * Remove the specified tax credit (soft delete).
     */
    public function destroy(TaxCredit $taxCredit)
    {
        // Check if credit is being used in payroll calculations
        if ($this->isCreditInUse($taxCredit)) {
            return response()->json([
                'message' => 'Tax credit is currently in use and cannot be deleted.',
                'errors' => ['credit' => ['This tax credit is in use and cannot be deleted.']]
            ], 422);
        }

        $taxCredit->delete();

        return response()->json(['message' => 'Tax credit deleted successfully']);
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
        return false;
    }
}
