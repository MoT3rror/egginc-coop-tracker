<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Illuminate\Support\Arr;

class Delete extends Base
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
            return 'Coop name is required';
        }

        $coops = array_slice($parts, 2);
        foreach ($coops as $coopName) {
            $coop = Coop::contract($parts[1])
                ->coop($coopName)
                ->guild($this->guildId)
                ->first()
            ;
    
            if (!$coop) {
                return 'Coop ' . $coopName . ' does not exist yet.';
            }
    
            if (!$coop->delete()) {
                return 'Was not able to delete the ' . $coopName . ' coop.';
            }
        }
        return 'Coops have been deleted.';
    }

    public function help(): string
    {
        return '{contractID} {Coop} - Remove coop from tracking';
    }

    public function description(): string
    {
        return 'Remove coop from tracking.';
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
