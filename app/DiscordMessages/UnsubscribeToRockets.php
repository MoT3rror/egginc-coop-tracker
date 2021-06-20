<?php
namespace App\DiscordMessages;

class UnsubscribeToRockets extends base
{
    public function message(): string
    {
        $this->user->subscribe_to_rockets = false;
        $this->user->save();

        return 'You are now unsubscribe for rocket notifications. Use `eb!subscribe-to-rockets` to get notifications again.';
    }
}