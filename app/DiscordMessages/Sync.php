<?php
namespace App\DiscordMessages;

class Sync extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $guild = $this->guild;
        $guild->last_sync = null;
        $guild->sync();
        
        return 'Roles/Users have been synced.';
    }

    public function help(): string
    {
        return '- Update roles/users that bot knows about.';
    }
}
