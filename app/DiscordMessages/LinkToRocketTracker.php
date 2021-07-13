<?php
namespace App\DiscordMessages;

use App\Models\User;
use App\Exceptions\UserNotFoundException;
use Arr;
use Cache;

class LinkToRocketTracker extends Base
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

        if (Arr::get($this->parts, 1)) {
            $this->isAdmin();

            $discordId = str_replace(['<@!', '<@', '>'], '', $this->parts[1]);
            $user = $this->guild->members->firstWhere('discord_id', $discordId);
            if (!$user) {
                return 'User not found';
            }
        }

        if (!$user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }
        Cache::forget('egg-player-info-' . $user->egg_inc_player_id);
        $playerInfo = $user->getEggPlayerInfo();
        if (!$playerInfo) {
            return 'Invalid Egg Inc Player ID. Use `eb!set-player-id {id}` to set correct ID';
        }

        return 'https://wasmegg.netlify.app/rockets-tracker/?playerId=' . strtoupper($user->egg_inc_player_id);
    }

    public function help(): string
    {
        return 'Get link to rocket tracker with ID.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
