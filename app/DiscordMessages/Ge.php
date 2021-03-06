<?php
namespace App\DiscordMessages;

use App\Models\User;
use App\Exceptions\UserNotFoundException;
use Cache;

class Ge extends Base
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

        if (!$user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }
        Cache::forget('egg-player-info-' . $user->egg_inc_player_id);
        $playerInfo = $user->getEggPlayerInfo();
        if (!$playerInfo) {
            return 'Invalid Egg Inc Player ID. Use `eb!set-player-id {id}` to set correct ID';
        }

        $username = $user->getEggPlayerInfo()->userName;
        $liftTimeGoldenEggs = number_format($user->getLifeTimeGoldenEggsAttribute());
        $currentGoldenEggs = number_format($user->getCurrentGoldenEggs());
        $roles = $user
            ->roles
            ->where('show_role')
            ->pluck('name')
            ->join(', ')
        ;

        return <<<RANK
```
$username
Current Golden Eggs: $currentGoldenEggs
Total Golden Eggs: $liftTimeGoldenEggs
```
RANK;
    }

    public function help(): string
    {
        return 'Get player golden egg stats.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
