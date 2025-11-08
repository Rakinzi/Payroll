<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'emp_system_id',
        'firstname',
        'surname',
        'othername',
        'emp_email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'center_id',
        'is_active',
        'is_ex',
        'is_ex_on',
        'hire_date',
        'department',
        'position',
        'base_salary',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate employee ID on create if not provided
        static::creating(function ($employee) {
            if (empty($employee->emp_system_id)) {
                $employee->emp_system_id = static::generateEmployeeId();
            }
        });

        // Auto-generate employee ID on update if cleared/empty
        static::updating(function ($employee) {
            if (empty($employee->emp_system_id)) {
                $employee->emp_system_id = static::generateEmployeeId();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_ex' => 'boolean',
            'is_ex_on' => 'date',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    /**
     * Get the cost center that the employee belongs to.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id');
    }

    /**
     * Get the user account associated with the employee.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    /**
     * Scope query to only active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_ex', false)
                    ->orWhereNull('is_ex_on')
                    ->orWhere('is_ex_on', '>', now());
            });
    }

    /**
     * Scope query to only ex-employees.
     */
    public function scopeExEmployees($query)
    {
        return $query->where('is_ex', true)
            ->where('is_ex_on', '<=', now());
    }

    /**
     * Scope query to employees in a specific cost center.
     */
    public function scopeInCenter($query, string $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->firstname . ' ';

        if ($this->othername) {
            $name .= $this->othername . ' ';
        }

        $name .= $this->surname;

        return $name;
    }

    /**
     * Check if the employee is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->is_ex && $this->is_ex_on && $this->is_ex_on <= now()) {
            return false;
        }

        return true;
    }

    /**
     * Generate a unique employee ID.
     * Format: EMP-YYYY-NNNN (e.g., EMP-2025-0001)
     */
    public static function generateEmployeeId(): string
    {
        $year = now()->year;
        $prefix = "EMP-{$year}-";

        // Get the last employee created this year
        $lastEmployee = static::where('emp_system_id', 'LIKE', "{$prefix}%")
            ->orderBy('emp_system_id', 'desc')
            ->first();

        if ($lastEmployee) {
            // Extract the number from the last ID and increment
            $lastNumber = (int) substr($lastEmployee->emp_system_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // First employee this year
            $newNumber = 1;
        }

        // Pad with zeros to make it 4 digits
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
