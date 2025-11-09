<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefaultTransaction extends Model
{
    protected $table = 'default_transactions';
    protected $primaryKey = 'default_id';

    protected $fillable = [
        'code_id',
        'period_id',
        'center_id',
        'transaction_effect',
        'employee_amount',
        'employer_amount',
        'hours_worked',
        'transaction_currency',
    ];

    protected $casts = [
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_amount',
        'effect_display',
    ];

    /**
     * Get the transaction code.
     */
    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class, 'code_id', 'code_id');
    }

    /**
     * Get the accounting period.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id', 'period_id');
    }

    /**
     * Get the cost center.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id', 'id');
    }

    /**
     * Get total amount (employee + employer).
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->employee_amount + $this->employer_amount;
    }

    /**
     * Get effect display (Addition/Deduction).
     */
    public function getEffectDisplayAttribute(): string
    {
        return $this->transaction_effect === '+' ? 'Addition' : 'Deduction';
    }

    /**
     * Scope to filter by period.
     */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope to filter by center.
     */
    public function scopeForCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Scope to filter by current period.
     */
    public function scopeCurrentPeriod($query)
    {
        $currentPeriod = AccountingPeriod::current()->first();
        if ($currentPeriod) {
            return $query->where('period_id', $currentPeriod->period_id);
        }
        return $query->whereRaw('1 = 0'); // Return empty result
    }

    /**
     * Check if transaction can be modified by user.
     */
    public function canBeModifiedBy(User $user): bool
    {
        // Only allow modification for current period and user's center
        return $this->period->is_current &&
               ($user->hasRole('admin') || $this->center_id === $user->center_id);
    }

    /**
     * Validate transaction uniqueness.
     */
    public function validateUniqueness(): bool
    {
        $existing = self::where('code_id', $this->code_id)
                       ->where('period_id', $this->period_id)
                       ->where('center_id', $this->center_id)
                       ->where('transaction_currency', $this->transaction_currency)
                       ->when($this->exists, function ($query) {
                           return $query->where('default_id', '!=', $this->default_id);
                       })
                       ->exists();

        return !$existing;
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return $this->transaction_currency === 'USD' ? '$' : 'ZWG';
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'code_id' => 'required|exists:transaction_codes,code_id',
            'period_id' => 'required|exists:payroll_accounting_periods,period_id',
            'center_id' => 'required|exists:cost_centers,id',
            'transaction_effect' => 'required|in:+,-',
            'employee_amount' => 'required|numeric|min:0',
            'employer_amount' => 'nullable|numeric|min:0',
            'hours_worked' => 'nullable|numeric|min:0',
            'transaction_currency' => 'required|in:ZWG,USD',
        ];
    }
}
