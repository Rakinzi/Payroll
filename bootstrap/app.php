<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register tenant routes (these have NeedsTenant middleware that will fail for non-tenant domains)
            Route::group([], base_path('routes/tenant.php'));

            // Register central fallback routes (will only be reached if tenant routes middleware fails)
            Route::middleware(['web'])
                ->group(base_path('routes/web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle NoCurrentTenant exception - redirect to central landing page
        $exceptions->render(function (\Spatie\Multitenancy\Exceptions\NoCurrentTenant $e, $request) {
            // Only show central info page on root path
            if ($request->path() === '/') {
                return response()->json([
                    'message' => 'Lorimak Payroll System',
                    'version' => '2.0',
                    'note' => 'Please access via your tenant domain.',
                    'example' => 'http://local.localhost:8000',
                    'tenants' => \App\Models\Tenant::with('domains')->get()->map(function ($tenant) {
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->system_name,
                            'domains' => $tenant->domains->pluck('domain'),
                        ];
                    }),
                ]);
            }

            // For other paths, show the standard error
            return response()->json([
                'error' => 'Tenant Required',
                'message' => 'This application requires a valid tenant domain.',
                'example' => 'http://local.localhost:8000',
            ], 404);
        });
    })->create();
