<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionCode extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code_number',
        'code_name',
        'code_category',
        'is_benefit',
        'code_amount',
        'minimum_threshold',
        'maximum_threshold',
        'code_percentage',
        'is_editable',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_benefit' => 'boolean',
        'is_editable' => 'boolean',
        'is_active' => 'boolean',
        'code_amount' => 'decimal:2',
        'minimum_threshold' => 'decimal:2',
        'maximum_threshold' => 'decimal:2',
        'code_percentage' => 'decimal:4',
    ];

    /**
     * Code categories
     */
    const CATEGORY_EARNING = 'Earning';
    const CATEGORY_DEDUCTION = 'Deduction';
    const CATEGORY_CONTRIBUTION = 'Contribution';

    /**
     * Get NEC grades using this transaction code.
     */
    public function necGrades(): HasMany
    {
        return $this->hasMany(NECGrade::class, 't_code_id');
    }

    /**
     * Scope a query to only include active transaction codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include editable transaction codes.
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('code_category', $category);
    }

    /**
     * Scope a query to only include benefits.
     */
    public function scopeBenefits($query)
    {
        return $query->where('is_benefit', true);
    }

    /**
     * Check if this is a system-defined code.
     */
    public function isSystem(): bool
    {
        return !$this->is_editable;
    }
}
