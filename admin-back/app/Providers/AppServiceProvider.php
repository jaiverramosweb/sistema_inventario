<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by($request->ip() . '|' . mb_strtolower($email)),
            ];
        });

        RateLimiter::for('mfa_challenge', function (Request $request) {
            $token = (string) $request->input('mfa_token', 'anonymous');

            return [
                Limit::perMinute(15)->by($request->ip()),
                Limit::perMinute(10)->by($request->ip() . '|' . $token),
            ];
        });

        RateLimiter::for('mfa_settings', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Implicitly grant "Super-Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super-Admin') ? true : null;
        });
    }
}
