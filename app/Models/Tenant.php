<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get tenant's system name.
     */
    public function getSystemNameAttribute(): string
    {
        return $this->data['system_name'] ?? 'Lorimak';
    }

    /**
     * Get tenant's logo path.
     */
    public function getLogoAttribute(): ?string
    {
        return $this->data['logo'] ?? null;
    }

    /**
     * Get the database name for this tenant.
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * Get domains for this tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Set tenant's system name.
     */
    public function withSystemName(string $name): self
    {
        $data = $this->data ?? [];
        $data['system_name'] = $name;
        $this->data = $data;
        return $this;
    }

    /**
     * Set tenant's logo.
     */
    public function withLogo(string $logo): self
    {
        $data = $this->data ?? [];
        $data['logo'] = $logo;
        $this->data = $data;
        return $this;
    }

    /**
     * Set database name for the tenant.
     */
    public function withDatabase(string $database): self
    {
        $this->database = $database;
        return $this;
    }
}
