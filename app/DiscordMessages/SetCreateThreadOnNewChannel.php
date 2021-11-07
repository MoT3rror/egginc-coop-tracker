<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Illuminate\Support\Arr;

class SetCreateThreadOnNewChannel extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1) || !in_array(strtolower($parts['1']), ['yes', 'no'])) {
            return 'yes or no required';
        }

        $this->guild->create_thread_on_new_channel = strtolower($parts[1]) == 'yes' ? true : false;
        $this->guild->save();

        return 'Coop channel category set ' . $parts[1];
    }

    public function help(): string
    {
        return '{yes or no} - Will create a thread called "Bot Commands" on create of coop channel.';
    }
}
