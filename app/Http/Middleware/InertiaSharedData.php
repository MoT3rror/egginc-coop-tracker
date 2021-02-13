<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Inertia\Inertia;

class InertiaSharedData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        Inertia::share(
            'user',
            $user ? $user->only('id', 'username') : null
        );
        Inertia::share('csrf_token', csrf_token());

        return $next($request);
    }
}
