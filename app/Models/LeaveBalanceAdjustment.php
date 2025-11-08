<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceAdjustment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'leave_balance_id',
        'old_balance_bf',
        'new_balance_bf',
        'old_balance_cf',
        'new_balance_cf',
        'adjusted_by',
        'adjustment_reason',
    ];

    protected $casts = [
        'old_balance_bf' => 'decimal:3',
        'new_balance_bf' => 'decimal:3',
        'old_balance_cf' => 'decimal:3',
        'new_balance_cf' => 'decimal:3',
    ];

    /**
     * Get the leave balance that owns the adjustment.
     */
    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    /**
     * Get the user who made the adjustment.
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Get the adjustment amount for BF.
     */
    public function getAdjustmentBfAttribute(): float
    {
        return $this->new_balance_bf - $this->old_balance_bf;
    }

    /**
     * Get the adjustment amount for CF.
     */
    public function getAdjustmentCfAttribute(): float
    {
        return $this->new_balance_cf - $this->old_balance_cf;
    }

    /**
     * Scope a query to only include adjustments by a specific user.
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('adjusted_by', $userId);
    }

    /**
     * Scope a query to only include adjustments for a specific leave balance.
     */
    public function scopeForBalance($query, string $leaveBalanceId)
    {
        return $query->where('leave_balance_id', $leaveBalanceId);
    }
}
