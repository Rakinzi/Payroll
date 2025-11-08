<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarianceDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'analysis_id',
        'transaction_code_id',
        'item_name',
        'baseline_amount',
        'comparison_amount',
        'variance_amount',
        'variance_percentage',
    ];

    protected $casts = [
        'baseline_amount' => 'decimal:2',
        'comparison_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['is_positive'];

    /**
     * Get the variance analysis this detail belongs to.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(VarianceAnalysis::class, 'analysis_id');
    }

    /**
     * Get the transaction code.
     */
    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class, 'transaction_code_id');
    }

    /**
     * Check if variance is positive.
     */
    public function getIsPositiveAttribute(): bool
    {
        return $this->variance_amount >= 0;
    }
}
