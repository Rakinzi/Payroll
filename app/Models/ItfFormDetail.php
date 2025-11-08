<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItfFormDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_id',
        'employee_id',
        'employee_name',
        'nat_id',
        'gross_income',
        'taxable_income',
        'tax_deducted',
    ];

    protected $casts = [
        'gross_income' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'tax_deducted' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the form this detail belongs to.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(ItfForm::class, 'form_id');
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
