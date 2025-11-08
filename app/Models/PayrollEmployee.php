<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEmployee extends Model
{
    use HasUuids;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'assigned_date',
        'is_active',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the payroll.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope query to active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to specific payroll.
     */
    public function scopeForPayroll($query, string $payrollId)
    {
        return $query->where('payroll_id', $payrollId);
    }
}
