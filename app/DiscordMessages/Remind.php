<?php
namespace App\DiscordMessages;

use App\Jobs\RemindCoopStatus;
use Arr;

class Remind extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): array
    {
        $parts = $this->parts;
        $contract = Arr::get($parts, 1);
        $hours = (int) Arr::get($parts, 2);
        $minutes = (int) Arr::get($parts, 3);

        if (!$contract) {
            return ['No contract set'];
        }

        if (!$hours || $hours > 24) {
            return ['Please set hours or hours should be less than 24.'];
        }

        if (!$minutes || $minutes <= 5) {
            return ['Please set minutes or should be greater than 5'];
        }

        for ($i = $minutes; $i <= ($hours * 60); $i += $minutes) {
            RemindCoopStatus::dispatch(
                $this->authorId,
                $this->guildId,
                $this->channelId,
                $contract
            )->delay(now()->addMinutes($i));
        }

        $status = new Status(
            $this->authorId,
            $this->authorName,
            $this->guildId,
            $this->channelId,
            $this->parts,
            $this->skipMiddleWareChecks
        );
        return array_merge(['Reminders set.'], $status->message());
    }

    public function help(): string
    {
        return 'eb!remind {Contract ID} {Hours} {Minutes}';
    }
}
