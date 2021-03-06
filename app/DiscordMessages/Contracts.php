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
            $message[] = $contract->id . '(' . $contract->name . ')';
        }
        $message[] = '```';

        return implode("\n", $message);
    }

    public function getContractsInfo()
    {
        return Contract::getAllActiveContracts()
            ->getInRawFormat()
        ;
    }

    public function help(): string
    {
        return '- Display current contracts with IDs.';
    }

    public function description(): string
    {
        return 'Display current contract with IDs.';
    }
}
