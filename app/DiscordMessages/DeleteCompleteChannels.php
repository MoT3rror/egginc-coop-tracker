<?php
namespace App\DiscordMessages;

class DeleteCompleteChannels extends Status
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = false;

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
}
