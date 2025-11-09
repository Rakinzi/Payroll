<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PayslipNotification extends Model
{
    protected $table = 'payslip_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'payslip_id',
        'employee_id',
        'sent_by',
        'channel',
        'recipient',
        'message',
        'status',
        'error_message',
        'external_id',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by', 'id');
    }

    // Business Methods

    /**
     * Create a new notification record
     */
    public static function createNotification(
        string $payslipId,
        string $employeeId,
        int $sentBy,
        string $channel,
        string $recipient,
        ?string $message = null
    ): self {
        return self::create([
            'payslip_id' => $payslipId,
            'employee_id' => $employeeId,
            'sent_by' => $sentBy,
            'channel' => $channel,
            'recipient' => $recipient,
            'message' => $message,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark as sent
     */
    public function markAsSent(?string $externalId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'external_id' => $externalId,
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get status display
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'delivered' => 'Delivered',
            'read' => 'Read',
            default => 'Unknown',
        };
    }

    /**
     * Get channel icon/label
     */
    public function getChannelDisplayAttribute(): string
    {
        return match ($this->channel) {
            'email' => 'Email',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            default => 'Unknown',
        };
    }

    /**
     * Check if notification was successful
     */
    public function wasSuccessful(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'read']);
    }

    // Scopes

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPayslip($query, string $payslipId)
    {
        return $query->where('payslip_id', $payslipId);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'read']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Static Methods

    /**
     * Get statistics for a payslip
     */
    public static function getStatsForPayslip(string $payslipId): array
    {
        $notifications = self::forPayslip($payslipId)->get();

        return [
            'total' => $notifications->count(),
            'email' => $notifications->where('channel', 'email')->count(),
            'sms' => $notifications->where('channel', 'sms')->count(),
            'whatsapp' => $notifications->where('channel', 'whatsapp')->count(),
            'sent' => $notifications->whereIn('status', ['sent', 'delivered', 'read'])->count(),
            'failed' => $notifications->where('status', 'failed')->count(),
            'pending' => $notifications->where('status', 'pending')->count(),
        ];
    }
}
