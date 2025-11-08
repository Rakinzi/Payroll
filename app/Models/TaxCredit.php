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
}
