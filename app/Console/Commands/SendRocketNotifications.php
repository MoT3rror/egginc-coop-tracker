<?php

namespace App\Console\Commands;

use App\Jobs\SendRocketNotification;
use App\Models\User;
use Illuminate\Console\Command;

class SendRocketNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-rocket-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Triggers events to send notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::where('subscribe_to_rockets', true)->get();
        foreach ($users as $user) {
            $playerInfo = $user->getEggPlayerInfo();

            if (!$playerInfo) {
                continue;
            }

            foreach ($playerInfo->artifactsDb->missionInfos as $mission) {
                if ($mission->status != 'EXPLORING') {
                    continue;
                }
                $timeLeft = $mission->durationSeconds + round($mission->startTimeDerived) - time();
                if ($timeLeft > 60 * 20) {
                    continue;
                }

                SendRocketNotification::dispatch($user, $mission)->delay(now()->addSeconds($timeLeft));
                break;
            }
        }
    }
}
