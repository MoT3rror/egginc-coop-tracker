<?php
namespace App\DiscordMessages;

use App\Models\User;

class Rank extends Base
{
    protected $middlewares = [];

    public function message(): string
    {
        $user = User::discordId($this->authorId)
            ->first()
        ;

        if (!$user) {
            // @todo finish this
            return 'No user found.';
        }

        if (!$user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }

        $username = $user->getEggPlayerInfo()->userName;
        $soulEggs = $user->getSoulEggsFormattedAttribute();
        $goldenEggs = $user->getEggsOfProphecyAttribute();
        $farmerRole = $user->getPlayerEggRankAttribute();
        $soulEggsNeeded = $user->getSoulEggsNeededForNextRankFormattedAttribute();
        $goldenEggsNeeded = $user->getPENeededForNextRankAttribute();
        $roles = $user
            ->roles
            ->where('show_role')
            ->pluck('name')
            ->join(', ')
        ;

        return <<<RANK
```
$username
Soul Eggs: $soulEggs
Golden Eggs: $goldenEggs
Farmer Role: $farmerRole
Group Role: $roles
Soul Eggs Needed for Next Rank: $soulEggsNeeded
Golden Eggs Needed for Next Rank: $goldenEggsNeeded
```
RANK;
    }
}
