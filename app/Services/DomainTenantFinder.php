<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)->first();

        if (! $domain) {
            return null;
        }

        return Tenant::find($domain->tenant_id);
    }
}
