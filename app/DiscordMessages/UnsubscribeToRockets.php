<?php
namespace App\DiscordMessages;

class UnsubscribeToRockets extends Base
{
    public $globalSlash = true;

    public function message(): string
    {
        $this->user->subscribe_to_rockets = false;
        $this->user->save();

        return 'You are now unsubscribed for rocket notifications. Use `eb!subscribe-to-rockets` to get notifications again.';
    }

    public function help(): string
    {
        return 'Unsubscribe to notifications when your rockets come back.';
    }

    public function description(): string
    {
        return 'Unsubscribe to notifications when your rockets come back.';
    }
}