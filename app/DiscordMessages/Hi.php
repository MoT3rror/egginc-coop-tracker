<?php
namespace App\DiscordMessages;

class Hi extends Base
{
    public function message(): string
    {
        $message[] = 'Hello <@' . $this->authorId . '>!';
        // add multi line output for multi part input
        $parts = $this->parts;
        foreach ($parts as $key => $part) {
            // skip command name
            if (in_array($key, [0])) {
                continue;
            }

            $message[] = 'Hello <@' . $part . '>!';
        }
    return implode("\n", $message);
    }
}
