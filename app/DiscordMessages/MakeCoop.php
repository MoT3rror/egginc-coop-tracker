<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\Role;
use App\Models\User;
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
            $coop->contract = $parts[1];
            $coop->coop = $parts[2];
            $coop->save();
        }

        $coop->members()->delete();

        $members = array_splice($parts, 3);
        foreach ($members as $member) {
            $id = $this->cleanAt($member);

            $user = User::query()
                ->discordId($id)
                ->partOfTeam($this->guild->id)
                ->first()
            ;

            if ($user) {
                $coopMember = new CoopMember;
                $coopMember->user_id = $user->id;
                $coop->members()->save($coopMember);
                continue;
            }

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                foreach ($role->members as $roleMember) {
                    $coopMember = new CoopMember;
                    $coopMember->user_id = $roleMember->id;
                    $coop->members()->save($coopMember);
                    continue;
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
