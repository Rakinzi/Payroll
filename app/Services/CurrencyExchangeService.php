<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRateHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CurrencyExchangeService
{
    /**
     * Free API endpoint for exchange rates
     * Alternative: https://api.exchangerate-api.com/v4/latest/{base}
     */
    protected string $apiUrl = 'https://api.exchangerate-api.com/v4/latest';

    /**
     * Update all active currency exchange rates from API.
     */
    public function updateAllRates(): array
    {
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            return [
                'success' => false,
                'message' => 'No base currency configured',
            ];
        }

        $currencies = Currency::active()->where('is_base', false)->get();
        $updated = [];
        $failed = [];

        foreach ($currencies as $currency) {
            try {
                $newRate = $this->fetchExchangeRate($baseCurrency->code, $currency->code);

                if ($newRate) {
                    $previousRate = $currency->exchange_rate;

                    // Update currency
                    $currency->update(['exchange_rate' => $newRate]);

                    // Record history
                    ExchangeRateHistory::recordRateChange(
                        $currency->currency_id,
                        $newRate,
                        $previousRate,
                        'api',
                        null,
                        'Automatic update from exchange rate API'
                    );

                    $updated[] = [
                        'currency' => $currency->code,
                        'old_rate' => $previousRate,
                        'new_rate' => $newRate,
                    ];

                    Log::info("Updated exchange rate for {$currency->code}: {$previousRate} → {$newRate}");
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'currency' => $currency->code,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to update exchange rate for {$currency->code}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'updated' => $updated,
            'failed' => $failed,
            'total_updated' => count($updated),
            'total_failed' => count($failed),
        ];
    }

    /**
     * Fetch exchange rate from API.
     */
    public function fetchExchangeRate(string $fromCode, string $toCode): ?float
    {
        $cacheKey = "exchange_rate_{$fromCode}_{$toCode}";

        // Check cache first (valid for 1 hour)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(10)->get("{$this->apiUrl}/{$fromCode}");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['rates'][$toCode])) {
                    $rate = (float) $data['rates'][$toCode];

                    // Cache for 1 hour
                    Cache::put($cacheKey, $rate, 3600);

                    return $rate;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error("API request failed for {$fromCode} to {$toCode}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update single currency rate.
     */
    public function updateCurrencyRate(Currency $currency, ?int $userId = null): array
    {
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            return [
                'success' => false,
                'message' => 'No base currency configured',
            ];
        }

        if ($currency->is_base) {
            return [
                'success' => false,
                'message' => 'Cannot update base currency rate',
            ];
        }

        try {
            $newRate = $this->fetchExchangeRate($baseCurrency->code, $currency->code);

            if (!$newRate) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch exchange rate from API',
                ];
            }

            $previousRate = $currency->exchange_rate;

            // Update currency
            $currency->update(['exchange_rate' => $newRate]);

            // Record history
            ExchangeRateHistory::recordRateChange(
                $currency->currency_id,
                $newRate,
                $previousRate,
                'api',
                $userId,
                'Manual API update'
            );

            Log::info("Updated exchange rate for {$currency->code}: {$previousRate} → {$newRate}");

            return [
                'success' => true,
                'currency' => $currency->code,
                'previous_rate' => $previousRate,
                'new_rate' => $newRate,
                'change' => $newRate - $previousRate,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to update {$currency->code}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get supported currencies from API.
     */
    public function getSupportedCurrencies(): array
    {
        try {
            $baseCurrency = Currency::getBaseCurrency();
            $baseCode = $baseCurrency ? $baseCurrency->code : 'USD';

            $response = Http::timeout(10)->get("{$this->apiUrl}/{$baseCode}");

            if ($response->successful()) {
                $data = $response->json();
                return array_keys($data['rates'] ?? []);
            }

            return [];

        } catch (\Exception $e) {
            Log::error("Failed to fetch supported currencies: " . $e->getMessage());
            return [];
        }
    }
}
