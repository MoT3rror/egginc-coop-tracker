<?php
namespace App\DiscordMessages;

use Illuminate\Support\Arr;

class SetCoopChannelPermission extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {
        $parts = $this->parts;

        $this->guild->group_permissions = Arr::get($parts, 1, null);
        $this->guild->save();

        return 'Coop channel category set #' . Arr::get($parts, 1, null);
    }

    public function help(): string
    {
        return '{Permission number} - Set permission for users/roles when channel is created. Default = 3072. Send no number to set default. Use https://discordapi.com/permissions.html to get number.';
    }
}
