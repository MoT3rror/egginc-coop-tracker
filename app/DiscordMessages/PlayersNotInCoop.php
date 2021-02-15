<?php
namespace App\DiscordMessages;

use App\SimilarText;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class PlayersNotInCoop extends Status
{
    public function message(): string
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return $coops;
        }

        $players = [];
        foreach ($coops as $coop) {
            $players = array_merge($players, collect($coop->getCoopInfo()->members)->pluck('id')->all());
        }

        $this->guild->sync();
        $members = $this->guild
            ->members()
            ->withEggIncId()
            ->get()
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
        ;

        foreach ($members as $key => $member) {
            if (in_array($member->egg_inc_player_id, $players)) {
                unset($members[$key]);
            }
        }

        return '- ' . $members->implode('username', PHP_EOL . '- ');
    }

    public function help(): string
    {
        return 'eb!players-not-in-coop {Contract ID} - ';
    }
}
