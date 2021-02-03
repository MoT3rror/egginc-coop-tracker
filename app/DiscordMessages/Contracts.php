<?php
namespace App\DiscordMessages;

use App\Models\Contract;

class Contracts extends Base
{
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
        return 'eb!contracts - Display current contracts with IDs.';
    }
}
