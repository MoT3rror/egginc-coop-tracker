<?php
namespace App\DiscordMessages;

use Arr;

class Unavailable extends Base
{
    protected $middlewares = ['requiresGuild'];

    public $guildOnly = true;

    public function message(): string
    {
        $guild = $this->guild;
        $parts = $this->parts;
        $contractId = Arr::get($parts, '1');

        if (!$contractId) {
            return 'Contract ID is required.';
        }

        $contract = $this->getContractInfo($contractId);

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
        if ($users->count() === 0) {
            return 'All users need to complete this contract.';
        }

        $message = $contract->name . ' (' . $users->count() . ')' . PHP_EOL;

        return $message . '- ' . $users->implode('username', PHP_EOL . '- ');
    }

    public function help(): string
    {
        $this->isAdmin();
        return '{Contract ID} - Get users that do not have the contract.';
    }

    public function description(): string
    {
        return 'Get users that do not have the contract.';
    }

    public function options(): array
    {
        $contracts = $this->getAvailableContractOptions();

        return [
            [
                'type'        => 3,
                'name'        => 'contract_id',
                'description' => 'Contract ID',
                'required'    => true,
                'choices'     => $contracts,
            ],
        ];
    }
}
