<?php

namespace Tests\Unit\DiscordMessages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Base extends TestCase
{
    use RefreshDatabase;

    public function makeDiscordMessage($class, $parts = [], $authorId = 123456, $authorName = 'Test', $guildId = 1, $channelId = 1, $skipMiddleware = false)
    {
        $this->mockGuildCall();

        return new $class($authorId, $authorName, $guildId, $channelId, $parts, $skipMiddleware);
    }
}