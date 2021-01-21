<?php

namespace App\Http\Controllers;

use App\Models\Guild as GuildModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Guild extends Controller
{
    public function index(Request $request, $guildId)
    {
        $guilds = $request->user()->discordGuilds();
        $guild = collect($guilds)
            ->where('id', $guildId)
            ->first()
        ;

        if (!$guild) {
            return redirect()->route('home');
        }

        $guildModel = GuildModel::findByDiscordGuild($guild);

        return Inertia::render('Guild', [
            'guild'            => $guild,
            'guildModel'       => $guildModel,
            'currentContracts' => $this->getContractsInfo(),
        ]);
    }

    public function settings(Request $request, $guildId)
    {
        $guilds = $request->user()->discordGuilds();
        $guild = collect($guilds)
            ->where('id', $guildId)
            ->first()
        ;

        if (!$guild) {
            return redirect()->route('home');
        }


    }
}
