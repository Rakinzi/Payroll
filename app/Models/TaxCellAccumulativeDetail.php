<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCellAccumulativeDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cell_accumulative_id',
        'employee_id',
        'employee_name',
        'nat_id',
        'tax_bracket',
        'bracket_min',
        'bracket_max',
        'tax_rate',
        'ytd_income_in_bracket',
        'ytd_tax_in_bracket',
    ];

    protected $casts = [
        'bracket_min' => 'decimal:2',
        'bracket_max' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'ytd_income_in_bracket' => 'decimal:2',
        'ytd_tax_in_bracket' => 'decimal:2',
    ];

    /**
     * Get the tax cell accumulative that owns this detail.
     */
    public function cellAccumulative(): BelongsTo
    {
        return $this->belongsTo(TaxCellAccumulative::class, 'cell_accumulative_id');
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the bracket range display.
     */
    public function getBracketRangeAttribute(): string
    {
        if ($this->bracket_max === null) {
            return number_format($this->bracket_min, 2) . ' and above';
        }

        return number_format($this->bracket_min, 2) . ' - ' . number_format($this->bracket_max, 2);
    }

    /**
     * Get the effective rate for this income.
     */
    public function getEffectiveRateAttribute(): float
    {
        if ($this->ytd_income_in_bracket == 0) {
            return 0;
        }

        return ($this->ytd_tax_in_bracket / $this->ytd_income_in_bracket) * 100;
    }

    /**
     * Scope a query to only include details for a specific tax bracket.
     */
    public function scopeForBracket($query, string $bracket)
    {
        return $query->where('tax_bracket', $bracket);
    }

    /**
     * Scope a query to order by income in bracket.
     */
    public function scopeOrderByIncome($query, string $direction = 'desc')
    {
        return $query->orderBy('ytd_income_in_bracket', $direction);
    }

    /**
     * Scope a query to order by tax rate.
     */
    public function scopeOrderByRate($query, string $direction = 'asc')
    {
        return $query->orderBy('tax_rate', $direction);
    }
}
