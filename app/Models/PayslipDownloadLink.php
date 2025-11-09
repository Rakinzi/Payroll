<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @property int $link_id
 * @property string $payslip_id
 * @property string $employee_id
 * @property string $token
 * @property string $download_method
 * @property Carbon $expires_at
 * @property bool $is_used
 * @property Carbon|null $accessed_at
 * @property string|null $access_ip
 * @property string|null $access_user_agent
 * @property int $access_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Payslip $payslip
 * @property-read Employee $employee
 */
class PayslipDownloadLink extends Model
{
    protected $table = 'payslip_download_links';
    protected $primaryKey = 'link_id';

    protected $fillable = [
        'payslip_id',
        'employee_id',
        'token',
        'download_method',
        'expires_at',
        'is_used',
        'accessed_at',
        'access_ip',
        'access_user_agent',
        'access_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accessed_at' => 'datetime',
        'is_used' => 'boolean',
        'access_count' => 'integer',
    ];

    // Relationships
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id', 'id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    // Business Methods

    /**
     * Generate a new secure download link for a payslip
     */
    public static function generate(
        string $payslipId,
        string $employeeId,
        string $method = 'link',
        int $expiresInHours = 168 // 7 days default
    ): self {
        return self::create([
            'payslip_id' => $payslipId,
            'employee_id' => $employeeId,
            'token' => Str::random(64),
            'download_method' => $method,
            'expires_at' => Carbon::now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Check if the link is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        return !$this->is_used
            && $this->expires_at->isFuture()
            && $this->access_count < 5; // Max 5 accesses
    }

    /**
     * Check if the link is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark the link as accessed
     */
    public function markAccessed(string $ip, string $userAgent): void
    {
        $this->increment('access_count');
        $this->update([
            'accessed_at' => Carbon::now(),
            'access_ip' => $ip,
            'access_user_agent' => $userAgent,
        ]);
    }

    /**
     * Mark the link as used (downloaded)
     */
    public function markUsed(): void
    {
        $this->update([
            'is_used' => true,
            'accessed_at' => Carbon::now(),
        ]);
    }

    /**
     * Get the full download URL
     */
    public function getDownloadUrl(): string
    {
        return route('payslips.secure-download', ['token' => $this->token]);
    }

    /**
     * Get human-readable expiry time
     */
    public function getExpiryDisplay(): string
    {
        if ($this->isExpired()) {
            return 'Expired ' . $this->expires_at->diffForHumans();
        }

        return 'Expires ' . $this->expires_at->diffForHumans();
    }

    // Scopes

    /**
     * @param Builder<PayslipDownloadLink> $query
     * @return Builder<PayslipDownloadLink>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->where('access_count', '<', 5);
    }

    /**
     * @param Builder<PayslipDownloadLink> $query
     * @return Builder<PayslipDownloadLink>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    /**
     * @param Builder<PayslipDownloadLink> $query
     * @return Builder<PayslipDownloadLink>
     */
    public function scopeByMethod(Builder $query, string $method): Builder
    {
        return $query->where('download_method', $method);
    }

    /**
     * @param Builder<PayslipDownloadLink> $query
     * @return Builder<PayslipDownloadLink>
     */
    public function scopeForPayslip(Builder $query, string $payslipId): Builder
    {
        return $query->where('payslip_id', $payslipId);
    }

    /**
     * @param Builder<PayslipDownloadLink> $query
     * @return Builder<PayslipDownloadLink>
     */
    public function scopeForEmployee(Builder $query, string $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    // Static Methods

    /**
     * Find by token
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }

    /**
     * Clean up expired links
     */
    public static function cleanupExpired(): int
    {
        return self::expired()
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->delete();
    }
}
