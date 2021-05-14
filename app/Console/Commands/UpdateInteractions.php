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
        foreach ($this->botGuilds() as $guild) {
            $this->setGuildCommands($guild->id);
        }
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
                'options'     => $commandClass->options(),
            ];
            if ($commandClass->globalSlash) {
                if ($currentlySetKeyed->has($commandString)) {
                    $currentlySetInfo = $currentlySetKeyed->get($commandString);
                    if ($this->compareDiscordVsOurs($currentlySetInfo, $commandData)) {
                        $this->httpClient()->post('/commands', $commandData)->json();
                    }
                    unset($currentlySetKeyed[$commandString]);
                } else {
                    $this->httpClient()->post('/commands', $commandData)->json();
                }
            }
        }

        foreach ($currentlySetKeyed as $command) {
            $this->httpClient()->delete('/guilds/' . $guildId . '/commands/' . $command['id']);
        }
    }

    private function setGuildCommands($guildId)
    {
        $currentlySet = $this->httpClient()
            ->get('/guilds/' . $guildId . '/commands')
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
                'options'     => $commandClass->options(),
            ];

            if ($commandClass->guildOnly) {
                if ($currentlySetKeyed->has($commandString)) {
                    $currentlySetInfo = $currentlySetKeyed->get($commandString);
                    if ($this->compareDiscordVsOurs($currentlySetInfo, $commandData)) {
                        $this->httpClient()->post('/guilds/' . $guildId . '/commands', $commandData)->json();
                    }
                    unset($currentlySetKeyed[$commandString]);
                } else {
                    $this->httpClient()->post('/guilds/' . $guildId . '/commands', $commandData)->json();
                }
            }
        }

        foreach ($currentlySetKeyed as $command) {
            $this->httpClient()->delete('/guilds/' . $guildId . '/commands/' . $command['id']);
        }
    }

    private function compareDiscordVsOurs($discord, $ours): bool
    {
        $discord = collect($discord)->only(['name', 'description', 'options']);
        if (!$discord->has('options')) {
            $discord['options'] = [];
        }
        return $this->different($discord, $ours);
    }

    private function different($array1, $array2): bool
    {
        return json_encode($array1) !== json_encode($array2);
    }

    private function httpClient()
    {
        return $currentlySet = Http::withHeaders([
            'Authorization' => 'Bot ' . config('services.discord.token'),
        ])
            ->withOptions([
                'http_errors' => true,
            ])
            ->baseUrl('https://discord.com/api/v8/applications/' . config('services.discord.client_id'))
        ;
    }

    private function botGuilds()
    {
        return app()->make('DiscordBotGuilds');
    }
}
