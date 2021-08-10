<?php
namespace App\DiscordMessages;

use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Ping extends Status
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

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
        $contract = $this->getContractInfo($this->parts[1]);

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
                return !in_array($user->egg_inc_player_id, $players);
            })
        ;

        $members
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
        ;

        $message = $contract->name . ' (' . $members->count() . ')' . PHP_EOL . 'These people haven\'t joined a co-op yet:' . PHP_EOL;

        foreach ($members as $member) {
            $message .= '<@' . $member->discord_id . '>' . PHP_EOL;
        }

        return [$message];
    }

    public function help(): string
    {
        return '{Contract ID} - Ping members not in a coop.';
    }

    public function description(): string
    {
        return 'Ping members not in a coop.';
    }
}
