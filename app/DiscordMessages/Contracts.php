<?php
namespace App\DiscordMessages;

use App\Models\Contract;

class Contracts extends Base
{
    public $globalSlash = true;

    public function message(): string
    {
        $contracts = $this->getContractsInfo();

        $message[] = '```';

        foreach ($contracts as $contract) {
            $message[] = $contract->identifier . ' (' . $contract->raw_data->name . ') ' . $contract->expiration->format('m-d-Y');
        }
        $message[] = '```';

        return implode("\n", $message);
    }

    public function getContractsInfo()
    {
        return Contract::getAllActiveContracts();
    }

    public function help(): string
    {
        return '- Display current contracts with IDs and expiration.';
    }

    public function description(): string
    {
        return 'Display current contract with IDs and expiration.';
    }
}
