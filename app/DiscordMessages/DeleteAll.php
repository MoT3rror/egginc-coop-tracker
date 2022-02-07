<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Illuminate\Support\Arr;

class DeleteAll extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        $coops = Coop::contract($parts[1])
            ->guild($this->guildId)
            ->get()
        ;

        foreach ($coops as $coop) {
            $coop->delete();
        }

        return 'Coops have been deleted for the contract';
    }

    public function help(): string
    {
        return '{contractID} - Remove all coop from tracking for contract.';
    }
}
