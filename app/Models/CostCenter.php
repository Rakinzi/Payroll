<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'center_name',
        'center_code',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the employees for the cost center.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'center_id');
    }

    /**
     * Get the users for the cost center.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'center_id');
    }

    /**
     * Scope query to only active cost centers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the count of active employees in this cost center.
     */
    public function getActiveEmployeesCountAttribute(): int
    {
        return $this->employees()->active()->count();
    }
}
