<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'payslip_id',
        'transaction_code_id',
        'description',
        'transaction_type',
        'display_order',
        'amount_zwg',
        'amount_usd',
        'is_taxable',
        'is_recurring',
        'is_manual',
        'notes',
        // Calculation fields
        'days',
        'hours',
        'rate',
        'quantity',
        'calculation_basis',
        'is_calculated',
        'manual_override',
        'calculation_metadata',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'amount_zwg' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
        'is_manual' => 'boolean',
        // Calculation field casts
        'days' => 'decimal:2',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'quantity' => 'decimal:2',
        'is_calculated' => 'boolean',
        'manual_override' => 'boolean',
        'calculation_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['type_display', 'total_amount'];

    /**
     * Get the payslip this transaction belongs to.
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    /**
     * Get the transaction code.
     */
    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class, 'transaction_code_id');
    }

    /**
     * Scope query to earnings only.
     */
    public function scopeEarnings($query)
    {
        return $query->where('transaction_type', 'earning');
    }

    /**
     * Scope query to deductions only.
     */
    public function scopeDeductions($query)
    {
        return $query->where('transaction_type', 'deduction');
    }

    /**
     * Scope query to taxable transactions only.
     */
    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    /**
     * Scope query to manual transactions only.
     */
    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    /**
     * Get transaction type display.
     */
    public function getTypeDisplayAttribute(): string
    {
        return ucfirst($this->transaction_type);
    }

    /**
     * Get total amount (ZWG + USD in display format).
     */
    public function getTotalAmountAttribute(): string
    {
        $parts = [];

        if ($this->amount_zwg > 0) {
            $parts[] = 'ZWG ' . number_format($this->amount_zwg, 2);
        }

        if ($this->amount_usd > 0) {
            $parts[] = 'USD ' . number_format($this->amount_usd, 2);
        }

        return implode(' + ', $parts) ?: 'ZWG 0.00';
    }

    /**
     * Check if transaction is an earning.
     */
    public function isEarning(): bool
    {
        return $this->transaction_type === 'earning';
    }

    /**
     * Check if transaction is a deduction.
     */
    public function isDeduction(): bool
    {
        return $this->transaction_type === 'deduction';
    }

    /**
     * Validation rules for transactions.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payslip_id' => 'required|exists:payslips,id',
            'transaction_code_id' => 'nullable|exists:transaction_codes,id',
            'description' => 'required|string|max:255',
            'transaction_type' => 'required|in:earning,deduction',
            'amount_zwg' => 'nullable|numeric|min:0',
            'amount_usd' => 'nullable|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'is_recurring' => 'nullable|boolean',
            'is_manual' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            // Calculation fields
            'days' => 'nullable|numeric|min:0',
            'hours' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'calculation_basis' => 'nullable|in:days,hours,amount,percentage',
            'is_calculated' => 'nullable|boolean',
            'manual_override' => 'nullable|boolean',
            'calculation_metadata' => 'nullable|array',
        ];
    }
}
