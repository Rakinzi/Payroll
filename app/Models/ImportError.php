<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'row_number',
        'column_name',
        'error_message',
        'raw_value',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the import session that owns this error.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ImportSession::class, 'session_id');
    }

    /**
     * Get the error location as a string.
     */
    public function getErrorLocationAttribute(): string
    {
        if ($this->column_name) {
            return "Row {$this->row_number}, Column {$this->column_name}";
        }

        return "Row {$this->row_number}";
    }

    /**
     * Get formatted error message with location.
     */
    public function getFormattedMessageAttribute(): string
    {
        return "{$this->error_location}: {$this->error_message}";
    }
}
