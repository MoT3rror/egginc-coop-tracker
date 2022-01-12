<?php
namespace App\DiscordMessages;

use App\Models\Role;
use Illuminate\Support\Arr;

class AvailableByRolesCount extends Base
{
    protected $middlewares = ['requiresGuild'];

    public function message(): string
    {
        $parts = $this->parts;
        $contractId = Arr::get($parts, '1');

        if (!$contractId) {
            return 'Contract ID is required.';
        }

        $roles = array_splice($parts, 2);
        $messages = [];
        foreach ($roles as $roleId) {
            $users = [];
            $id = $this->cleanAt($roleId);

            $role = Role::query()
                ->discordId((int) $id)
                ->guildId($this->guild->id)
                ->first()
            ;

            if ($role) {
                foreach ($role->members as $roleMember) {
                    if (!$roleMember->hasCompletedContract($parts[1])) {
                        $users[] = $roleMember->username;
                    }
                }

                $messages[] = $role->name . ' (' . count($users) . ')';
            }
        }

        return implode(PHP_EOL, $messages);
    }

    public function help(): string
    {
        return '{Contract ID} {@roles...} - Get who has not complete contract by roles.';
    }

    public function description(): string
    {
        return 'Get who has not complete contract by roles.';
    }
}
