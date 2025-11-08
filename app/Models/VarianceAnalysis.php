<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VarianceAnalysis extends Model
{
    use HasUuids;

    protected $table = 'variance_analysis';

    protected $fillable = [
        'payroll_id',
        'generated_by',
        'analysis_type',
        'baseline_period',
        'comparison_period',
        'total_variance_zwg',
        'total_variance_usd',
        'variance_percentage',
        'generated_at',
    ];

    protected $casts = [
        'baseline_period' => 'date',
        'comparison_period' => 'date',
        'total_variance_zwg' => 'decimal:2',
        'total_variance_usd' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['analysis_display', 'is_positive_variance'];

    /**
     * Get the payroll this analysis belongs to.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    /**
     * Get the user who generated this analysis.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get all variance details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(VarianceDetail::class, 'analysis_id');
    }

    /**
     * Scope query by analysis type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('analysis_type', $type);
    }

    /**
     * Get analysis display.
     */
    public function getAnalysisDisplayAttribute(): string
    {
        return ucfirst($this->analysis_type) . ' Variance Analysis';
    }

    /**
     * Check if variance is positive.
     */
    public function getIsPositiveVarianceAttribute(): bool
    {
        return $this->total_variance_zwg >= 0 && $this->total_variance_usd >= 0;
    }

    /**
     * Check if analysis can be accessed by user.
     */
    public function canAccess(User $user): bool
    {
        if ($user->hasPermissionTo('access all centers')) {
            return true;
        }

        return $this->generated_by === $user->id;
    }

    /**
     * Get supported analysis types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            'summary' => 'Summary Variance Analysis',
            'detailed' => 'Detailed Variance Analysis',
        ];
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'payroll_id' => 'required|exists:payrolls,id',
            'analysis_type' => 'required|in:summary,detailed',
            'baseline_period' => 'required|date',
            'comparison_period' => 'required|date|different:baseline_period',
        ];
    }
}
