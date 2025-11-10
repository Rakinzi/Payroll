<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?int
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)->first();

        return $domain?->tenant_id;
    }
}
