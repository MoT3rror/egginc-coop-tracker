<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;
use App\Models\Coop;
use Illuminate\Support\Arr;

class Add extends Base
{
    protected $middlewares = ['requiresGuild',];

    public $guildOnly = true;

    protected function canAdd()
    {
        $this->guild->sync();
        $admin = false;
        foreach ($this->guild->roles as $role) {
            if (($role->is_admin || $role->can_add) && $role->members->contains('discord_id', $this->authorId))  {
                $admin = true;
                break;
            }
        }
        if (!$admin) {
            throw new DiscordErrorException('You are not allowed to do that.');
        }
    }

    public function message(): string
    {
        $this->canAdd();

        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            throw new DiscordErrorException('Contract ID required');
        }

        if (!Arr::get($parts, 2)) {
            throw new DiscordErrorException('Coop name is required');
        }
        
        $this->getContractInfo($parts[1]);

        if (isset($parts[3])) { // do we have more than one coop name parameter?
            $position = 1;

            foreach ($parts as $key => $part) {
                if (in_array($key, [0, 1])) {
                    continue;
                }

                Coop::unguarded(function () use ($parts, $part, $position) {
                    Coop::updateOrCreate(
                        [
                            'contract' => $parts[1],
                            'coop'     => $part,
                            'guild_id' => $this->guildId,
                        ],
                        [
                            'position' => $position,
                        ]
                    );
                });

                $position++;
            }

            return 'Coops added successfully.';
        } else { // there is only one coop name parameter
            $coopCheck = Coop::contract($parts[1])
                ->guild($this->guildId)
                ->coop($parts[2])
                ->first()
            ;

            if ($coopCheck) {
                return 'Coop is already being tracked.';
            }

            $coop = new Coop([
                'contract' => $parts[1],
                'coop'     => $parts[2],
            ]);
            $coop->guild_id = $this->guildId;
            if ($coop->save()) {
                return 'Coop added successfully.';
            } else {
                return 'Was not able to add coop.';
            }
        }
    }

    public function help(): string
    {
        $this->canAdd();
        return '{Contract ID} {Coop} {?Coop} - Add coop to tracking, multiple can be added by this command. When multiple is added, the position of the coops is set.';
    }

    public function description(): string
    {
        return 'Add coop to tracker.';
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
        ];
    }
}
