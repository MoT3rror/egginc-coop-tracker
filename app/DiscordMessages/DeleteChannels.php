<?php
namespace App\DiscordMessages;

class DeleteChannels extends Status
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
            $coop->deleteChannel();
        }
        
        return ['Coop channels have been deleted.'];
    }

    public function help(): string
    {
        return '{Contract ID} - Delete coop channels in mass.';
    }
}
