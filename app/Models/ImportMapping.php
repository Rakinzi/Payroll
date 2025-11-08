<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_type',
        'spreadsheet_column',
        'database_field',
        'data_type',
        'is_required',
        'validation_rules',
        'transformation_rules',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'validation_rules' => 'array',
        'transformation_rules' => 'array',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope query to active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by import type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('import_type', $type);
    }

    /**
     * Scope query to required fields.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get mappings for a specific import type.
     */
    public static function getMappingsForType(string $type): array
    {
        return static::active()
            ->forType($type)
            ->orderBy('display_order')
            ->get()
            ->toArray();
    }

    /**
     * Get validation rules as an array.
     */
    public function getValidationRulesArrayAttribute(): array
    {
        return $this->validation_rules ?? [];
    }

    /**
     * Get transformation rules as an array.
     */
    public function getTransformationRulesArrayAttribute(): array
    {
        return $this->transformation_rules ?? [];
    }
}
