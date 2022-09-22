<?php

namespace App\Jobs;

use App\Models\Guild;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class CleanUpMembers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $guild;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Guild $guild)
    {
        $this->guild = $guild;
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->guild->id))->expireAfter(180)];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->guild->cleanUpOldMembers();
    }
}
