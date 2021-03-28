<?php
namespace App\DiscordMessages;

class Hi extends Base
{
    public function message(): string
    {
        return 'Hello <@' . $this->authorId . '>!';
    }

    public function help(): string
    {
        return 'eb!hi Just say hi.';
    }
}
