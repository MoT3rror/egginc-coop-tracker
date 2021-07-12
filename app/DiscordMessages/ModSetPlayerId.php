<?php
namespace App\DiscordMessages;

use App\Models\User;
use Arr;
use RestCord\DiscordClient;

class ModSetPlayerId extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!isset($parts[1])) {
            return '@user is required';
        }

        $this->guild->sync();

        $userId = str_replace(['<@!', '<@', '>'], '', $parts[1]);

        $user = User::query()->discordId($userId)->first();

        if (!$user || !$user->guilds->contains($this->guild)) {
            return 'User is not in this server.';
        }

        $user->egg_inc_player_id = Arr::get($parts, 2, null);
        $user->save();

        return 'Player ID set successfully.';
    }

    public function help(): string
    {
        return '{@user} {Egg Inc Player ID} - Player ID starts with EI (letter i)';
    }
}
