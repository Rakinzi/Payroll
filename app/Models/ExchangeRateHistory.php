<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $history_id
 * @property int $currency_id
 * @property string $rate
 * @property string $previous_rate
 * @property string $source
 * @property int|null $updated_by
 * @property string|null $notes
 * @property \Carbon\Carbon $effective_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read float|null $rate_change
 * @property-read float|null $rate_change_percentage
 * @property-read Currency $currency
 * @property-read User|null $updatedBy
 */
class ExchangeRateHistory extends Model
{
    protected $table = 'exchange_rate_history';
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'currency_id',
        'rate',
        'previous_rate',
        'source',
        'updated_by',
        'notes',
        'effective_date',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'previous_rate' => 'decimal:4',
        'effective_date' => 'datetime',
    ];

    protected $appends = [
        'rate_change',
        'rate_change_percentage',
    ];

    // Relationships
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'currency_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    // Accessors
    public function getRateChangeAttribute(): ?float
    {
        if (empty($this->previous_rate)) {
            return null;
        }

        return (float) $this->rate - (float) $this->previous_rate;
    }

    public function getRateChangePercentageAttribute(): ?float
    {
        if (empty($this->previous_rate) || (float) $this->previous_rate == 0) {
            return null;
        }

        $rate = (float) $this->rate;
        $prevRate = (float) $this->previous_rate;
        return (($rate - $prevRate) / $prevRate) * 100;
    }

    // Scopes
    /**
     * @param Builder<ExchangeRateHistory> $query
     * @return Builder<ExchangeRateHistory>
     */
    public function scopeForCurrency(Builder $query, int $currencyId): Builder
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * @param Builder<ExchangeRateHistory> $query
     * @return Builder<ExchangeRateHistory>
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('source', 'manual');
    }

    /**
     * @param Builder<ExchangeRateHistory> $query
     * @return Builder<ExchangeRateHistory>
     */
    public function scopeApi(Builder $query): Builder
    {
        return $query->where('source', 'api');
    }

    /**
     * @param Builder<ExchangeRateHistory> $query
     * @return Builder<ExchangeRateHistory>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('effective_date', '>=', now()->subDays($days));
    }

    /**
     * @param Builder<ExchangeRateHistory> $query
     * @param mixed $startDate
     * @param mixed $endDate
     * @return Builder<ExchangeRateHistory>
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('effective_date', [$startDate, $endDate]);
    }

    // Static Methods
    public static function recordRateChange(
        int $currencyId,
        float $newRate,
        float $previousRate,
        string $source = 'manual',
        ?int $userId = null,
        ?string $notes = null
    ): self {
        return self::create([
            'currency_id' => $currencyId,
            'rate' => $newRate,
            'previous_rate' => $previousRate,
            'source' => $source,
            'updated_by' => $userId,
            'notes' => $notes,
            'effective_date' => now(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ExchangeRateHistory>
     */
    public static function getHistoryForCurrency(int $currencyId, int $limit = 10)
    {
        return self::forCurrency($currencyId)
            ->with(['currency', 'updatedBy'])
            ->orderBy('effective_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @param mixed $date
     */
    public static function getRateAtDate(int $currencyId, $date): ?string
    {
        $history = self::forCurrency($currencyId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $history?->rate;
    }
}
