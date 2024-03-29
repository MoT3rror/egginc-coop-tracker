<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use Illuminate\Support\Arr;

class MakeCoops extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $contractId = Arr::get($this->parts, '1');

        if (!$contractId) {
            return 'Contract ID is required.';
        }

        $contract = $this->getContractInfo($contractId);

        $coops = Arr::get($this->parts, 2);

        if (!$coops || !is_numeric($coops)) {
            return 'Number of coops is required.';
        }

        $prefix = Arr::get($this->parts, 3);

        if (!$prefix) {
            return 'Prefix is required.';
        }
        
        $users = $this->guild->getMembersAvailableForContract($contract->identifier);

        if ($users->count() > $coops * $contract->getMaxCoopSize()) {
            return 'Figure out your math. You are attempting to make ' . $coops . ' that has size restrion of ' . ($coops * $contract->getMaxCoopSize()) . ' for ' . $users->count() . ' players.';
        }

        $coopsAdded = [];
        for ($i = 1; $i <= $coops; $i++) { 
            $coop = new Coop();
            $coop->contract = $contract->identifier;
            $coop->guild_id = $this->guild->discord_id;
            $coop->coop = $this->getCoopName($prefix, $i);
            $coop->save();
            $coopsAdded[] = $coop;
        }

        $currentCoop = 0;
        foreach ($users as $user) {
            $checkIfAlreadyOnTeam = $user->coopsMembers()
                ->join('coops', 'coops.id', '=', 'coop_members.coop_id')
                ->where('coops.contract', '=', $contract->identifier)
                ->first()
            ;

            if ($checkIfAlreadyOnTeam) {
                continue;
            }

            $coopToAddTo = $coopsAdded[$currentCoop];
            $coopToAddTo->addMember($user);

            if ($currentCoop < count($coopsAdded) - 1) {
                $currentCoop++;
            } else {
                $currentCoop = 0;
            }
        }

        foreach ($coopsAdded as $coop) {
            $coop->makeChannel();
        }

        return 'Coops have been made.';
    }

    public function help(): string
    {
        return '{Contract ID} {Number of Coops} {Coop Prefix} - Make coops with all members available. Will add members by EB rating till all coops made.';
    }

    private function getCoopName($prefix, $number): string
    {
        $randomCharacters = 'abcdefghjkmnpqrstuvwy';

        $randomIndex = mt_rand(0, strlen($randomCharacters) - 1);

        return $prefix . $number . $randomCharacters[$randomIndex];
    }
}
