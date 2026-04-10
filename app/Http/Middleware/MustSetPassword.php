<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MustSetPassword
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs([
            'filament.admin.auth.logout',
            'filament.admin.auth.login',
            'filament.app.pages.member-login',
            'filament.app.pages.set-email',
            'filament.app.pages.set-password',
            'app/set-password',
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

        // Safety net: if user somehow got past login with must_change_password, log them out
        if (auth()->check() && auth()->user()->must_change_password) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
