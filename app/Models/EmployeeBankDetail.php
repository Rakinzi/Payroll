<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class EmployeeBankDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_number',
        'account_name',
        'account_type',
        'account_currency',
        'capacity',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'capacity' => 'decimal:2',
    ];

    /**
     * Get the employee that owns the bank detail.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Encrypt the account number when setting.
     */
    public function setAccountNumberAttribute($value)
    {
        if ($value) {
            $this->attributes['account_number'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt the account number when getting.
     */
    public function getAccountNumberAttribute($value)
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
     * Get the masked account number for display.
     * Shows only last 4 digits (e.g., ****1234)
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $accountNumber = $this->account_number;

        if (!$accountNumber) {
            return '';
        }

        $length = strlen($accountNumber);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($accountNumber, -4);
    }

    /**
     * Scope query to only active bank details.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default bank account.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope query by currency.
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('account_currency', $currency);
    }
}
