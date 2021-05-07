<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Arr;

class Replace extends Base
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

        if (!Arr::get($parts, 3)) {
            return 'New coop name is required.';
        }

        $coop = Coop::contract($parts[1])
            ->coop($parts[2])
            ->guild($this->guildId)
            ->first()
        ;

        if (!$coop) {
            return 'Coop does not exist yet.';
        }
        $coop->coop = $parts[3];
        $coop->save();

        return 'Coop has been replaced.';
    }

    public function help(): string
    {
        return '{Contract ID} {Coop} {New Coop} - Replace coop name';
    }
}
