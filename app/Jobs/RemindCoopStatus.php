<?php

namespace App\Jobs;

use App\DiscordMessages\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemindCoopStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $author;

    protected $guild;

    protected $channel;

    protected $contract;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($author, $guild, $channel, $contract)
    {
        $this->author = $author;
        $this->guild = $guild;
        $this->channel = $channel;
        $this->contract = $contract;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = new Status(
            $this->author,
            'Author',
            $this->guild,
            $this->channel,
            ['status', $this->contract]
        );

        if ($message == 'Invalid contract ID or no coops setup.') {
            return;
        }
        $messageContent = $message->message();

        foreach ($messageContent as $value) {
            app()->make('DiscordClientBot')->channel->createMessage([
                'channel.id' => $this->channel,
                'content'    => $value,
            ]);
        }

    }
}
