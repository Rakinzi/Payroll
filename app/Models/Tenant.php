<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the database name for this tenant.
     */
    public function database(): string
    {
        return $this->tenancy_db_name;
    }

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
     * Set custom database name for the tenant.
     *
     * @param string $database
     * @return $this
     */
    public function withDatabase(string $database): self
    {
        $this->tenancy_db_name = $database;
        return $this;
    }

    /**
     * Set tenant's system name.
     *
     * @param string $name
     * @return $this
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
     *
     * @param string $logo
     * @return $this
     */
    public function withLogo(string $logo): self
    {
        $data = $this->data ?? [];
        $data['logo'] = $logo;
        $this->data = $data;
        return $this;
    }
}
