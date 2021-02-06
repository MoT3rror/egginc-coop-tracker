<?php
namespace App\DiscordMessages;

use Arr;

class WhoHasNotCompleteContract extends Base
{
    protected $middlewares = ['requiresGuild'];

    public function message(): string
    {
        $guild = $this->guild;
        $parts = $this->parts;
        $contractId = Arr::get($parts, '1');

        if (!$contractId) {
            return 'Contract ID is required.';
        }

        $guild->sync();
        $users = $guild
            ->members()
            ->withEggIncId()
            ->inShowRoles()
            ->get()
            ->sortBy('username')
            ->filter(function ($user) use ($contractId) {
                return !in_array($contractId, $user->getCompleteContractsAttribute());
            })
        ;
        if ($users->count() == 0) {
            return 'All users have completed the contract.';
        }

        return '- ' . $users->implode('username', PHP_EOL . '- ');
    }
}
