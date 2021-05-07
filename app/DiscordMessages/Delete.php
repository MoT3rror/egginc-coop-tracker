<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Arr;

class Delete extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        if (!Arr::get($parts, 2)) {
            return 'Coop name is required';
        }

        $coop = Coop::contract($parts[1])
            ->coop($parts[2])
            ->guild($this->guildId)
            ->first()
        ;

        if (!$coop) {
            return 'Coop does not exist yet.';
        }

        if ($coop->delete()) {
            return 'Coop has been deleted.';
        } else {
            return 'Was not able to delete the coop.';
        }
    }

    public function help(): string
    {
        return '{contractID} {Coop} - Remove coop from tracking';
    }
}
