<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleActiveSession
{
    private const SESSION_KEY = 'active_session_token';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $currentToken = $user->active_session_token;
        $sessionToken = $request->session()->get(self::SESSION_KEY);

        if (! $currentToken) {
            $currentToken = Str::random(64);

            $user->forceFill([
                'active_session_token' => $currentToken,
            ])->save();

            $request->session()->put(self::SESSION_KEY, $currentToken);

            return $next($request);
        }

        if (hash_equals($currentToken, (string) $sessionToken)) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This account was signed in on another device. Please log in again.',
                'redirect' => route('login'),
            ], 409);
        }

        return redirect()->route('login')
            ->with('status', 'This account was signed in on another device. Please log in again.');
    }
}
