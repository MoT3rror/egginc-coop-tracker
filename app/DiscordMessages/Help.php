<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;

class Help extends Base
{
    public $slashSupport = true;

    public function message(): string
    {
        $commands = app()->make('DiscordMessages');

        $message = '```' . PHP_EOL;

        foreach ($commands as $start => $command) {
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
        }

        $message .= '```';
        return $message;
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
