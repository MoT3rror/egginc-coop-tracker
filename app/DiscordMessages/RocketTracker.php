<?php
namespace App\DiscordMessages;

use App\Formatters\TimeLeft;
use Arr;
use Cache;

class RocketTracker extends Base
{
    public $globalSlash = true;

    public function message(): string
    {
        $user = $this->user;

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

        $message = [];

        foreach ($playerInfo->artifactsDb->missionInfos as $mission) {
            if ($mission->status != 'EXPLORING') {
                continue;
            }
            $timeLeft = round($mission->startTimeDerived + $mission->durationSeconds - time());
            $message[] =  $mission->ship . ' - ' . ($timeLeft > 0 ? resolve(TimeLeft::class)->format($timeLeft, false, true, false) : 'Ready to Collect');
        }
        return implode(PHP_EOL, $message);
    }

    public function help(): string
    {
        return '- Get current status of rockets. Time left might be off by a couple of minutes because of backup/sync times.';
    }

    public function description(): string
    {
        return 'Get current status of rockets.';
    }
}
