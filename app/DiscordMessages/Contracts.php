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
            $message[] = $contract->identifier . '(' . $contract->name . ')';
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
}
