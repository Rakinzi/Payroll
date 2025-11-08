<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxCredit extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'credit_name',
        'credit_amount',
        'currency',
        'period',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_amount' => 'decimal:2',
    ];

    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWG = 'ZWG';

    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_ANNUAL = 'annual';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Get formatted credit amount.
     *
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->credit_amount, 2);
    }

    /**
     * Get credit value in specified currency.
     *
     * @param string $targetCurrency The target currency to convert to
     * @param float|null $exchangeRate The exchange rate to use for conversion
     * @return float
     */
    public function getValueInCurrency(string $targetCurrency, ?float $exchangeRate = null): float
    {
        if ($this->currency === $targetCurrency) {
            return $this->credit_amount;
        }

        // Convert using exchange rate
        if ($targetCurrency === self::CURRENCY_ZWG && $this->currency === self::CURRENCY_USD) {
            return $this->credit_amount * ($exchangeRate ?? 1);
        }

        if ($targetCurrency === self::CURRENCY_USD && $this->currency === self::CURRENCY_ZWG) {
            return $this->credit_amount / ($exchangeRate ?? 1);
        }

        return $this->credit_amount;
    }

    /**
     * Get available currencies.
     *
     * @return array
     */
    public static function getCurrencies(): array
    {
        return [
            self::CURRENCY_USD,
            self::CURRENCY_ZWG,
        ];
    }

    /**
     * Get available periods.
     *
     * @return array
     */
    public static function getPeriods(): array
    {
        return [
            self::PERIOD_MONTHLY,
            self::PERIOD_ANNUAL,
        ];
    }
}
