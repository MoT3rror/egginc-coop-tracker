<?php

namespace App\Console\Commands;

use GuzzleHttp\Exception\RequestException;
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
        $this->info('Setting global commands');
        $this->setGlobalCommands();
        $this->info('Done setting global commands');
        foreach ($this->botGuilds() as $guild) {
            sleep(15);
            $this->info('Start guild ' . $guild['id']);
            $this->setGuildCommands($guild['id']);
            $this->info('end guild ' . $guild['id']);
        }
    }

    private function setGlobalCommands()
    {
        $currentlySet = $this->httpClient()
            ->get('/commands')
        ;
        $currentlySetKeyed = collect($currentlySet->json())->keyBy('name');

        $messages = app()->make('DiscordMessages');
        $commands = [];

        foreach ($messages as $command => $message) {
            $class = $message['class'];
            $commandClass = new $class(1, 'Slashes', null, null, [], true);

            if (!$commandClass->globalSlash) {
                continue;
            }

            $commandString = 'eb' . $command;
            $commandData = [
                'name'        => $commandString,
                'description' => $commandClass->description(),
                'options'     => $commandClass->options(),
                'type'        => 1,
            ];

            if ($currentlySetKeyed->has($commandString)) {
                unset($currentlySetKeyed[$commandString]);
            }
            $commands[] = $commandData;
        }

        $this->httpClient()->put('/commands', array_values($commands))->json();

        foreach ($currentlySetKeyed as $command) {
            $this->httpClient()->delete('/commands/' . $command['id']);
        }
    }

    private function setGuildCommands($guildId)
    {
        try {
            $currentlySet = $this->httpClient()
                ->get('/guilds/' . $guildId . '/commands')
            ;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return;
        }
        $currentlySetKeyed = collect($currentlySet->json())->keyBy('name');

        $messages = app()->make('DiscordMessages');
        $commands = [];

        foreach ($messages as $command => $message) {
            $class = $message['class'];
            $commandClass = new $class(1, 'Slashes', null, null, [], true);
            if (!$commandClass->guildOnly) {
                continue;
            }
            $commandString = 'eb' . $command;
            $commandData = [
                'name'        => $commandString,
                'description' => $commandClass->description(),
                'options'     => $commandClass->options(),
                'type'        => 1,
            ];

            if ($currentlySetKeyed->has($commandString)) {
                unset($currentlySetKeyed[$commandString]);
            }
            $commands[] = $commandData;
        }

        try {
            $this->httpClient()->put('/guilds/' . $guildId . '/commands', array_values($commands))->json();
        } catch (RequestException $e) {
            dd($e->getResponse()->getBody()->getContents(), $commands);
        }

        foreach ($currentlySetKeyed as $command) {
            $this->httpClient()->delete('/guilds/' . $guildId . '/commands/' . $command['id']);
        }
    }

    private function httpClient()
    {
        return Http::withHeaders([
            'Authorization' => 'Bot ' . config('services.discord.token'),
        ])
            ->withOptions([
                'http_errors' => true,
            ])
            ->baseUrl('https://discord.com/api/v9/applications/' . config('services.discord.client_id'))
        ;
    }

    private function botGuilds(): array
    {
        return app()->make('DiscordBotGuilds')->toArray();
    }
}
