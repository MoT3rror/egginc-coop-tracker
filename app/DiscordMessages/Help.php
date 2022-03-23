<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;

class Help extends Base
{
    public $globalSlash = true;

    public function message(): array
    {
        $commands = app()->make('DiscordMessages');

        if (count($this->parts) > 1) {
            if (array_key_exists($this->parts[1], $commands)) {
                $command = $commands[$this->parts[1]];
                $start = $this->parts[1];
                $class = $command['class'];
                try {
                    $commandObject = new $class($this->authorId, $this->authorName, $this->guildId, $this->channelId, $this->parts);
                    if ($commandObject->help()) {
                        return ['eb!' . $start . ' ' .  $commandObject->help()];
                    }
                } catch (DiscordErrorException $e) {
                    return ['Command not found'];
                }
            }
            return ['Command not found'];
        }

        $messages = [];
        $message = '```' . PHP_EOL;

        $count = 0;
        foreach ($commands as $start => $command) {
            if (isset($command['duplicate']) && $command['duplicate']) {
                continue;
            }
            $helpText = '';
            $class = $command['class'];
            try {
                $commandObject = new $class($this->authorId, $this->authorName, $this->guildId, $this->channelId, $this->parts);
                if ($commandObject->help()) {
                    $helpText = 'eb!' . $start . ' ' .  $commandObject->help(); 
                }
            } catch (DiscordErrorException $e) {}
            if ($helpText) {
                $count++;
                $message .= $helpText . PHP_EOL;
            }

            if ($count > 19) {
                $messages[] = $message . '```';
                $message = '```' . PHP_EOL;
                $count = 0;
            }
        }

        $message .= '```';
        $messages[] = $message;
        return $messages;
    }

    public function help(): string
    {
        return '- ' . $this->description();
    }

    public function description(): string
    {
        return 'Display list of available commands.';
    }
}
