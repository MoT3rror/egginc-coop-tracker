<?php

namespace App\Http\Middleware;

use Closure;

class OnlyLocal
{
    private $whiteIPs = ['127.0.0.1', '104.131.42.208'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->ip(), $this->whiteIPs)) {
            abort(403);
        }

        return $next($request);
    }
}
