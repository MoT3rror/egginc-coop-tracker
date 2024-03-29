<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\Role;
use Illuminate\Support\Arr;

class MakeCoopsByRoles extends Base
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

        if (!$coops) {
            return 'Number of coops is required.';
        }

        $prefix = Arr::get($this->parts, 3);

        if (!$prefix) {
            return 'Prefix is required.';
        }

        $roles = array_splice($this->parts, 4);

        $count = 0;
        $found = [];
        foreach ($roles as $role) {
            $id = $this->cleanAt($role);

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                foreach ($role->members as $member) {
                    if ($member->hasCompletedContract($contract->identifier)) {
                        continue;
                    }

                    if (Arr::get($found, $member->id)) {
                        continue;
                    }
                    $found[$member->id] = true;
                    $count++;
                }
            }
        }

        if ($count > $coops * $contract->getMaxCoopSize()) {
            return 'Figure out your math. You are attempting to make ' . $coops . ' that has size restrion of ' . ($coops * $contract->getMaxCoopSize()) . ' for ' . $count . ' players.';
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

        foreach ($roles as $role) {
            $id = $this->cleanAt($role);

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                $members = $role->members->sortBy(function ($user) {
                    return $user->getPlayerEarningBonus();
                }, SORT_REGULAR, true);
                foreach ($members as $roleMember) {
                    if ($roleMember->hasCompletedContract($contract->identifier)) {
                        continue;
                    }

                    $checkIfAlreadyOnTeam = $roleMember->coopsMembers()
                        ->join('coops', 'coops.id', '=', 'coop_members.coop_id')
                        ->where('coops.contract', '=', $contract->identifier)
                        ->first()
                    ;

                    if ($checkIfAlreadyOnTeam) {
                        continue;
                    }

                    $coopToAddTo = $coopsAdded[$currentCoop];
                    $coopToAddTo->addMember($roleMember);

                    if ($currentCoop < count($coopsAdded) - 1) {
                        $currentCoop++;
                    } else {
                        $currentCoop = 0;
                    }
                    
                }
            }
        }

        foreach ($coopsAdded as $coop) {
            $coop->makeChannel();
        }

        return 'Coops have been made.';
    }

    public function help(): string
    {
        return '{Contract ID} {Number of Coops} {Coop Prefix} - Make coops with roles specified. The command will check if the user has the contract available.';
    }

    private function getCoopName($prefix, $number): string
    {
        $randomCharacters = 'abcdefghjkmnpqrstuvwy';

        $randomIndex = mt_rand(0, strlen($randomCharacters) - 1);

        return $prefix . $number . $randomCharacters[$randomIndex];
    }
}
