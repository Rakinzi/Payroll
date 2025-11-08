<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class CompanyBankDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'center_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_number',
        'account_type',
        'account_currency',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['masked_account_number'];

    /**
     * Account types
     */
    const TYPE_CURRENT = 'Current';
    const TYPE_NOSTRO = 'Nostro';
    const TYPE_FCA = 'FCA';

    /**
     * Currencies
     */
    const CURRENCY_USD = 'USD';
    const CURRENCY_ZWL = 'ZWL';
    const CURRENCY_RTGS = 'RTGS';

    /**
     * Get the cost center that owns the bank detail.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id');
    }

    /**
     * Encrypt account number when setting.
     */
    public function setAccountNumberAttribute($value): void
    {
        if ($value) {
            $this->attributes['account_number'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt account number when getting.
     */
    public function getAccountNumberAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get masked account number for display.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $accountNumber = $this->account_number;

        if (!$accountNumber) {
            return '****';
        }

        $length = strlen($accountNumber);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $visibleDigits = 4;
        $masked = str_repeat('*', $length - $visibleDigits) . substr($accountNumber, -$visibleDigits);

        return $masked;
    }

    /**
     * Scope query to only active bank details.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default bank detail.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope query by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('account_currency', $currency);
    }

    /**
     * Scope query by account type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope query by cost center.
     */
    public function scopeByCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false, ?string $currentId = null): array
    {
        $rules = [
            'center_id' => 'required|exists:cost_centers,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'required|string|max:10',
            'account_number' => 'required|string|min:10|max:20',
            'account_type' => 'required|in:Current,Nostro,FCA',
            'account_currency' => 'required|in:RTGS,ZWL,USD',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];

        return $rules;
    }

    /**
     * Boot method to handle default account logic.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($bankDetail) {
            if ($bankDetail->is_default) {
                // Remove default flag from other accounts for this center
                static::where('center_id', $bankDetail->center_id)
                    ->where('id', '!=', $bankDetail->id ?? '')
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get available account types.
     */
    public static function getAccountTypes(): array
    {
        return [
            self::TYPE_CURRENT,
            self::TYPE_NOSTRO,
            self::TYPE_FCA,
        ];
    }

    /**
     * Get available currencies.
     */
    public static function getCurrencies(): array
    {
        return [
            self::CURRENCY_USD,
            self::CURRENCY_ZWL,
            self::CURRENCY_RTGS,
        ];
    }
}
