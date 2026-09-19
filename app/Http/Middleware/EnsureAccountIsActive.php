<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $request->session()->has('account_session_version')) {
            $request->session()->put('account_session_version', $user->session_version);
        }

        if ($user && (! $user->is_active || (int) $request->session()->get('account_session_version') !== $user->session_version)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Session berakhir atau akun sudah tidak aktif.',
            ]);
        }

        return $next($request);
    }
}
