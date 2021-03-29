<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;

class Help extends Base
{
    public function message(): string
    {
        $commands = [
            Help::class,
            Add::class,
            Available::class,
            Contracts::class,
            CoopLeaderBoard::class,
            Delete::class,
            Ge::class,
            Hi::class,
            ModSetPlayerId::class,
            Players::class,
            PlayersNotInCoop::class,
            Rank::class,
            Remind::class,
            Replace::class,
            SetPlayerId::class,
            Status::class,
            ShortStatus::class,
            Tracker::class,
            Unavailable::class,
        ];

        $message = '```' . PHP_EOL;

        foreach ($commands as $command) {
            $helpText = '';
            try {
                $commandObject = new $command($this->authorId, $this->authorName, $this->guildId, $this->channelId, $this->parts);
                $helpText = $commandObject->help(); 
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
        return 'eb!help - Display list of available commands.';
    }
}
