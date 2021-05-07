<?php

namespace App\Providers;

use App\Api\EggInc;
use App\DiscordMessages\Add;
use App\DiscordMessages\Available;
use App\DiscordMessages\Contracts;
use App\DiscordMessages\CoopLeaderBoard;
use App\DiscordMessages\Delete;
use App\DiscordMessages\Ge;
use App\DiscordMessages\Help;
use App\DiscordMessages\Hi;
use App\DiscordMessages\Love;
use App\DiscordMessages\ModSetPlayerId;
use App\DiscordMessages\Players;
use App\DiscordMessages\PlayersNotInCoop;
use App\DiscordMessages\Rank;
use App\DiscordMessages\Remind;
use App\DiscordMessages\Replace;
use App\DiscordMessages\SetPlayerId;
use App\DiscordMessages\ShortStatus;
use App\DiscordMessages\Status;
use App\DiscordMessages\Tracker;
use App\DiscordMessages\Unavailable;
use App\Formatters\EarningBonus;
use App\Formatters\Egg;
use App\Formatters\TimeLeft;
use Cache;
use Illuminate\Support\ServiceProvider;
use RestCord\DiscordClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EggInc::class);
        $this->app->singleton(EarningBonus::class);
        $this->app->singleton(Egg::class);
        $this->app->singleton(TimeLeft::class);
        $this->app->bind(DiscordClient::class, function ($app, $options) {
            return new DiscordClient($options);
        });
        $this->app->bind('DiscordClientBot', function ($app) {
            return $app->makeWith(DiscordClient::class, [
                'token'     => config('services.discord.token'),
                'tokenType' => 'Bot',
            ]);
        });

        $this->app->bind('DiscordBotGuilds', function () {
            return Cache::remember('discord-bot-guilds', 60 * 5, function () {
                return $this->app->make('DiscordClientBot')->user->getCurrentUserGuilds();
            });
        });

        $this->app->bind('DiscordMessages', function () {
            return [
                'add'                 => ['class' => Add::class],
                'available'           => ['class' => Available::class],
                'contracts'           => ['class' => Contracts::class],
                'coop-leaderboard'    => ['class' => CoopLeaderBoard::class],
                'delete'              => ['class' => Delete::class],
                'ge'                  => ['class' => Ge::class],
                'help'                => ['class' => Help::class],
                'hi'                  => ['class' => Hi::class],
                'love'                => ['class' => Love::class],
                'mod-set-player-id'   => ['class' => ModSetPlayerId::class],
                'players'             => ['class' => Players::class],
                'players-not-in-coop' => ['class' => PlayersNotInCoop::class],
                'rank'                => ['class' => Rank::class],
                'remind'              => ['class' => Remind::class],
                'replace'             => ['class' => Replace::class],
                'set-player-id'       => ['class' => SetPlayerId::class],
                'status'              => ['class' => Status::class],
                's'                   => ['class' => ShortStatus::class],
                'tracker'             => ['class' => Tracker::class],
                'unavailable'         => ['class' => Unavailable::class],
            ];
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
