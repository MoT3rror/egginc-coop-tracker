<?php
namespace App\DiscordMessages;

use App\Models\User;
use App\Exceptions\UserNotFoundException;
use Illuminate\Support\Arr;
use Cache;

class Rank extends Base
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

            $discordId = $this->cleanAt($this->parts[1]);
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

        $username = $user->getEggPlayerInfo()->userName;
        $backupTime = $user->getBackupTimeFormatted();
        $soulEggs = $user->getSoulEggsFormattedAttribute();
        $goldenEggs = $user->getEggsOfProphecyAttribute();
        $earningBonus = $user->getPlayerEarningBonusFormatted();
        $farmerRole = $user->getPlayerEggRankAttribute();
        $soulEggsNeeded = $user->getSoulEggsNeededForNextRankFormattedAttribute();
        $goldenEggsNeeded = $user->getPENeededForNextRankAttribute();
        $drones = number_format($user->getDronesAttribute());
        $eliteDrones = number_format($user->getEliteDronesAttribute());
        $prestiges = $user->getPrestigesAttribute();
        $boostsUsed = number_format($user->getBoostsUsedAttribute());
        $liftTimeGoldenEggs = number_format($user->getLifeTimeGoldenEggsAttribute());
        $currentGoldenEggs = number_format($user->getCurrentGoldenEggs());
        $soulEggsPerPrestige = $user->getSoulEggsPerPrestigeFormatted();
        $roles = $user
            ->roles
            ->where('show_role')
            ->pluck('name')
            ->join(', ')
        ;

        return <<<RANK
```
$username
Backup Time: $backupTime
Soul Eggs: $soulEggs
Prophecy Eggs: $goldenEggs
Earning Bonus: $earningBonus
Farmer Role: $farmerRole
Group Role: $roles
Total Soul Eggs Needed for Next Rank: $soulEggsNeeded
Total Prophecy Eggs Needed for Next Rank: $goldenEggsNeeded
Current Golden Eggs: $currentGoldenEggs
Total Golden Eggs: $liftTimeGoldenEggs
Drones/Elite: {$drones}/{$eliteDrones}
Prestiges: $prestiges
Boosts Used: $boostsUsed
Soul Eggs Per Prestige: $soulEggsPerPrestige
```
RANK;
    }

    public function help(): string
    {
        return 'Get player stats/rank.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
