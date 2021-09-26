<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use Illuminate\Support\Arr;

class SetCoopChannelParent extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Channel category required';
        }

        $this->guild->coop_channel_parent = $parts[1];
        $this->guild->save();

        return 'Coop channel category set #' . $parts[1];
    }

    public function help(): string
    {
        return '{Channel category ID} - May need to enable developer mode to grab ID. Set by website for easier dropdown';
    }
}
