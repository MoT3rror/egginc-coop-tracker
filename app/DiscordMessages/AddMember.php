<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\User;
use Illuminate\Support\Arr;

class AddMember extends Base
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

        if (!Arr::get($parts, 3)) {
            return 'Member required';
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

        $user = User::query()
            ->discordId($this->cleanAt($parts[3]))
            ->partOfTeam($this->guild->id)
            ->first()
        ;

        if (!$user) {
            return 'User not found';
        }

        $memberExists = CoopMember::query()
            ->user($user->id)
            ->contractGuild($parts[1], $this->guild->discord_id)
            ->first()
        ;

        if ($memberExists) {
            $memberExists->delete();
        }

        $coopMember = new CoopMember;
        $coopMember->user_id = $user->id;
        $coop->members()->save($coopMember);

        $coop->sendMessageToChannel('I added <@' . $user->discord_id . '> to this coop.');

        return 'Member added.';
    }

    public function help(): string
    {
        return '{contractID} {Coop} {member} - Add member to coop.';
    }
}
