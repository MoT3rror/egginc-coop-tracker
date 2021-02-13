<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BeMemberOfGuild
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

        if (!$guild) {
            return redirect()
                ->route('home')
                ->with('error', 'Not a member of that server.')
            ;
        }

        return $next($request);
    }
}
