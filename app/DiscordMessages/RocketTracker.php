<?php
namespace App\DiscordMessages;

use App\Formatters\TimeLeft;
use Cache;

class RocketTracker extends Base
{
    public function message(): string
    {
        if (!$this->user->egg_inc_player_id) {
            return 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';
        }
        Cache::forget('egg-player-info-' . $this->user->egg_inc_player_id);
        $playerInfo = $this->user->getEggPlayerInfo();
        if (!$playerInfo) {
            return 'Invalid Egg Inc Player ID. Use `eb!set-player-id {id}` to set correct ID';
        }

        $message = [];

        foreach ($playerInfo->artifactsDb->missionInfos as $mission) {
            if ($mission->status != 'EXPLORING') {
                continue;
            }
            $message[] =  $mission->ship . ' - ' . resolve(TimeLeft::class)->format($mission->durationSeconds + round($mission->startTimeDerived) - time(), false, true);
        }
        return implode(PHP_EOL, $message);
    }
}
