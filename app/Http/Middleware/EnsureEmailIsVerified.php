<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
             ! $request->user()->hasVerifiedEmail())) {
            return Redirect::route('verification.notice');
        }

        return $next($request);
    }
}
