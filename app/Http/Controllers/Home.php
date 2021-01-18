<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class Home extends Controller
{
    public function index(Request $request)
    {
        $guilds = new \StdClass;
        $playerInfo = new \StdClass;
        $user = $request->user();
        if ($user) {
            $guilds = $user->discordGuilds();

            $playerInfo = $user->getEggPlayerInfo();

            if (!$guilds) {
                $guilds = new \StdClass;
            }
        }

        return Inertia::render('Home', [
            'guilds'     => $guilds,
            'playerInfo' => $playerInfo,
            'user'       => $user,
        ]);
    }
}
