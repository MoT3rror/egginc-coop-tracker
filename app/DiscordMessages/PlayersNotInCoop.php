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
        $contract = $this->getContractInfo($this->parts[1]);

        $this->guild->sync();
        $members = $this->guild->getMembersAvailableForContract($contract->identifier)
            ->filter(function($user) use ($players) {
                return !in_array($user->egg_inc_player_id, $players);
            })
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
        ;

        $message = $contract->name . ' (' . $members->count() . ')';
        
        foreach ($members as $member) {
            $coopMember = $member->coopsMembers()
                ->select('coop_members.*')
                ->join('coops', 'coops.id', '=', 'coop_members.coop_id')
                ->where('coops.contract', '=', $this->parts[1])
                ->first()
            ;

            $message .= PHP_EOL . $member->username_with_roles;

            if ($coopMember) {
                $message .= ' - ' . $coopMember->coop->coop;
            }
        }

        return [$message];
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
