<?php

namespace App\Http\Controllers;

use App\Models\TransactionCode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionCodeController extends Controller
{
    /**
     * Display a listing of transaction codes.
     */
    public function index(Request $request)
    {
        $query = TransactionCode::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code_name', 'LIKE', "%{$search}%")
                  ->orWhere('code_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('code_category', $request->get('category'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // Filter by benefits only
        if ($request->has('is_benefit') && $request->get('is_benefit')) {
            $query->where('is_benefit', true);
        }

        $transactionCodes = $query->orderBy('code_number', 'asc')->paginate(25);

        return Inertia::render('transaction-codes/index', [
            'transactionCodes' => $transactionCodes,
            'filters' => $request->only(['search', 'category', 'is_active', 'is_benefit']),
            'categories' => TransactionCode::getCategories(),
        ]);
    }

    /**
     * Show the form for creating a new transaction code.
     */
    public function create()
    {
        $nextCodeNumber = $this->getNextCodeNumber();

        return Inertia::render('transaction-codes/create', [
            'nextCodeNumber' => $nextCodeNumber,
            'categories' => TransactionCode::getCategories(),
        ]);
    }

    /**
     * Store a newly created transaction code.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_number' => 'nullable|integer|unique:transaction_codes,code_number',
            'code_name' => 'required|string|max:255',
            'code_category' => 'required|in:Earning,Deduction,Contribution',
            'is_benefit' => 'boolean',
            'code_amount' => 'nullable|numeric|min:0',
            'minimum_threshold' => 'nullable|numeric|min:0',
            'maximum_threshold' => 'nullable|numeric|min:0|gte:minimum_threshold',
            'code_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate that is_benefit can only be true for Earnings
        if (($validated['is_benefit'] ?? false) && $validated['code_category'] !== TransactionCode::CATEGORY_EARNING) {
            return back()->withErrors([
                'is_benefit' => 'Benefits can only be assigned to Earning transaction codes.'
            ]);
        }

        // Auto-generate code number if not provided
        if (empty($validated['code_number'])) {
            $validated['code_number'] = $this->getNextCodeNumber();
        }

        // Set is_editable to true for user-created codes
        $validated['is_editable'] = true;

        $transactionCode = TransactionCode::create($validated);

        return redirect()->route('transaction-codes.index')
            ->with('success', "Transaction code {$transactionCode->formatted_code} created successfully");
    }

    /**
     * Display the specified transaction code.
     */
    public function show(TransactionCode $transactionCode)
    {
        return Inertia::render('transaction-codes/show', [
            'transactionCode' => $transactionCode,
        ]);
    }

    /**
     * Show the form for editing the specified transaction code.
     */
    public function edit(TransactionCode $transactionCode)
    {
        // Prevent editing system codes
        if ($transactionCode->isSystem()) {
            return redirect()->route('transaction-codes.index')
                ->with('error', 'System transaction codes cannot be edited.');
        }

        return Inertia::render('transaction-codes/edit', [
            'transactionCode' => $transactionCode,
            'categories' => TransactionCode::getCategories(),
        ]);
    }

    /**
     * Update the specified transaction code.
     */
    public function update(Request $request, TransactionCode $transactionCode)
    {
        // Prevent updating system codes
        if ($transactionCode->isSystem()) {
            return redirect()->route('transaction-codes.index')
                ->with('error', 'System transaction codes cannot be modified.');
        }

        $validated = $request->validate([
            'code_number' => "nullable|integer|unique:transaction_codes,code_number,{$transactionCode->id}",
            'code_name' => 'required|string|max:255',
            'code_category' => 'required|in:Earning,Deduction,Contribution',
            'is_benefit' => 'boolean',
            'code_amount' => 'nullable|numeric|min:0',
            'minimum_threshold' => 'nullable|numeric|min:0',
            'maximum_threshold' => 'nullable|numeric|min:0|gte:minimum_threshold',
            'code_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate that is_benefit can only be true for Earnings
        if (($validated['is_benefit'] ?? false) && $validated['code_category'] !== TransactionCode::CATEGORY_EARNING) {
            return back()->withErrors([
                'is_benefit' => 'Benefits can only be assigned to Earning transaction codes.'
            ]);
        }

        $transactionCode->update($validated);

        return redirect()->route('transaction-codes.index')
            ->with('success', "Transaction code {$transactionCode->formatted_code} updated successfully");
    }

    /**
     * Remove the specified transaction code (soft delete).
     */
    public function destroy(TransactionCode $transactionCode)
    {
        // Prevent deleting system codes
        if ($transactionCode->isSystem()) {
            return redirect()->route('transaction-codes.index')
                ->with('error', 'System transaction codes cannot be deleted.');
        }

        // Check if code is in use by NEC grades
        if ($transactionCode->necGrades()->count() > 0) {
            return redirect()->route('transaction-codes.index')
                ->with('error', "Transaction code {$transactionCode->formatted_code} is currently in use and cannot be deleted.");
        }

        $transactionCode->delete();

        return redirect()->route('transaction-codes.index')
            ->with('success', "Transaction code {$transactionCode->formatted_code} deleted successfully");
    }

    /**
     * Get the next available code number.
     */
    private function getNextCodeNumber(): int
    {
        $lastCode = TransactionCode::orderBy('code_number', 'desc')->first();
        return $lastCode ? $lastCode->code_number + 1 : 1;
    }
}
