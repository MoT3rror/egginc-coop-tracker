<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\Role;
use Illuminate\Support\Arr;

class MakeCoop extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        $contract = $this->getContractInfo($parts['1']);

        if (!Arr::get($parts, 2)) {
            return 'Coop name required';
        }

        $coop = Coop::query()
            ->guild($this->guild->discord_id)
            ->contract($parts[1])
            ->coop($parts[2])
            ->first()
        ;

        if (!$coop) {
            $coop = new Coop;
            $coop->guild_id = $this->guild->discord_id;
            $coop->contract = $contract->identifier;
            $coop->coop = $parts[2];
            $coop->save();
        }

        $members = array_splice($parts, 3);
        foreach ($members as $member) {
            $id = $this->cleanAt($member);

            $user = $this->guild->members->firstWhere('discord_id', $id);

            if ($user && !$user->hasCompletedContract($contract->identifier)) {
                $coop->addMember($user);
                continue;
            }

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                foreach ($role->members as $roleMember) {
                    if (!$roleMember->hasCompletedContract($contract->identifier)) {
                        $coop->addMember($roleMember);
                    }
                }
            }
        }

        $coop->makeChannel();

        return 'Coop made/set.';
    }

    public function help(): string
    {
        return '{contractID} {Coop} {members/roles...} - Make/set players for coops.';
    }
}
