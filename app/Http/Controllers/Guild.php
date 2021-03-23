<?php

namespace App\Http\Controllers;

use App\Models\Guild as GuildModel;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Guild extends Controller
{
    public function index(Request $request, $guildId)
    {
        User::setStaticAppends(['player_earning_bonus_formatted', 'player_egg_rank', 'soul_eggs', 'eggs_of_prophecy', 'drones', 'player_earning_bonus', 'highest_deflector_without_percent'], true);

        $guildModel = GuildModel::findByDiscordGuildId($guildId);
        $guildModel->sync();

        return Inertia::render('Guild', [
            'guildModel'       => $guildModel,
            'currentContracts' => $this->getContractsInfo(),
        ]);
    }

    public function settings(Request $request, $guildId)
    {
        return Inertia::render('Settings', [
            'guildModel' => GuildModel::findByDiscordGuildId($guildId),
        ]);
    }

    public function settingsSave(Request $request, $guildId)
    {
        $guild = GuildModel::findByDiscordGuildId($guildId);

        foreach ($request->input('roles') as $roleId => $role) {
            $roleModel = $guild->roles->find($roleId);
            $roleModel->is_admin = $role['is_admin'];
            $roleModel->show_members_on_roster = $role['show_members_on_roster'];
            $roleModel->show_role = $role['show_role'];
            $roleModel->save();
        }

        return redirect()
            ->route('guild.settings', ['guildId' => $guildId])
            ->with('success', 'Settings saved.')
        ;
    }
}
