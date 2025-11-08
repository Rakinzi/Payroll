<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyBankDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
        'branch_code',
        'swift_code',
        'currency',
        'is_primary',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWG = 'ZWG';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }
}
