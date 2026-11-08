<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Password::defaults(fn () => app()->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->uncompromised()
            : Password::min(12)->mixedCase()->numbers());

        // Every Permission becomes a Gate ability: ->middleware('can:users.manage'), $user->can('...').
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }

        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));

        // Brute-force protection: per email+IP and per IP.
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(Str::lower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
    }
}
