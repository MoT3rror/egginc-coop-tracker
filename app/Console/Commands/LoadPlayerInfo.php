<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LoadPlayerInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'load-player-info-in-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load player info in cache before contract.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::query()
            ->withEggIncId()
            ->get()
        ;

        foreach ($users as $user) {
            $user->getEggPlayerInfo();
        }
    }
}
