<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user();
        $user = $authenticated ? User::withTrashed()->find($authenticated->id) : null;
        if ($authenticated && (! $user || ! $user->isAvailable() || $user->invitation_pending || (int) $request->session()->get('access_version', 0) !== (int) $user->access_version)) {
            $message = $user?->invitation_pending ? 'Conclua a definição da senha pelo link de primeiro acesso.' : ($user?->isAvailable() ? 'Sua sessão expirou. Entre novamente.' : 'Sua conta está bloqueada. Fale com o administrador.');
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $user?->isAvailable() ? 401 : 403);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        if ($user) {
            Auth::guard('web')->setUser($user);
        }

        return $next($request);
    }
}
