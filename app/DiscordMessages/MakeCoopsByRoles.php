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

        $coops = Arr::get($this->parts, 2);

        if (!$coops) {
            return 'Number of coops is required.';
        }

        $prefix = Arr::get($this->parts, 3);

        if (!$prefix) {
            return 'Prefix is required.';
        }
        
        $users = $this->guild->getMembersAvailableForContract($contractId);

        $coopsAdded = [];
        for ($i = 1; $i <= $coops; $i++) { 
            $coop = new Coop();
            $coop->contract = $contractId;
            $coop->guild_id = $this->guild->discord_id;
            $coop->coop = $this->getCoopName($prefix, $i);
            $coop->save();
            $coopsAdded[] = $coop;
        }

        $currentCoop = 0;

        $roles = array_splice($parts, 4);
        foreach ($roles as $role) {
            $id = $this->cleanAt($role);

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                foreach ($role->members as $roleMember) {
                    if (!$roleMember->hasCompletedContract($parts[1])) {
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
