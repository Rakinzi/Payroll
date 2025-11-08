<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_type',
        'file_name',
        'file_path',
        'total_rows',
        'processed_rows',
        'error_rows',
        'status',
        'imported_by',
        'notes',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'error_rows' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who imported this session.
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Get the errors for this import session.
     */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class, 'session_id');
    }

    /**
     * Scope query to completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope query to failed sessions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope query to processing sessions.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope query by import type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('import_type', $type);
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Check if the session has errors.
     */
    public function getHasErrorsAttribute(): bool
    {
        return $this->error_rows > 0;
    }

    /**
     * Get success percentage.
     */
    public function getSuccessPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round((($this->total_rows - $this->error_rows) / $this->total_rows) * 100, 2);
    }

    /**
     * Increment processed rows count.
     */
    public function incrementProcessed(): void
    {
        $this->increment('processed_rows');
    }

    /**
     * Increment error rows count.
     */
    public function incrementErrors(): void
    {
        $this->increment('error_rows');
    }

    /**
     * Mark the session as completed.
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark the session as failed.
     */
    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Mark the session as preview ready.
     */
    public function markPreview(): void
    {
        $this->update(['status' => 'preview']);
    }

    /**
     * Mark the session as processing.
     */
    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Check if session can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === 'preview' && $this->error_rows < $this->total_rows;
    }

    /**
     * Get supported import types.
     */
    public static function getSupportedTypes(): array
    {
        return [
            'employees' => 'Employee Master Data',
            'salaries' => 'Salary Data',
            'transactions' => 'Transaction Data',
            'banking' => 'Banking Information',
        ];
    }
}
