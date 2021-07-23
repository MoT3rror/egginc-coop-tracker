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
            $user->load('userStats');
        }

        return Inertia::render('Home', [
            'guilds'     => $guilds,
            'playerInfo' => $playerInfo,
            'user'       => $user,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->makeVisible('egg_inc_player_id');
        return Inertia::render('Profile', ['user' => $user]);
    }

    public function profileSave(Request $request)
    {
        $user = $request->user();
        $user->egg_inc_player_id = $request->input('egg_inc_player_id');
        $user->keep_stats = (bool) $request->input('keep_stats');

        $user->save();
        return redirect(route('profile'));
    }
}
