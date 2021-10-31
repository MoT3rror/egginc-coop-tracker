<?php
namespace App\DiscordMessages;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class Links extends Base
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

        $id = strtoupper($user->egg_inc_player_id);
        $links = [
            'https://wasmegg.netlify.app/rockets-tracker/?playerId={$id}',
            'https://wasmegg.netlify.app/past-contracts/?playerId={$id}',
            'https://wasmegg.netlify.app/smart-assistant/?playerId={$id}',
            'https://wasmegg.netlify.app/enlightenment/?playerId={$id}',
            'https://eicoop.netlify.app/u/{$id}',

        ];
        foreach ($links as $key => $link) {
            $links[$key] = str_replace('{$id}', $id, $link);
        }

        return implode(PHP_EOL, $links);
    }

    public function help(): string
    {
        return 'Get links to helpful tools.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
