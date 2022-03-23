<?php
namespace App\DiscordMessages;

use App\SimilarText;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class BootWarning extends Status
{
    public function message(): array
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return [$coops];
        }

        $contract = $this->getContractInfo($this->parts['1']);

        $players = collect([]);
        foreach ($coops as $coop) {
            try {
                if ($coop->isComplete()) {
                    continue;
                }

                $players = $players->merge(
                    collect($coop->getCoopInfo()->members)
                        ->filter(function ($player) use ($coop) {
                            $player->coop = $coop->coop;
                            return !$player->active || $player->leech;
                        })
                );
            } catch (\App\Exceptions\CoopNotFoundException $e) {
                // just catch error
            }
        }

        $this->guild->sync();
        $members = $this->guild
            ->members()
            ->withEggIncId()
            ->partOfTeam($this->guild)
            ->get()
        ;

        $inTrouble = '';
        foreach ($players as $player) {
            $member = $members->where('egg_inc_player_id', strtolower($player->id))->first();
            if ($member) {
                $type = ' -';
                if (!$player->active) {
                    $type .= ' Sleeping';
                }
                if ($player->leech) {
                    $type .= ' Low Rate';
                }
                $inTrouble .= $member->username . ' - ' . $player->coop . $type . PHP_EOL;
            }
        }

        if (!$inTrouble) {
            $inTrouble = 'No users in trouble';
        }

        return [$contract->name . PHP_EOL . $inTrouble];
    }

    public function help(): string
    {
        $this->isAdmin();
        return '{Contract ID} - Coop Member Status.';
    }

    public function description(): string
    {
        return 'Coop Member Status.';
    }
}
