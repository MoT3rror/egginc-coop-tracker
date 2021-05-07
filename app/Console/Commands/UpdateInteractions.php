<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdateInteractions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-interactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Discord Interactions (slash commands)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->setGlobalCommands();
    }

    private function setGlobalCommands()
    {
        $currentlySet = $this->httpClient()
            ->get('/commands')
        ;
        $currentlySetKeyed = collect($currentlySet->json())->keyBy('name');

        $messages = app()->make('DiscordMessages');

        foreach ($messages as $command => $message) {
            $class = $message['class'];
            $commandClass = new $class(1, 'Slashes', null, null, [], true);
            $commandString = 'eb' . $command;
            $commandData = [
                'name'        => $commandString,
                'description' => $commandClass->description(),
            ];
            if ($commandClass->globalSlash) {
                if ($currentlySetKeyed->has($commandString)) {
                    $currentlySetInfo = $currentlySetKeyed->get($commandString);
                    if (collect($currentlySetInfo)->only(['name', 'description'])->diff($commandData)->count() !== 0) {
                        $this->httpClient()->post('/commands', $commandData)->json();
                    }
                } else {
                    $this->httpClient()->post('/commands', $commandData)->json();
                }
            }
        }
    }

    private function httpClient()
    {
        return $currentlySet = Http::withHeaders([
            'Authorization' => 'Bot ' . config('services.discord.token'),
        ])
            ->baseUrl('https://discord.com/api/v8/applications/' . config('services.discord.client_id'))
        ;
    }

    private function botGuilds()
    {
        return app()->make('DiscordBotGuilds');
    }
}
