<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'center_id',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the employee that owns the user.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the cost center that the user belongs to.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'center_id');
    }

    /**
     * Check if user is a super admin (has access to all centers).
     */
    public function isSuperAdmin(): bool
    {
        return $this->center_id === null;
    }

    /**
     * Check if user can access a specific cost center.
     */
    public function canAccessCenter(?string $centerId): bool
    {
        // Super admin can access all centers
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Regular users can only access their assigned center
        return $this->center_id === $centerId;
    }

    /**
     * Scope query to only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to users with admin role (via Spatie permissions).
     */
    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        });
    }

    /**
     * Scope query to super admins only.
     */
    public function scopeSuperAdmins($query)
    {
        return $query->whereNull('center_id')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            });
    }

    /**
     * Scope query to cost center admins only.
     */
    public function scopeCostCenterAdmins($query, ?string $centerId = null)
    {
        $query = $query->whereNotNull('center_id')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            });

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        return $query;
    }

    /**
     * Check if user is a cost center admin.
     */
    public function isCostCenterAdmin(): bool
    {
        return !$this->isSuperAdmin() && $this->hasRole('admin');
    }

    /**
     * Get accessible cost centers for this user.
     */
    public function getAccessibleCostCenters()
    {
        if ($this->isSuperAdmin()) {
            return CostCenter::all();
        }

        return CostCenter::where('id', $this->center_id)->get();
    }

    /**
     * Check if this user is an admin (super or cost center).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Update last login information.
     */
    public function updateLoginInfo(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }
}
