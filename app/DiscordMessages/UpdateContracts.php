<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;

class UpdateContracts extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        try {
            Artisan::call(
                'contracts:update',
                [
                    'clientVersion' => Arr::get($this->parts, 1),
                    'appVersion'    => Arr::get($this->parts, 2),
                ]
            );
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return 'Contracts have been updated.';
    }

    public function help(): string
    {
        return '{Client Version} {App Version} - Update contracts the bot knows about.';
    }
}
