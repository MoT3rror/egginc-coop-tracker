<?php
namespace App\DiscordMessages;

class SubscribeToRockets extends Base
{
    public function message(): string
    {
        $this->user->subscribe_to_rockets = true;
        $this->user->save();

        return 'You are now subscribe for rocket notifications. Use `eb!unsubscribe-to-rockets` to remove.';
    }
}