<?php

namespace App\Http\Controllers;

use App\Models\CurrencySplit;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CurrencySetupController extends Controller
{
    /**
     * Display the currency setup page.
     */
    public function index(Request $request)
    {
        $centerId = auth()->user()->center_id;

        // Get currency splits for the user's cost center
        $currencySplitsQuery = CurrencySplit::with('costCenter')
            ->forCenter($centerId)
            ->orderBy('effective_date', 'desc');

        if ($request->filled('split_active_only')) {
            $currencySplitsQuery->active();
        }

        $currencySplits = $currencySplitsQuery->paginate(10, ['*'], 'splits_page');

        // Get exchange rates
        $exchangeRatesQuery = ExchangeRate::orderBy('effective_date', 'desc')
            ->orderBy('from_currency')
            ->orderBy('to_currency');

        if ($request->filled('rate_active_only')) {
            $exchangeRatesQuery->active();
        }

        if ($request->filled('from_currency')) {
            $exchangeRatesQuery->where('from_currency', $request->from_currency);
        }

        if ($request->filled('to_currency')) {
            $exchangeRatesQuery->where('to_currency', $request->to_currency);
        }

        $exchangeRates = $exchangeRatesQuery->paginate(10, ['*'], 'rates_page');

        // Get current effective configurations
        $currentSplit = CurrencySplit::getCurrentSplit($centerId);

        return Inertia::render('currency-setup/index', [
            'currencySplits' => $currencySplits,
            'exchangeRates' => $exchangeRates,
            'currentSplit' => $currentSplit,
            'supportedCurrencies' => ExchangeRate::getSupportedCurrencies(),
            'filters' => $request->only(['split_active_only', 'rate_active_only', 'from_currency', 'to_currency']),
        ]);
    }

    /**
     * Store a new currency split.
     */
    public function storeSplit(Request $request)
    {
        $centerId = auth()->user()->center_id;

        $validated = $request->validate(array_merge(
            CurrencySplit::rules(),
            [
                'center_id' => 'required|uuid|in:' . $centerId, // Must match user's center
            ]
        ));

        // Validate percentages total 100
        if (abs(($validated['zwl_percentage'] + $validated['usd_percentage']) - 100) >= 0.01) {
            return redirect()->back()
                ->with('error', 'Currency split percentages must total 100%');
        }

        $currencySplit = CurrencySplit::create($validated);

        return redirect()->route('currency-setup.index')
            ->with('success', 'Currency split created successfully');
    }

    /**
     * Update a currency split.
     */
    public function updateSplit(Request $request, CurrencySplit $currencySplit)
    {
        $centerId = auth()->user()->center_id;

        // Ensure the split belongs to the user's cost center
        if ($currencySplit->center_id !== $centerId) {
            abort(403, 'Unauthorized access to this currency split');
        }

        $validated = $request->validate(array_merge(
            CurrencySplit::rules(true),
            [
                'center_id' => 'required|uuid|in:' . $centerId,
            ]
        ));

        // Validate percentages total 100
        if (abs(($validated['zwl_percentage'] + $validated['usd_percentage']) - 100) >= 0.01) {
            return redirect()->back()
                ->with('error', 'Currency split percentages must total 100%');
        }

        $currencySplit->update($validated);

        return redirect()->route('currency-setup.index')
            ->with('success', 'Currency split updated successfully');
    }

    /**
     * Delete a currency split.
     */
    public function destroySplit(CurrencySplit $currencySplit)
    {
        $centerId = auth()->user()->center_id;

        // Ensure the split belongs to the user's cost center
        if ($currencySplit->center_id !== $centerId) {
            abort(403, 'Unauthorized access to this currency split');
        }

        $currencySplit->delete();

        return redirect()->route('currency-setup.index')
            ->with('success', 'Currency split deleted successfully');
    }

    /**
     * Store a new exchange rate.
     */
    public function storeRate(Request $request)
    {
        $validated = $request->validate(ExchangeRate::rules());

        // Check for duplicate active rate on same date
        $existing = ExchangeRate::currencyPair($validated['from_currency'], $validated['to_currency'])
            ->where('effective_date', $validated['effective_date'])
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'An exchange rate for this currency pair already exists on this date');
        }

        $exchangeRate = ExchangeRate::create($validated);

        return redirect()->route('currency-setup.index')
            ->with('success', 'Exchange rate created successfully');
    }

    /**
     * Update an exchange rate.
     */
    public function updateRate(Request $request, ExchangeRate $exchangeRate)
    {
        $validated = $request->validate(ExchangeRate::rules(true));

        // Check for duplicate active rate on same date (excluding current)
        $existing = ExchangeRate::currencyPair($validated['from_currency'], $validated['to_currency'])
            ->where('effective_date', $validated['effective_date'])
            ->where('id', '!=', $exchangeRate->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'An exchange rate for this currency pair already exists on this date');
        }

        $exchangeRate->update($validated);

        return redirect()->route('currency-setup.index')
            ->with('success', 'Exchange rate updated successfully');
    }

    /**
     * Delete an exchange rate.
     */
    public function destroyRate(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();

        return redirect()->route('currency-setup.index')
            ->with('success', 'Exchange rate deleted successfully');
    }

    /**
     * Get current exchange rate for a currency pair.
     */
    public function getCurrentRate(Request $request)
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
        ]);

        $rate = ExchangeRate::getRate($validated['from_currency'], $validated['to_currency']);

        if ($rate === null) {
            return response()->json([
                'error' => 'No exchange rate found for this currency pair'
            ], 404);
        }

        return response()->json([
            'from_currency' => $validated['from_currency'],
            'to_currency' => $validated['to_currency'],
            'rate' => $rate,
        ]);
    }

    /**
     * Convert amount between currencies.
     */
    public function convertCurrency(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
        ]);

        $converted = ExchangeRate::convert(
            $validated['amount'],
            $validated['from_currency'],
            $validated['to_currency']
        );

        if ($converted === null) {
            return response()->json([
                'error' => 'No exchange rate found for this currency pair'
            ], 404);
        }

        return response()->json([
            'amount' => $validated['amount'],
            'from_currency' => $validated['from_currency'],
            'to_currency' => $validated['to_currency'],
            'converted_amount' => $converted,
            'rate' => ExchangeRate::getRate($validated['from_currency'], $validated['to_currency']),
        ]);
    }
}
