<?php
namespace App\DiscordMessages;

use App\Models\User;
use Arr;

class ModSetPlayerId extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = true;

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

    public function description(): string
    {
        return 'Set Egg Inc ID for user.';
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
            [
                'type'        => 3,
                'name'        => 'id',
                'description' => 'ID',
                'required'    => true,
            ],
        ];
    }
}
