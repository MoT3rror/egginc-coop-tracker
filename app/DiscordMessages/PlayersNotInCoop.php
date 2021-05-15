<?php
namespace App\DiscordMessages;

use App\SimilarText;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class PlayersNotInCoop extends Status
{
    public function message(): array
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return [$coops];
        }

        $players = [];
        foreach ($coops as $coop) {
            try {
                $players = array_merge($players, collect($coop->getCoopInfo()->members)->pluck('id')->all());
            } catch (\App\Exceptions\CoopNotFoundException $e) {
                // just catch error
            }
        }
        $players = array_map('strtolower', $players);

        $this->guild->sync();
        $members = $this->guild
            ->members()
            ->withEggIncId()
            ->partOfTeam($this->guild)
            ->get()
            ->filter(function($user) {
                return !$user->hasCompletedContract($this->parts[1]);
            })
            ->filter(function($user) use ($players) {
                return in_array($user->egg_inc_player_id, $players);
            })
        ;

        $members
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
        ;
        return ['- ' . $members->implode('username_with_roles', PHP_EOL . '- ')];
    }

    public function help(): string
    {
        return '{Contract ID} - Find players not in contract.';
    }

    public function description(): string
    {
        return 'Find players not in contract.';
    }
}
