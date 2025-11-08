<?php

namespace App\Providers;

use App\Models\Employee;
use App\Policies\EmployeePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Employee::class => EmployeePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register gates for backward compatibility and convenience
        Gate::define('access-center', function ($user, $centerId) {
            return $user->hasPermissionTo('access all centers') ||
                   $user->center_id === $centerId;
        });

        Gate::define('super-admin', function ($user) {
            return $user->hasPermissionTo('access all centers');
        });

        // Gate to check if user is a super admin
        Gate::define('is-super-admin', function ($user) {
            return $user->isSuperAdmin();
        });
    }
}
