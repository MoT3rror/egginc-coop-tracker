<?php
namespace App\DiscordMessages;

class Help extends Base
{
    public function message(): string
    {
        $commands = [
            Help::class,
            Add::class,
            Contracts::class,
            Delete::class,
            Players::class,
            Rank::class,
            Remind::class,
            SetPlayerId::class,
            Status::class,
            ShortStatus::class,
        ];

        $message = '```' . PHP_EOL;

        foreach ($commands as $command) {
            $commandObject = new $command($this->authorId, $this->authorName, $this->guildId, $this->channelId, $this->parts);
            $helpText = $commandObject->help();
            if ($helpText) {
                $message .= $helpText . PHP_EOL;
            }
        }

        $message .= '```';
        return $message;
    }

    public function help(): string
    {
        return 'eb!help - Display list of available commands.';
    }
}
