<?php

namespace App\Models;

use App\Api\EggInc;
use App\Formatters\EarningBonus;
use App\Formatters\Egg;
use GuzzleHttp\Client;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RestCord\DiscordClient;
use StdClass;

class User extends Authenticatable
{
    use Notifiable;

    protected $casts = [
        'discord_token_expires' => 'datetime',
    ];

    protected $appends = ['player_earning_bonus_formatted', 'player_egg_rank', 'drones', 'soul_eggs', 'eggs_of_prophecy', 'player_earning_bonus', 'soul_eggs_needed_for_next_rank', 'p_e_needed_for_next_rank'];

    protected $with = ['roles'];

    public function getCurrentDiscordToken()
    {
        if ($this->discord_token_expires->lt(now())) {
            $client = new Client;
            $response = $client->request('POST', 'https://discord.com/api/v6/oauth2/token', [
                'form_params' => [
                    'client_id'     => config('services.discord.client_id'),
                    'client_secret' => config('services.discord.client_secret'),
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $this->discord_refresh_token,
                    'redirect_url'  => config('services.discord.redirect'),
                    'scope'         => 'identify email guilds',
                ],
            ]);
            $data = json_decode($response->getBody());
            $this->discord_token = $data->access_token;
            $this->discord_token_expires = now()->addSeconds($data->expires_in);
            $this->discord_refresh_token = $data->refresh_token;
            $this->save();
        }
        return $this->discord_token;
    }

    public function discordGuilds()
    {
        $discord = new DiscordClient([
            'token'     => $this->getCurrentDiscordToken(),
            'tokenType' => 'OAuth',
        ]);
        $guilds = $discord->user->getCurrentUserGuilds();

        foreach ($guilds as $key => $guild) {
            $guild->isAdmin = $guild->permissions & 8;
            // weird bug with vue or something that causes this number to change
            $guild->id = (string) $guild->id;
            $guildModel = Guild::findByDiscordGuild($guild);

            if (!$guild->isAdmin && !$guildModel->getIsBotMemberOfAttribute()) {
                unset($guilds[$key]);
            }
        }
        return $guilds;
    }

    public function guilds()
    {
        return $this->belongsToMany(Guild::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function getEggPlayerInfo(): ?StdClass
    {
        if (!$this->egg_inc_player_id) {
            return null;
        }
        return resolve(EggInc::class)->getPlayerInfo($this->egg_inc_player_id);
    }

    public function getCurrentContracts(): array
    {
        return $this->getEggPlayerInfo()->contracts->contractsList;
    }

    public function getEggsOfProphecyAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }

        return $info->game->eggsOfProphecy;
    }

    public function getEachSoulEggBonus(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }

        $epicResearch = collect($info->game->epicResearchList);
        $prophecyBonus = $epicResearch->where('id', 'prophecy_bonus')->first()->level;
        $soulBonus = $epicResearch->where('id', 'soul_eggs')->first()->level;
        $eggsOfProphecy = $this->getEggsOfProphecyAttribute();

        return floor(((.1 + $soulBonus * .01) * (1.05 + $prophecyBonus * .01) ** $eggsOfProphecy) * 100);
    }

    public function getPlayerEarningBonus(): float
    {
        return floor($this->getEachSoulEggBonus() * $this->getSoulEggsAttribute());
    }

    public function getPlayerEarningBonusAttribute(): float
    {
        return $this->getPlayerEarningBonus();
    }

    public function getSoulEggsAttribute(): float
    {
        $info = $this->getEggPlayerInfo();

        if (!$info) {
            return 0;
        }

        return $info->game->soulEggsD;
    }

    public function getSoulEggsFormattedAttribute(): string
    {
        return resolve(Egg::class)->format($this->getSoulEggsAttribute(), 3);
    }

    public function getPlayerEarningBonusFormatted(): string
    {
        return resolve(EarningBonus::class)->format($this->getPlayerEarningBonus());
    }

    public function getPlayerEarningBonusFormattedAttribute(): string
    {
        return $this->getPlayerEarningBonusFormatted();
    }

    public function getPlayerEggRankInfo(): ?StdClass
    {
        $roles = json_decode(file_get_contents(base_path('resources/js/roleMagnitude.json')));
        $earningBonus = $this->getPlayerEarningBonus();

        $last = null;
        foreach ($roles as $role) {
            if ($earningBonus / pow(10, $role->magnitude) < 1) {
                break;
            }
            $last = $role;
        }
        return $last;
    }

    public function getPlayerEggRank(): string
    {
        $last = $this->getPlayerEggRankInfo();
        if (!$last) {
            return '';
        }
        return $last->name;
    }

    public function getPlayerEggRankAttribute(): string
    {
        return $this->getPlayerEggRank();
    }

    public function getDronesAttribute(): int
    {
        $info = $this->getEggPlayerInfo();

        if (!$info) {
            return 0;
        }
        return $info->stats->droneTakedowns;
    }

    public function getPENeededForNextRankAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }

        if (!$this->getPlayerEggRankInfo()) {
            return -1;
        }

        $nextLevelMagnitude = $this->getPlayerEggRankInfo()->magnitude + 1;
        $nextLevelEarningBonus = pow(10, $nextLevelMagnitude);
        $epicResearch = collect($info->game->epicResearchList);
        $prophecyBonus = $epicResearch->where('id', 'prophecy_bonus')->first()->level;
        $soulBonus = $epicResearch->where('id', 'soul_eggs')->first()->level;
        $eggsOfProphecy = $this->getEggsOfProphecyAttribute();
        $soulEggs = $this->getSoulEggsAttribute();

        while ($eggsOfProphecy <= $this->getEggsOfProphecyAttribute() + 25) {
            $newEarningBonus = floor(((.1 + $soulBonus * .01) * (1.05 + $prophecyBonus * .01) ** $eggsOfProphecy) * 100) * $soulEggs;
            if ($newEarningBonus > $nextLevelEarningBonus) {
                return $eggsOfProphecy;
            }
            $eggsOfProphecy++;
            
        }

        return -1;
    }

    public function getSoulEggsNeededForNextRankFormattedAttribute(): string
    {
        return resolve(Egg::class)->format($this->getSoulEggsNeededForNextRankAttribute(), 3);
    }

    public function getSoulEggsNeededForNextRankAttribute(): float
    {
        if (!$this->getPlayerEggRankInfo()) {
            return -1;
        }

        $nextLevelMagnitude = $this->getPlayerEggRankInfo()->magnitude + 1;
        return ceil(pow(10, $nextLevelMagnitude) / $this->getEachSoulEggBonus());
    }

    public function scopeWithEggIncId($query)
    {
        return $query->where(function ($query) {
            return $query->where('egg_inc_player_id', '!=', '')->orWhereNotNull('egg_inc_player_id');
        });
    }

    public function scopeInShowRoles($query)
    {
        return $query
            ->whereHas('roles', function($query) {
                $query->where('show_members_on_roster', 1);
            })
        ;
    }

    public function scopeDiscordId($query, $discordId)
    {
        return $query
            ->where('discord_id', $discordId)
        ;
    }
}
