<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'company_details';

    protected $fillable = [
        'company_name',
        'company_email_address',
        'phone_number',
        'telephone_number',
        'physical_address',
        'registration_number',
        'tax_number',
        'industry',
        'website',
        'description',
        'logo',
        'is_active',
        // Working days policy
        'working_days_policy',
        'standard_working_days_per_month',
        'exclude_saturdays',
        'exclude_sundays',
        'exclude_public_holidays',
        'custom_holidays',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'exclude_saturdays' => 'boolean',
        'exclude_sundays' => 'boolean',
        'exclude_public_holidays' => 'boolean',
        'custom_holidays' => 'array',
        'standard_working_days_per_month' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the employees for the company.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'center_id', 'id');
    }

    /**
     * Get the bank details for the company.
     */
    public function bankDetails(): HasMany
    {
        return $this->hasMany(CompanyBankDetail::class, 'center_id', 'id');
    }

    /**
     * Get the cost centers for the company.
     */
    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'company_id', 'id');
    }

    /**
     * Scope query to active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        return $this->physical_address ?? '';
    }

    /**
     * Get the primary contact information.
     */
    public function getPrimaryContactAttribute(): array
    {
        return [
            'email' => $this->company_email_address,
            'phone' => $this->phone_number,
            'telephone' => $this->telephone_number,
        ];
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // Check if logo exists in storage
        if (Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

    /**
     * Validation rules.
     */
    public static function rules(bool $isUpdate = false): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'company_email_address' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'physical_address' => 'required|string|max:1000',
            'registration_number' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => $isUpdate ? 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' : 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Update contact information.
     */
    public function updateContactInfo(array $contactData): bool
    {
        $validated = validator($contactData, [
            'company_email_address' => 'required|email',
            'phone_number' => 'required|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
        ])->validate();

        return $this->update($validated);
    }

    /**
     * Update address.
     */
    public function updateAddress(string $address): bool
    {
        return $this->update(['physical_address' => $address]);
    }
}
