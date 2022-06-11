<?php

namespace App\Console\Commands;

use App\Models\Guild;
use Illuminate\Console\Command;

class SyncGuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-guild {guildId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Guild';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $guild = Guild::findByDiscordGuildId($this->argument('guildId'));
        $guild->last_sync = null;
        $startTime = microtime(true);
        $guild->sync();
        $endTime = microtime(true);
        
        $this->info('Roles/Users have been synced. Time taken: ' . ($endTime - $startTime));
        return 0;
    }
}
