<?php
namespace App\DiscordMessages;

use App\Models\Coop;

class SendInitialMessage extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];
    
    public $coop;

    public $guildOnly = true;

    public function message(): array
    {
        $this->coop = Coop::query()
            ->channelId($this->channelType == 'GUILD_PUBLIC_THREAD' ? $this->channelParent : $this->channelId)
            ->first()
        ;
        if (!$this->coop) {
            return ['This is not a coop channel.'];
        }

        return $this->coop->getInitialMessage();
    }

    public function help(): string
    {
        return 'Sends initial coop message again.';
    }

    public function description(): string
    {
        return 'Sends initial coop message again.';
    }

    public function options(): array
    {
        return [];
    }
}