<?php
namespace App\DiscordMessages;

use App\Models\User;

class GetMyId extends Base
{
    protected $middlewares = [];

    public $globalSlash = true;

    public function message(): string
    {
        $user = User::unguarded(function () {
            return User::firstOrCreate(
                [
                    'discord_id' => $this->authorId,
                ],
                [
                    'username' => $this->authorName,
                ]
            );
        });

        if ($this->guildId) {
            return 'Command must be ran by DM.';
        }

        if (!$user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }
        return strtoupper($user->egg_inc_player_id);
    }

    public function help(): string
    {
        return 'Get current set Egg Player ID.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
