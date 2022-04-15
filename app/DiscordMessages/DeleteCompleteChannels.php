<?php
namespace App\DiscordMessages;

class DeleteCompleteChannels extends Status
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = true;

    public function message(): array
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return [$coops];
        }

        foreach ($coops as $coop) {
            try {
                if ($coop->isComplete()) {
                    $coop->deleteChannel();
                }
            } catch (\App\Exceptions\CoopNotFoundException $e) {
            }
        }
        
        return ['Coop channels have been deleted.'];
    }

    public function help(): string
    {
        return '{Contract ID} - Delete coop channels in mass that are complete.';
    }

    public function description(): string
    {
        return 'Delete coop channels in mass that are complete.';
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
