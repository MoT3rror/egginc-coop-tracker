<?php

namespace Tests\Unit\DiscordMessages;

use App\DiscordMessages\Love;

class LoveTest extends Base
{
    public function testMessage()
    {
        $message = $this->makeDiscordMessage(Love::class);
        $expects = 'What is this thing called love?';
        $actual = $message->message();
        $this->assertEquals($expects, $actual);
    }
}