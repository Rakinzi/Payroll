<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'tenant' => $this->getTenantInfo(),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'center_id' => $request->user()->center_id,
                    'is_super_admin' => $request->user()->isSuperAdmin(),
                    'employee' => $request->user()->employee,
                    'costCenter' => $request->user()->costCenter,
                    'roles' => $request->user()->roles->pluck('name'),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'can' => $this->getUserPermissionsMap($request->user()),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * Get tenant information to share with frontend.
     */
    private function getTenantInfo(): ?array
    {
        if (!tenancy()->initialized) {
            return null;
        }

        $tenant = tenant();

        return [
            'id' => $tenant->id,
            'name' => $tenant->system_name,
            'logo' => $tenant->logo,
        ];
    }

    /**
     * Get a map of all permissions for easier checking in React components.
     */
    private function getUserPermissionsMap($user): array
    {
        if (!$user) {
            return [];
        }

        $permissions = $user->getAllPermissions()->pluck('name');
        $permissionMap = [];

        foreach ($permissions as $permission) {
            $permissionMap[$permission] = true;
        }

        return $permissionMap;
    }
}
