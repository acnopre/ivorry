<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustSetPassword
{
    public function handle(Request $request, Closure $next)
    {
        // Skip checks on logout & login routes
        if ($request->routeIs([
            'filament.admin.auth.logout',
            'filament.admin.auth.login',
            'filament.app.pages.member-login',
            'filament.app.pages.set-email',
            'filament.app.pages.set-password',
        ])) {
            return $next($request);
        }

        // Force set email if missing
        if (auth()->check() && empty(auth()->user()->email)) {
            if (! $request->routeIs('filament.app.pages.set-email')) {
                return redirect()->route('filament.app.pages.set-email');
            }
            return $next($request);
        }

        // Force set password if flagged
        if (auth()->check() && auth()->user()->must_change_password) {
            if (! $request->routeIs('filament.app.pages.set-password')) {
                return redirect()->route('filament.app.pages.set-password');
            }
            return $next($request);
        }

        return $next($request);
    }
}
