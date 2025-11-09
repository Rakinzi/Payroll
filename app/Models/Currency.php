<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currencies';
    protected $primaryKey = 'currency_id';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
        'decimal_places',
        'description',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
    ];

    protected $appends = [
        'formatted_rate',
        'display_name',
    ];

    // Accessors
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->exchange_rate, 4);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // Business Methods
    public function formatAmount(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }

    public function convertTo(float $amount, Currency $targetCurrency): float
    {
        // Convert to base currency first, then to target currency
        $baseAmount = $amount / $this->exchange_rate;
        return $baseAmount * $targetCurrency->exchange_rate;
    }

    public function convertFrom(float $amount, Currency $sourceCurrency): float
    {
        return $sourceCurrency->convertTo($amount, $this);
    }

    public function updateExchangeRate(float $newRate): bool
    {
        if ($this->is_base) {
            throw new \Exception('Cannot change exchange rate of base currency');
        }

        return $this->update(['exchange_rate' => $newRate]);
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        if ($this->is_base) {
            throw new \Exception('Cannot deactivate base currency');
        }

        return $this->update(['is_active' => false]);
    }

    public function setAsBase(): bool
    {
        // Remove base flag from all other currencies
        static::where('is_base', true)->update(['is_base' => false]);

        // Set this as base and exchange rate to 1
        return $this->update([
            'is_base' => true,
            'exchange_rate' => 1.0000,
        ]);
    }

    // Static Methods
    public static function getBaseCurrency(): ?self
    {
        return static::base()->first();
    }

    public static function getActiveCurrencies(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('code')->get();
    }

    public static function getByCode(string $code): ?self
    {
        return static::byCode($code)->first();
    }

    public static function getExchangeRate(string $fromCode, string $toCode): float
    {
        $fromCurrency = static::getByCode($fromCode);
        $toCurrency = static::getByCode($toCode);

        if (!$fromCurrency || !$toCurrency) {
            throw new \Exception('Currency not found');
        }

        // Convert to base first, then to target
        $baseAmount = 1.0 / $fromCurrency->exchange_rate;
        return $baseAmount * $toCurrency->exchange_rate;
    }

    public static function convert(float $amount, string $fromCode, string $toCode): float
    {
        $fromCurrency = static::getByCode($fromCode);
        $toCurrency = static::getByCode($toCode);

        if (!$fromCurrency || !$toCurrency) {
            throw new \Exception('Currency not found');
        }

        return $fromCurrency->convertTo($amount, $toCurrency);
    }
}
