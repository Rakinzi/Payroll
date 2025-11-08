<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        // Personal Information
        'title',
        'firstname',
        'surname',
        'othername',
        'nationality',
        'nat_id',
        'nassa_number',
        'gender',
        'date_of_birth',
        'marital_status',
        // Contact Information
        'home_address',
        'city',
        'country',
        'phone',
        'emp_email',
        'personal_email_address',
        // Identification
        'passport',
        'driver_license',
        // Employment Information
        'hire_date',
        'department_id',
        'position_id',
        'occupation_id',
        'paypoint_id',
        'center_id',
        'average_working_days',
        'working_hours',
        'payment_basis',
        'payment_method',
        // Compensation & Benefits
        'basic_salary',
        'basic_salary_usd',
        'leave_entitlement',
        'leave_accrual',
        // Tax Configuration
        'tax_directives',
        'disability_status',
        'dependents',
        'vehicle_engine_capacity',
        // Currency Splitting
        'zwl_percentage',
        'usd_percentage',
        // NEC Integration
        'nec_grade_id',
        // Role & Access
        'emp_role',
        // Status & Lifecycle
        'is_active',
        'is_ex',
        'is_ex_on',
        'employment_status',
        'discharge_notes',
        // Audit
        'last_login_time',
        'last_login_ip',
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
            'disability_status' => 'boolean',
            'is_ex_on' => 'date',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'last_login_time' => 'datetime',
            'basic_salary' => 'decimal:2',
            'basic_salary_usd' => 'decimal:2',
            'leave_entitlement' => 'decimal:2',
            'leave_accrual' => 'decimal:2',
            'zwl_percentage' => 'decimal:2',
            'usd_percentage' => 'decimal:2',
            'working_hours' => 'decimal:2',
            'dependents' => 'integer',
            'average_working_days' => 'integer',
            'vehicle_engine_capacity' => 'integer',
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
     * Get the department that the employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the position of the employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Get the occupation of the employee.
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class, 'occupation_id');
    }

    /**
     * Get the paypoint of the employee.
     */
    public function paypoint(): BelongsTo
    {
        return $this->belongsTo(Paypoint::class, 'paypoint_id');
    }

    /**
     * Get the NEC grade of the employee.
     */
    public function necGrade(): BelongsTo
    {
        return $this->belongsTo(NECGrade::class, 'nec_grade_id');
    }

    /**
     * Get the bank details for the employee.
     */
    public function bankDetails()
    {
        return $this->hasMany(EmployeeBankDetail::class, 'employee_id');
    }

    /**
     * Get the default bank account for the employee.
     */
    public function defaultBankAccount()
    {
        return $this->hasOne(EmployeeBankDetail::class, 'employee_id')
                    ->where('is_default', true);
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
