<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'payroll_id',
        'report_type',
        'parameters',
        'frequency',
        'email_recipients',
        'is_active',
        'is_global',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['frequency_display'];

    /**
     * Get the user who created this schedule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the payroll for this schedule.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Scope query for active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query for due schedules.
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
                    ->where('next_run_at', '<=', now());
    }

    /**
     * Get frequency display.
     */
    public function getFrequencyDisplayAttribute(): string
    {
        return ucfirst($this->frequency);
    }

    /**
     * Calculate next run time based on frequency.
     */
    public function calculateNextRun(): void
    {
        $nextRun = match ($this->frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth()
        };

        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $nextRun,
        ]);
    }

    /**
     * Get supported frequencies.
     */
    public static function getSupportedFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'report_type' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'email_recipients' => 'nullable|string',
            'parameters' => 'nullable|array',
        ];
    }
}
