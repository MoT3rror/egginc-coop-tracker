<?php
namespace App\DiscordMessages;

class SubscribeToRockets extends Base
{
    public $globalSlash = true;

    public function message(): string
    {
        $this->user->subscribe_to_rockets = true;
        $this->user->save();

        return 'You are now subscribed for rocket notifications. Use `eb!unsubscribe-to-rockets` to remove.';
    }

    public function help(): string
    {
        return 'Subscribe to notifications when your rockets come back.';
    }

    public function description(): string
    {
        return 'Subscribe to notifications when your rockets come back.';
    }
}