<?php
namespace App\DiscordMessages;

use App\Models\User;

class GetPlayerId extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = true;

    public function message(): string
    {
        $user = $discordId = str_replace(['<@!', '<@', '>'], '', $this->parts[1]);
        $user = $this->guild->members->firstWhere('discord_id', $discordId);
        if (!$user) {
            return 'User not found';
        }

        if (!$user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }
        return strtoupper($user->egg_inc_player_id);
    }

    public function help(): string
    {
        return '- Get current set Egg Player ID of user.';
    }

    public function description(): string
    {
        return 'Get current set Egg Player ID of user.';
    }

    public function options(): array
    {
        $contracts = $this->getAvailableContractOptions();

        return [
            [
                'type'        => 6,
                'name'        => 'user',
                'description' => 'User',
                'required'    => true,
            ],
        ];
    }
}
