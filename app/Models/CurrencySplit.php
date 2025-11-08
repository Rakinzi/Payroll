<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrencySplit extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'center_id',
        'zwl_percentage',
        'usd_percentage',
        'effective_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'zwl_percentage' => 'decimal:2',
        'usd_percentage' => 'decimal:2',
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the cost center that owns this currency split.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id');
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'center_id' => 'required|uuid|exists:cost_centers,id',
            'zwl_percentage' => 'required|numeric|min:0|max:100',
            'usd_percentage' => 'required|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Scope query to a specific cost center.
     */
    public function scopeForCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Scope query to active splits.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to effective on a specific date.
     */
    public function scopeEffectiveOn($query, string $date)
    {
        return $query->where('effective_date', '<=', $date)
                     ->orderBy('effective_date', 'desc');
    }

    /**
     * Get the current effective currency split for a cost center.
     */
    public static function getCurrentSplit(string $centerId): ?self
    {
        return self::forCenter($centerId)
                   ->active()
                   ->effectiveOn(now()->toDateString())
                   ->first();
    }

    /**
     * Validate that percentages total 100.
     */
    public function validatePercentages(): bool
    {
        $total = $this->zwl_percentage + $this->usd_percentage;
        return abs($total - 100) < 0.01; // Allow for small floating point differences
    }

    /**
     * Boot method to validate percentages on save.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($currencySplit) {
            if (!$currencySplit->validatePercentages()) {
                throw new \InvalidArgumentException('Currency split percentages must total 100%');
            }
        });
    }

    /**
     * Get formatted percentage strings.
     */
    public function getFormattedZwlPercentageAttribute(): string
    {
        return number_format($this->zwl_percentage, 2) . '%';
    }

    public function getFormattedUsdPercentageAttribute(): string
    {
        return number_format($this->usd_percentage, 2) . '%';
    }
}
