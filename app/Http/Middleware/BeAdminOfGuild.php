<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BeAdminOfGuild
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
        $guilds = $request->user()->discordGuilds();
        $guild = collect($guilds)
            ->where('id', $request->guildId)
            ->first()
        ;

        if (!$guild || !$guild->isAdmin) {
            return redirect()
                ->route('home')
                ->with('error', 'You are not an admin of this server.')
            ;
        }

        return $next($request);
    }
}
