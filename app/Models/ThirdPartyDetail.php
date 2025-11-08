<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdPartyDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'employee_id',
        'employee_name',
        'nat_id',
        'contribution_amount',
        'reference_number',
    ];

    protected $casts = [
        'contribution_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the report this detail belongs to.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ThirdPartyReport::class, 'report_id');
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
