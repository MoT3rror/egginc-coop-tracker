<?php

namespace Tests\Unit\DiscordMessages;

use Tests\TestCase;

class Base extends TestCase
{
    public function makeDiscordMessage($class, $authorId = 1, $authorName = 'Test')
    {
        return new $class($authorId, $authorName);
    }
}