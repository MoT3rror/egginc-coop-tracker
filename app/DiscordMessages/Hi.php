<?php
namespace App\DiscordMessages;

class Hi extends Base
{
    public function message(): string
    {
        return 'Hello <@' . $this->authorId . '>!';
    }
}
