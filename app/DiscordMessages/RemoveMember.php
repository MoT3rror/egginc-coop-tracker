<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\User;
use Illuminate\Support\Arr;

class RemoveMember extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        if (!Arr::get($parts, 2)) {
            return 'Member required';
        }

        $user = User::query()
            ->discordId($this->cleanAt($parts[2]))
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
            $memberExists->coop->sendMessageToChannel('I remove <@' . $user->discord_id . '> from this coop.');

            $memberExists->delete();

            $memberExists->coop->makeChannel();
        }

        return 'Member was removed from contract coops.';
    }

    public function help(): string
    {
        return '{contractID} {member} - Remove member from contract coops.';
    }
}
