<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostBreakdownDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'cache_id',
        'category_name',
        'category_type',
        'zwg_amount',
        'usd_amount',
        'employee_count',
        'percentage_of_total',
    ];

    protected $casts = [
        'zwg_amount' => 'decimal:2',
        'usd_amount' => 'decimal:2',
        'employee_count' => 'integer',
        'percentage_of_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['total_amount', 'category_type_display'];

    /**
     * Get the cost analysis cache this detail belongs to.
     */
    public function cache(): BelongsTo
    {
        return $this->belongsTo(CostAnalysisCache::class, 'cache_id');
    }

    /**
     * Scope query by category type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('category_type', $type);
    }

    /**
     * Get total amount (ZWG + USD).
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->zwg_amount + $this->usd_amount;
    }

    /**
     * Get category type display.
     */
    public function getCategoryTypeDisplayAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->category_type));
    }
}
