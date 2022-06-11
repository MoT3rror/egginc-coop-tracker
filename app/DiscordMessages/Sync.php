<?php
namespace App\DiscordMessages;

class Sync extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public $guildOnly = true;

    public function message(): string
    {
        $guild = $this->guild;
        $guild->last_sync = null;
        $startTime = microtime(true);
        $guild->sync();
        $endTime = microtime(true);
        
        return 'Roles/Users have been synced. Time taken: ' . ($endTime - $startTime);
    }

    public function help(): string
    {
        return '- Update roles/users that bot knows about.';
    }

    public function description(): string
    {
        return $this->help();
    }
}
