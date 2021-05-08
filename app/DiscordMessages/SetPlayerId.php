<?php
namespace App\DiscordMessages;

use App\Models\User;
use RestCord\DiscordClient;

class SetPlayerId extends Base
{
    protected $middlewares = [];

    public $globalSlash = true;

    public function message(): string
    {
        $parts = $this->parts;
        $user = User::unguarded(function () use ($parts) {
            return User::updateOrCreate(
                ['discord_id' => $this->authorId],
                [
                    'egg_inc_player_id' => $parts[1],
                    'username'          => $this->authorName,
                ]
            );
        });

        return (new Rank($this->authorId, $this->authorName))->message();
    }

    public function help(): string
    {
        return '{Egg Inc Player ID} - Player ID starts with EI (letter i)';
    }

    public function description(): string
    {
        return 'Player ID starts with EI (letter i)';
    }

    public function options(): array
    {
        return [
            [
                'type'        => 3,
                'name'        => 'player_id',
                'description' => 'Player ID',
                'required'    => true,
            ],
        ];
    }
}
