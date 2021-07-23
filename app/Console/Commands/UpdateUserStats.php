<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-user-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grab snapshot of user stats';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('keep_stats', 1)->get();

        foreach ($users as $user) {
            $user->createUserStat();
        }
    }
}
