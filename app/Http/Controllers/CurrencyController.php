<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     */
    public function index(Request $request)
    {
        $query = Currency::query();

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $currencies = $query->orderBy('is_base', 'desc')
                           ->orderBy('code')
                           ->paginate(15)
                           ->through(function ($currency) {
                               return [
                                   'currency_id' => $currency->currency_id,
                                   'code' => $currency->code,
                                   'name' => $currency->name,
                                   'symbol' => $currency->symbol,
                                   'exchange_rate' => $currency->exchange_rate,
                                   'formatted_rate' => $currency->formatted_rate,
                                   'is_base' => $currency->is_base,
                                   'is_active' => $currency->is_active,
                                   'decimal_places' => $currency->decimal_places,
                                   'description' => $currency->description,
                                   'display_name' => $currency->display_name,
                                   'created_at' => $currency->created_at,
                                   'updated_at' => $currency->updated_at,
                               ];
                           });

        return Inertia::render('Settings/Currencies/Index', [
            'currencies' => $currencies,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created currency.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'decimal_places' => 'required|integer|min:0|max:4',
            'description' => 'nullable|string',
        ]);

        try {
            Currency::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'exchange_rate' => $request->exchange_rate,
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => $request->decimal_places,
                'description' => $request->description,
            ]);

            Log::info("Currency created by user " . Auth::id() . ": {$request->code}");

            return back()->with('success', 'Currency created successfully');

        } catch (\Exception $e) {
            Log::error("Currency creation failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while creating the currency']);
        }
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency)
    {
        return response()->json([
            'currency' => [
                'currency_id' => $currency->currency_id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'exchange_rate' => $currency->exchange_rate,
                'formatted_rate' => $currency->formatted_rate,
                'is_base' => $currency->is_base,
                'is_active' => $currency->is_active,
                'decimal_places' => $currency->decimal_places,
                'description' => $currency->description,
                'created_at' => $currency->created_at,
                'updated_at' => $currency->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified currency.
     */
    public function update(Request $request, Currency $currency)
    {
        $this->authorize('update', $currency);

        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'decimal_places' => 'required|integer|min:0|max:4',
            'description' => 'nullable|string',
        ]);

        try {
            // Prevent changing exchange rate of base currency
            if ($currency->is_base && $request->exchange_rate != 1.0000) {
                return back()->withErrors(['exchange_rate' => 'Cannot change exchange rate of base currency']);
            }

            $currency->update([
                'name' => $request->name,
                'symbol' => $request->symbol,
                'exchange_rate' => $request->exchange_rate,
                'decimal_places' => $request->decimal_places,
                'description' => $request->description,
            ]);

            Log::info("Currency updated by user " . Auth::id() . ": Currency ID {$currency->currency_id}");

            return back()->with('success', 'Currency updated successfully');

        } catch (\Exception $e) {
            Log::error("Currency update failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while updating the currency']);
        }
    }

    /**
     * Remove the specified currency.
     */
    public function destroy(Currency $currency)
    {
        $this->authorize('delete', $currency);

        try {
            // Prevent deleting base currency
            if ($currency->is_base) {
                return back()->withErrors(['error' => 'Cannot delete base currency']);
            }

            $currencyCode = $currency->code;
            $currency->delete();

            Log::info("Currency deleted by user " . Auth::id() . ": {$currencyCode}");

            return back()->with('success', 'Currency deleted successfully');

        } catch (\Exception $e) {
            Log::error("Currency deletion failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while deleting the currency']);
        }
    }

    /**
     * Toggle currency active status.
     */
    public function toggleStatus(Currency $currency)
    {
        $this->authorize('update', $currency);

        try {
            if ($currency->is_base) {
                return back()->withErrors(['error' => 'Cannot deactivate base currency']);
            }

            if ($currency->is_active) {
                $currency->deactivate();
                $message = 'Currency deactivated successfully';
            } else {
                $currency->activate();
                $message = 'Currency activated successfully';
            }

            Log::info("Currency status toggled by user " . Auth::id() . ": {$currency->code}");

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Currency status toggle failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while updating currency status']);
        }
    }

    /**
     * Set currency as base currency.
     */
    public function setAsBase(Currency $currency)
    {
        $this->authorize('update', $currency);

        try {
            $currency->setAsBase();

            Log::info("Base currency changed by user " . Auth::id() . ": {$currency->code}");

            return back()->with('success', 'Base currency updated successfully');

        } catch (\Exception $e) {
            Log::error("Base currency update failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred while setting base currency']);
        }
    }

    /**
     * Get all active currencies.
     */
    public function getActive()
    {
        $currencies = Currency::getActiveCurrencies();

        return response()->json([
            'currencies' => $currencies->map(function ($currency) {
                return [
                    'currency_id' => $currency->currency_id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'display_name' => $currency->display_name,
                ];
            }),
        ]);
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(Request $request)
    {
        $request->validate([
            'from' => 'required|string|exists:currencies,code',
            'to' => 'required|string|exists:currencies,code',
        ]);

        try {
            $rate = Currency::getExchangeRate($request->from, $request->to);

            return response()->json([
                'from' => $request->from,
                'to' => $request->to,
                'rate' => $rate,
                'formatted_rate' => number_format($rate, 4),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Convert amount between currencies.
     */
    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|exists:currencies,code',
            'to' => 'required|string|exists:currencies,code',
        ]);

        try {
            $convertedAmount = Currency::convert($request->amount, $request->from, $request->to);

            return response()->json([
                'original_amount' => $request->amount,
                'from' => $request->from,
                'to' => $request->to,
                'converted_amount' => $convertedAmount,
                'formatted_amount' => number_format($convertedAmount, 2),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
