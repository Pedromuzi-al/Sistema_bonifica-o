<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureConfiguredEmailVerification extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if ($request->user() && ! config('innovation.email_verification_required')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
