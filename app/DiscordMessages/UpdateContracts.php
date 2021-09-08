<?php
namespace App\DiscordMessages;

use Illuminate\Support\Facades\Artisan;

class UpdateContracts extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {        
        Artisan::call('contracts:update');

        return 'Contracts have been updated.';
    }

    public function help(): string
    {
        return '- Update contracts the bot knows about.';
    }
}
