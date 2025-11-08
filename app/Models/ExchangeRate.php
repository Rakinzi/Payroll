<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Supported currencies.
     */
    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWL = 'ZWL';
    const CURRENCY_ZWG = 'ZWG';
    const CURRENCY_RTGS = 'RTGS';

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope query to a specific currency pair.
     */
    public function scopeCurrencyPair($query, string $fromCurrency, string $toCurrency)
    {
        return $query->where('from_currency', $fromCurrency)
                     ->where('to_currency', $toCurrency);
    }

    /**
     * Scope query to active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to effective on a specific date.
     */
    public function scopeEffectiveOn($query, string $date)
    {
        return $query->where('effective_date', '<=', $date)
                     ->orderBy('effective_date', 'desc');
    }

    /**
     * Get the current exchange rate for a currency pair.
     */
    public static function getCurrentRate(string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $rate = self::currencyPair($fromCurrency, $toCurrency)
                    ->active()
                    ->effectiveOn(now()->toDateString())
                    ->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Get the current exchange rate or inverse.
     */
    public static function getRate(string $fromCurrency, string $toCurrency): ?float
    {
        // Try direct rate
        $rate = self::getCurrentRate($fromCurrency, $toCurrency);

        if ($rate !== null) {
            return $rate;
        }

        // Try inverse rate
        $inverseRate = self::getCurrentRate($toCurrency, $fromCurrency);

        if ($inverseRate !== null && $inverseRate > 0) {
            return 1 / $inverseRate;
        }

        return null;
    }

    /**
     * Convert amount from one currency to another.
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = self::getRate($fromCurrency, $toCurrency);

        if ($rate === null) {
            return null;
        }

        return round($amount * $rate, 2);
    }

    /**
     * Get formatted rate string.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 6);
    }

    /**
     * Get supported currencies.
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            self::CURRENCY_USD,
            self::CURRENCY_ZWL,
            self::CURRENCY_ZWG,
            self::CURRENCY_RTGS,
        ];
    }
}
