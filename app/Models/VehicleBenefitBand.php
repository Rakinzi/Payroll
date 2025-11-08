<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleBenefitBand extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'engine_capacity_min',
        'engine_capacity_max',
        'benefit_amount',
        'currency',
        'period',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'engine_capacity_min' => 'integer',
        'engine_capacity_max' => 'integer',
        'benefit_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWG = 'ZWG';

    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_ANNUAL = 'annual';

    /**
     * Scope query to active benefit bands.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to specific currency.
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope query to specific period.
     */
    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope query ordered by engine capacity.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('engine_capacity_min');
    }

    /**
     * Check if this band contains the given engine capacity.
     */
    public function containsEngineCapacity(int $capacity): bool
    {
        return $capacity >= $this->engine_capacity_min &&
               ($this->engine_capacity_max === null || $capacity <= $this->engine_capacity_max);
    }

    /**
     * Get formatted capacity range.
     */
    public function getCapacityRangeAttribute(): string
    {
        $min = number_format($this->engine_capacity_min);
        $max = $this->engine_capacity_max ? number_format($this->engine_capacity_max) : 'Above';
        return "{$min} - {$max} cc";
    }

    /**
     * Get formatted benefit amount.
     */
    public function getFormattedBenefitAmountAttribute(): string
    {
        return number_format($this->benefit_amount, 2);
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'engine_capacity_min' => 'required|integer|min:0',
            'engine_capacity_max' => 'nullable|integer|min:0',
            'benefit_amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWG',
            'period' => 'required|in:monthly,annual',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Find benefit band for specific capacity.
     */
    public static function findBandForCapacity(int $capacity, string $currency = 'USD', string $period = 'monthly')
    {
        return self::active()
            ->currency($currency)
            ->period($period)
            ->where('engine_capacity_min', '<=', $capacity)
            ->where(function ($query) use ($capacity) {
                $query->whereNull('engine_capacity_max')
                    ->orWhere('engine_capacity_max', '>=', $capacity);
            })
            ->ordered()
            ->first();
    }

    /**
     * Get supported currencies.
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            self::CURRENCY_USD,
            self::CURRENCY_ZWG,
        ];
    }

    /**
     * Get supported periods.
     */
    public static function getSupportedPeriods(): array
    {
        return [
            self::PERIOD_MONTHLY,
            self::PERIOD_ANNUAL,
        ];
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($band) {
            // Validate that max capacity is greater than min capacity
            if ($band->engine_capacity_max !== null && $band->engine_capacity_max <= $band->engine_capacity_min) {
                throw new \InvalidArgumentException('Maximum engine capacity must be greater than minimum');
            }

            // Check for overlapping ranges with same currency and period
            $overlapping = static::where('id', '!=', $band->id ?? '')
                ->where('currency', $band->currency)
                ->where('period', $band->period)
                ->where('is_active', true)
                ->where(function ($query) use ($band) {
                    // Check if ranges overlap
                    $query->where(function ($q) use ($band) {
                        // New band's min is within existing range
                        $q->where('engine_capacity_min', '<=', $band->engine_capacity_min)
                            ->where(function ($subQ) use ($band) {
                                $subQ->whereNull('engine_capacity_max')
                                    ->orWhere('engine_capacity_max', '>=', $band->engine_capacity_min);
                            });
                    })->orWhere(function ($q) use ($band) {
                        // New band's max is within existing range (if max is set)
                        if ($band->engine_capacity_max !== null) {
                            $q->where('engine_capacity_min', '<=', $band->engine_capacity_max)
                                ->where(function ($subQ) use ($band) {
                                    $subQ->whereNull('engine_capacity_max')
                                        ->orWhere('engine_capacity_max', '>=', $band->engine_capacity_max);
                                });
                        }
                    })->orWhere(function ($q) use ($band) {
                        // Existing range is completely within new band
                        $q->where('engine_capacity_min', '>=', $band->engine_capacity_min);
                        if ($band->engine_capacity_max !== null) {
                            $q->where(function ($subQ) use ($band) {
                                $subQ->whereNull('engine_capacity_max')
                                    ->orWhere('engine_capacity_max', '<=', $band->engine_capacity_max);
                            });
                        }
                    });
                })
                ->exists();

            if ($overlapping) {
                throw new \InvalidArgumentException('Vehicle benefit band ranges cannot overlap with existing bands for the same currency and period');
            }
        });
    }
}
