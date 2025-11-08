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

    public function containsEngineCapacity(int $capacity): bool
    {
        return $capacity >= $this->engine_capacity_min &&
               ($this->engine_capacity_max === null || $capacity <= $this->engine_capacity_max);
    }
}
