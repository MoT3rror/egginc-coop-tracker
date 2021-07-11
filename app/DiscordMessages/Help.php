<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;

class Help extends Base
{
    public $globalSlash = true;

    public function message(): array
    {
        $commands = app()->make('DiscordMessages');

        $messages = [];
        $message = '```' . PHP_EOL;

        $count = 0;
        foreach ($commands as $start => $command) {
            $count++;
            $helpText = '';
            $class = $command['class'];
            try {
                $commandObject = new $class($this->authorId, $this->authorName, $this->guildId, $this->channelId, $this->parts);
                if ($commandObject->help()) {
                    $helpText = 'eb!' . $start . ' ' .  $commandObject->help(); 
                }
            } catch (DiscordErrorException $e) {}
            if ($helpText) {
                $message .= $helpText . PHP_EOL;
            }

            if ($count > 25) {
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
