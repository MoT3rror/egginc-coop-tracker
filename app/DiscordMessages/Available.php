<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;

class Available extends Base
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
                return !in_array($contractId, $user->getCompleteContractsAttribute());
            })
        ;
        if ($users->count() === 0) {
            return 'All users have completed this contract.';
        }

        $message = $contract->name . ' (' . $users->count() . ')' . PHP_EOL;

        return $message . '- ' . $users->implode('username', PHP_EOL . '- ');
    }

    public function help(): string
    {
        return '{Contract ID} - Get who has not complete contract. Will not validate contract ID.';
    }

    public function description(): string
    {
        return 'Get who has not complete contract. Will not validate contract ID.';
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
