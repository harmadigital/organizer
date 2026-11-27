<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanSignIn
{
    public const MESSAGE = 'Akun Anda sudah tidak aktif atau akses loginnya sudah dicabut. Hubungi administrator.';

    /**
     * Sign out a person whose account was deactivated or whose login access was revoked
     * while they were signed in, on their very next request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user instanceof User || $user->canSignIn()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'data' => self::MESSAGE, 'errors' => (object) []], 401);
        }

        return redirect()->route('login')->withErrors(['username' => self::MESSAGE]);
    }
}
