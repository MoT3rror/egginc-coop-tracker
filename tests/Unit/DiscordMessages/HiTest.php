<?php

namespace Tests\Unit\DiscordMessages;

use App\DiscordMessages\Hi;

class HiTest extends Base
{
    public function testMessage()
    {
        $message = $this->makeDiscordMessage(Hi::class);
        $expects = 'Hello <@1>!';
        $actual = $message->message();
        $this->assertEquals($expects, $actual);
    }
}