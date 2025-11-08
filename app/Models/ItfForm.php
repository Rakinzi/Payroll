<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItfForm extends Model
{
    use HasUuids;

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'form_type',
        'tax_year',
        'currency',
        'total_gross_income',
        'total_taxable_income',
        'total_tax_deducted',
        'generated_at',
    ];

    protected $casts = [
        'tax_year' => 'integer',
        'total_gross_income' => 'decimal:2',
        'total_taxable_income' => 'decimal:2',
        'total_tax_deducted' => 'decimal:2',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['form_display', 'employee_count'];

    /**
     * Get the payroll this form belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Get the user who generated this form.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get all form details (employee records).
     */
    public function details(): HasMany
    {
        return $this->hasMany(ItfFormDetail::class, 'form_id');
    }

    /**
     * Scope query by form type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('form_type', $type);
    }

    /**
     * Scope query by tax year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    /**
     * Get form display.
     */
    public function getFormDisplayAttribute(): string
    {
        return "ITF {$this->form_type} - {$this->tax_year} ({$this->currency})";
    }

    /**
     * Get employee count.
     */
    public function getEmployeeCountAttribute(): int
    {
        return $this->details()->count();
    }

    /**
     * Check if form can be accessed by user.
     */
    public function canAccess(User $user): bool
    {
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        return $this->generated_by === $user->id;
    }

    /**
     * Get supported form types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            'ITF16' => 'ITF 16 - Tax Certificate',
            'ITF36' => 'ITF 36 - Annual Return of Emoluments',
        ];
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'form_type' => 'required|in:ITF16,ITF36',
            'tax_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'currency' => 'required|in:ZWG,USD',
        ];
    }
}
