<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MustSetPassword
{
    public function handle(Request $request, Closure $next)
    {
        // Skip checks on logout & login routes
        if ($request->routeIs('filament.admin.auth.logout', 'logout', 'filament.admin.auth.login')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->must_change_password) {
            if (! $request->routeIs('filament.admin.pages.set-password')) {
                return redirect()->route('filament.admin.pages.set-password');
            }
        }

        if (auth()->check() && empty(auth()->user()->password)) {
            if (! $request->routeIs('filament.admin.pages.auth.set-password')) {
                return redirect()->route('filament.admin.pages.auth.set-password');
            }
        }

        return $next($request);
    }
}
