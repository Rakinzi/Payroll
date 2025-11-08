<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThirdPartyReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'report_type',
        'period_start',
        'period_end',
        'currency',
        'total_amount',
        'submission_status',
        'submission_reference',
        'submitted_at',
        'generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['report_type_display', 'status_display', 'can_submit'];

    /**
     * Get the payroll this report belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Get the user who generated this report.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get all report details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ThirdPartyDetail::class, 'report_id');
    }

    /**
     * Scope query by report type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope query by submission status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('submission_status', $status);
    }

    /**
     * Get report type display.
     */
    public function getReportTypeDisplayAttribute(): string
    {
        return match ($this->report_type) {
            'standard_levy' => 'Standard Levy Report',
            'zimdef' => 'ZIMDEF Contributions',
            'zimra_p2' => 'ZIMRA P2 Tax Report',
            default => 'Unknown Report'
        };
    }

    /**
     * Get status display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->submission_status);
    }

    /**
     * Check if report can be submitted.
     */
    public function getCanSubmitAttribute(): bool
    {
        return $this->submission_status === 'draft' && $this->details()->count() > 0;
    }

    /**
     * Mark report as submitted.
     */
    public function markAsSubmitted(string $reference): void
    {
        $this->update([
            'submission_status' => 'submitted',
            'submission_reference' => $reference,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Check if report can be accessed by user.
     */
    public function canAccess(User $user): bool
    {
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        return $this->generated_by === $user->id;
    }

    /**
     * Get supported report types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            'standard_levy' => 'Standard Levy Report',
            'zimdef' => 'ZIMDEF Contributions',
            'zimra_p2' => 'ZIMRA P2 Tax Report',
        ];
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'report_type' => 'required|in:standard_levy,zimdef,zimra_p2',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'currency' => 'required|in:ZWG,USD',
        ];
    }
}
