<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        if ($this->previous_rate === null) {
            return null;
        }

        return (float) $this->rate - (float) $this->previous_rate;
    }

    public function getRateChangePercentageAttribute(): ?float
    {
        if ($this->previous_rate === null || $this->previous_rate == 0) {
            return null;
        }

        return (($this->rate - $this->previous_rate) / $this->previous_rate) * 100;
    }

    // Scopes
    public function scopeForCurrency($query, int $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }

    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }

    public function scopeApi($query)
    {
        return $query->where('source', 'api');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('effective_date', '>=', now()->subDays($days));
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
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

    public static function getHistoryForCurrency(int $currencyId, int $limit = 10)
    {
        return self::forCurrency($currencyId)
            ->with(['currency', 'updatedBy'])
            ->orderBy('effective_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getRateAtDate(int $currencyId, $date)
    {
        $history = self::forCurrency($currencyId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $history?->rate;
    }
}
