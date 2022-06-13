<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendRocketNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public $mission;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $mission)
    {
        $this->user = $user;
        $this->mission = $mission;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $channel = app()->make('DiscordClientBot')->user->createDm([
            'recipient_id' => $this->user->discord_id,
        ]);

        app()->make('DiscordClientBot')->channel->createMessage([
            'channel.id' => (int) $channel['id'],
            'content'    => $this->mission->ship . ' has returned.  Use `eb!unsubscribe-to-rockets` to be removed.',
        ]);
    }
}
