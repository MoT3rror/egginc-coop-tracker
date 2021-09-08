<?php

namespace App\Providers;

use App\Api\EggInc;
use App\DiscordMessages\Add;
use App\DiscordMessages\Available;
use App\DiscordMessages\BootWarning;
use App\DiscordMessages\Contracts;
use App\DiscordMessages\Contributions;
use App\DiscordMessages\CoopLeaderBoard;
use App\DiscordMessages\Delete;
use App\DiscordMessages\DeleteChannels;
use App\DiscordMessages\Ge;
use App\DiscordMessages\GetMyId;
use App\DiscordMessages\GetPlayerId;
use App\DiscordMessages\Help;
use App\DiscordMessages\Hi;
use App\DiscordMessages\LinkToRocketTracker;
use App\DiscordMessages\Links;
use App\DiscordMessages\Love;
use App\DiscordMessages\ModSetPlayerId;
use App\DiscordMessages\Ping;
use App\DiscordMessages\Players;
use App\DiscordMessages\PlayersNotInCoop;
use App\DiscordMessages\Rank;
use App\DiscordMessages\Remind;
use App\DiscordMessages\RemindUnfilled;
use App\DiscordMessages\Replace;
use App\DiscordMessages\RocketTracker;
use App\DiscordMessages\SetPlayerId;
use App\DiscordMessages\ShortStatus;
use App\DiscordMessages\SubscribeToRockets;
use App\DiscordMessages\Status;
use App\DiscordMessages\Sync;
use App\DiscordMessages\Tracker;
use App\DiscordMessages\Unavailable;
use App\DiscordMessages\UnsubscribeToRockets;
use App\DiscordMessages\Unfilled;
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
                'add'                    => ['class' => Add::class],
                'atrisk'                 => ['class' => BootWarning::class],
                'available'              => ['class' => Available::class],
                'boot-warning'           => ['class' => BootWarning::class],
                'contracts'              => ['class' => Contracts::class],
                'contributions'          => ['class' => Contributions::class],
                'coopless'               => ['class' => PlayersNotInCoop::class],
                'coop-leaderboard'       => ['class' => CoopLeaderBoard::class],
                'delete'                 => ['class' => Delete::class],
                'delete-channels'        => ['class' => DeleteChannels::class],
                'ge'                     => ['class' => Ge::class],
                'get-my-id'              => ['class' => GetMyId::class],
                'get-player-id'          => ['class' => GetPlayerId::class],
                'help'                   => ['class' => Help::class],
                'hi'                     => ['class' => Hi::class],
                'leaders'                => ['class' => CoopLeaderBoard::class],
                'link-to-rocket-tracker' => ['class' => LinkToRocketTracker::class],
                'links'                  => ['class' => Links::class],
                'love'                   => ['class' => Love::class],
                'mod-set-player-id'      => ['class' => ModSetPlayerId::class],
                'ping'                   => ['class' => Ping::class],
                'players'                => ['class' => Players::class],
                'players-not-in-coop'    => ['class' => PlayersNotInCoop::class],
                'rank'                   => ['class' => Rank::class],
                'remind'                 => ['class' => Remind::class],
                'remind-unfilled'        => ['class' => RemindUnfilled::class],
                'replace'                => ['class' => Replace::class],
                'rocket-tracker'         => ['class' => RocketTracker::class],
                'set-player-id'          => ['class' => SetPlayerId::class],
                'short-status'           => ['class' => ShortStatus::class],
                'status'                 => ['class' => Status::class],
                'subscribe-to-rockets'   => ['class' => SubscribeToRockets::class],
                'sync'                   => ['class' => Sync::class],
                'tracker'                => ['class' => Tracker::class],
                'unavailable'            => ['class' => Unavailable::class],
                'unsubscribe-to-rockets' => ['class' => UnsubscribeToRockets::class],
                'unfilled'               => ['class' => Unfilled::class],
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
