<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'event',
        'severity',
        'details',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Severity levels
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Security event types
     */
    const EVENT_FAILED_LOGIN = 'failed_login';
    const EVENT_BRUTE_FORCE_ATTEMPT = 'brute_force_attempt';
    const EVENT_UNUSUAL_LOGIN_TIME = 'unusual_login_time';
    const EVENT_UNUSUAL_LOGIN_LOCATION = 'unusual_login_location';
    const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const EVENT_DATA_BREACH_ATTEMPT = 'data_breach_attempt';
    const EVENT_PERMISSION_VIOLATION = 'permission_violation';

    /**
     * Get the user associated with the security event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope query by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope query by event type.
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope query high severity events.
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope query by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope query recent events.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get available severity levels.
     */
    public static function getSeverityLevels(): array
    {
        return [
            self::SEVERITY_LOW,
            self::SEVERITY_MEDIUM,
            self::SEVERITY_HIGH,
            self::SEVERITY_CRITICAL,
        ];
    }

    /**
     * Get available event types.
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_FAILED_LOGIN,
            self::EVENT_BRUTE_FORCE_ATTEMPT,
            self::EVENT_UNUSUAL_LOGIN_TIME,
            self::EVENT_UNUSUAL_LOGIN_LOCATION,
            self::EVENT_UNAUTHORIZED_ACCESS,
            self::EVENT_DATA_BREACH_ATTEMPT,
            self::EVENT_PERMISSION_VIOLATION,
        ];
    }
}
