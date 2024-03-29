<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\User;
use Illuminate\Support\Arr;

class AddMember extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = true;

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        if (!Arr::get($parts, 2)) {
            return 'Coop name required';
        }

        if (!Arr::get($parts, 3)) {
            return 'Member required';
        }

        $coop = Coop::query()
            ->guild($this->guild->discord_id)
            ->contract($parts[1])
            ->coop($parts[2])
            ->first()
        ;

        $coopWasCreated = false;
        if (!$coop) {
            $coop = new Coop;
            $coop->guild_id = $this->guild->discord_id;
            $coop->contract = $parts[1];
            $coop->coop = $parts[2];
            $coop->save();
            $coopWasCreated = true;
        }

        $user = $this->guild->members->firstWhere('discord_id', $this->cleanAt($parts[3]));

        if (!$user) {
            return 'User not found';
        }

        $memberExists = CoopMember::query()
            ->user($user->id)
            ->contractGuild($parts[1], $this->guild->discord_id)
            ->first()
        ;

        if ($memberExists) {
            $memberExists->coop->makeChannel();
            $memberExists->delete();
        }

        $coop->addMember($user);

        $coop->makeChannel();

        if (!$coopWasCreated) {
            $coop->sendMessageToChannel('I added <@' . $user->discord_id . '> to this coop.');
        }

        return 'Member added.';
    }

    public function help(): string
    {
        return '{contractID} {Coop} {member} - Add member to coop.';
    }

    public function description(): string
    {
        return 'Add member to coop.';
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
            [
                'type'        => 3,
                'name'        => 'coop',
                'description' => 'Coop',
                'required'    => true,
            ],
            [
                'type'        => 6,
                'name'        => 'user',
                'description' => 'User',
                'required'    => true,
            ]
        ];
    }
}
