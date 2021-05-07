<?php
namespace App\DiscordMessages;

use Arr;

class Unavailable extends Base
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
            ->inShowRoles($this->guild)
            ->get()
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
            ->filter(function ($user) use ($contractId) {
                return in_array($contractId, $user->getCompleteContractsAttribute());
            })
        ;
        if ($users->count() == 0) {
            return 'All users need to complete this contract.';
        }

        return '- ' . $users->implode('username', PHP_EOL . '- ');
    }

    public function help(): string
    {
        return '{Contract ID} - Get users that do not have the contract.';
    }
}
