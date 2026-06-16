<?php
namespace App\DiscordMessages;

use App\Models\Role;
use Illuminate\Support\Arr;

class AvailableByRolesCount extends Base
{
    protected $middlewares = ['requiresGuild'];

    public $guildOnly = true;

    public function message(): string
    {
        $parts = $this->parts;
        $contractId = Arr::get($parts, '1');

        if (!$contractId) {
            return 'Contract ID is required.';
        }

        $contract = $this->getContractInfo($contractId);

        $roles = Role::query()
            ->where('part_of_team', true)
            ->guildId($this->guild->id)
            ->get()
        ;
        $messages = [];
        foreach ($roles as $role) {
            $users = [];

            foreach ($role->members as $roleMember) {
                if (!$roleMember->hasCompletedContract($contract->identifier)) {
                    $users[] = $roleMember->username;
                }
            }

            $messages[] = $role->name . ' (' . count($users) . ')';
        }

        return implode(PHP_EOL, $messages);
    }

    public function help(): string
    {
        $this->isAdmin();
        return '{Contract ID} - Get who has not completed contract by roles that are part of the team.';
    }

    public function description(): string
    {
        return 'Get who has not complete contract by roles.';
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
