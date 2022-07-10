<?php

namespace App\Models;

use App\Api\EggInc;
use App\Formatters\EarningBonus;
use App\Formatters\Egg;
use App\Exceptions\UserNotFoundException;
use Cache;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use RestCord\DiscordClient;
use StdClass;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use Traits\Appendable;

    protected $casts = [
        'discord_token_expires' => 'datetime',
        'keep_stats' => 'boolean',
    ];

    protected $appends = ['player_earning_bonus_formatted', 'player_egg_rank', 'drones', 'soul_eggs', 'eggs_of_prophecy', 'player_earning_bonus', 'soul_eggs_needed_for_next_rank', 'p_e_needed_for_next_rank', 'egg_inc_username', 'highest_deflector'];

    protected $with = ['roles'];

    protected $hidden = ['email', 'discord_id', 'discord_refresh_token', 'discord_token', 'discord_token_expires', 'egg_inc_player_id'];

    protected $discordGuildsCache = null;

    public function getCurrentDiscordToken()
    {
        if ($this->discord_token_expires->lt(now())) {
            $client = new Client;
            $response = $client->request('POST', 'https://discord.com/api/v9/oauth2/token', [
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
        if (!$this->discordGuildsCache) {
            $this->discordGuildsCache = Cache::remember('user-discord-guilds-' . $this->id, 60 * 5, function () {
                // just in case we keep calling it
                $discord = new DiscordClient([
                    'token'     => $this->getCurrentDiscordToken(),
                    'tokenType' => 'OAuth',
                ]);
                $guilds = $discord->user->getCurrentUserGuilds()->toArray();
                $guildModels = Guild::query()
                    ->whereIn('discord_id', Arr::pluck($guilds, 'id'))
                    ->get()
                ;
    
                foreach ($guilds as $key => $guild) {
                    $guilds[$key]['isAdmin'] = $guild['permissions'] & 8 === 8;
                    // weird bug with vue or something that causes this number to change
                    $guilds[$key]['id'] = (string) $guild['id'];
                    $guildModel = $guildModels->where('discord_id', $guild['id'])->first();
                    
                    if (!$guildModel) {
                        $guildModel = Guild::findByDiscordGuild($guild);
                    }
                    
                    if (!$guildModel->getIsBotMemberOfAttribute()) {
                        unset($guilds[$key]);
                        continue;
                    }
                    $guildModel->sync();
                    
                    if (!$guilds[$key]['isAdmin']) {
                        $guilds[$key]['isAdmin'] = $this->roles
                            ->where('is_admin', true)
                            ->where('guild_id', $guildModel->id)
                            ->count() >= 1
                        ;
                    }
                }

                
                return $guilds;
            });
        }
        return $this->discordGuildsCache;
    }

    public function guilds()
    {
        return $this->belongsToMany(Guild::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function userStats()
    {
        return $this->hasMany(UserStats::class);
    }

    public function coopsMembers()
    {
        return $this->hasMany(CoopMember::class);
    }

    public function getEggPlayerInfo(): ?StdClass
    {
        if (!$this->egg_inc_player_id) {
            return null;
        }
        try {
            return resolve(EggInc::class)->getPlayerInfo($this->egg_inc_player_id);
        } catch (UserNotFoundException $e) {
            return null;
        }
    }

    public function getBackupTime(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->approxTimestamp;
    }

    public function getBackupTimeFormatted(): string
    {
        $time = $this->getBackupTime();
        if ($time === 0) {
            return 'Never';
        }
        return Carbon::createFromTimestamp($time)->format('Y-m-d H:i:s e');
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

        return $info->progress->prophecyEggs;
    }

    public function getEachSoulEggBonus(): int
    {
        if (!$this->getEggPlayerInfo()) {
            return 0;
        }

        $prophecyBonus = $this->getProphecyBonus();
        $soulBonus = $this->getSoulEggsBonus();
        $eggsOfProphecy = $this->getEggsOfProphecyAttribute();

        return floor(((.1 + $soulBonus * .01) * (1.05 + $prophecyBonus * .01) ** $eggsOfProphecy) * 100);
    }

    public function getSoulEggsBonus(): int
    {
        return $this->getEpicResearchLevel('soul_eggs');
    }

    public function getProphecyBonus(): int
    {
        return $this->getEpicResearchLevel('prophecy_bonus');
    }

    public function getEpicResearchLevel(string $bonus): int
    {
        return object_get(collect($this->getEpicResearches())->where('id', $bonus)->first(), 'level');
    }

    public function getEpicResearches(): array
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return [];
        }

        return $info->progress->epicResearches;
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

        return $info->progress->soulEggs;
    }

    public function getSoulEggsFormattedAttribute(): string
    {
        return resolve(Egg::class)->format($this->getSoulEggsAttribute(), 3);
    }

    public function getSoulEggsPerPrestige(): float
    {
        return $this->getSoulEggsAttribute() / $this->getPrestigesAttribute();
    }

    public function getSoulEggsPerPrestigeFormatted(): string
    {
        return resolve(Egg::class)->format($this->getSoulEggsPerPrestige());
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

    public function getEliteDronesAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->stats->eliteDroneTakedowns;
    }

    public function getPrestigesAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->stats->prestiges;
    }

    public function getBoostsUsedAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->stats->boostsUsed;
    }

    public function getLifeTimeGoldenEggsAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->progress->lifefimeGoldenEggs;
    }

    public function getLiftTimeSpentGoldenEggsAttribute(): int
    {
        $info = $this->getEggPlayerInfo();
        if (!$info) {
            return 0;
        }
        return $info->progress->lifetimeGoldenEggsSpent;
    }

    public function getCurrentGoldenEggs(): int
    {
        return $this->getLifeTimeGoldenEggsAttribute() - $this->getLiftTimeSpentGoldenEggsAttribute();
    }

    public function getEggIncUsernameAttribute(): string
    {
        $info = $this->getEggPlayerInfo();
        if (!$info || !isset($info->userName)) {
            return '';
        }

        return $info->userName;
    }

    public function getPENeededForNextRankAttribute(): int
    {
        if (!$this->getEggPlayerInfo()) {
            return 0;
        }

        if (!$this->getPlayerEggRankInfo()) {
            return -1;
        }

        $nextLevelMagnitude = $this->getPlayerEggRankInfo()->magnitude + 1;
        $nextLevelEarningBonus = pow(10, $nextLevelMagnitude);
        $prophecyBonus = $this->getProphecyBonus();
        $soulBonus = $this->getSoulEggsBonus();
        $eggsOfProphecy = $this->getEggsOfProphecyAttribute();
        $soulEggs = $this->getSoulEggsAttribute();

        while ($eggsOfProphecy <= $this->getEggsOfProphecyAttribute() + 50) {
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

    public function getCompleteContractsAttribute(): array
    {
        $info = $this->getEggPlayerInfo(); 

        return object_get($info, 'contracts.completeContracts', []);
    }

    public function hasCompletedContract($contractId): bool
    {
        return in_array($contractId, $this->getCompleteContractsAttribute());
    }

    public function getHighestDeflectorWithoutPercentAttribute(): int
    {
        $info = $this->getEggPlayerInfo(); 

        if (!$info || !object_get($info, 'artifactsDb.inventoryItems')) {
            return 0;
        }
        $inventoryitems = collect($info->artifactsDb->inventoryItems)
            ->where('artifact.spec.name', 'TACHYON_DEFLECTOR')
        ;

        $highest = 0;
        $value = 0;
        foreach ($inventoryitems as $inventoryitem) {
            switch ($inventoryitem->artifact->spec->level) {
                case 'INFERIOR':
                    $value = 5;
                    break;
                case 'LESSER':
                    $value = 8;
                    break;
                case 'NORMAL':
                    switch ($inventoryitem->artifact->spec->rarity) {
                        case 'COMMON':
                            $value = 12;
                            break;
                        case 'RARE':
                            $value = 13;
                            break;
                    }

                    // rarity: COMMON, RARE, EPIC
                    break;
                case 'GREATER':
                    switch ($inventoryitem->artifact->spec->rarity) {
                        case 'COMMON':
                            $value = 15;
                            break;
                        case 'RARE':
                            $value = 17;
                            break;
                        case 'EPIC':
                            $value = 19;
                            break;
                        case 'LEGENDARY':
                            $value = 20;
                            break;
                    }
                    break;

            }
            if ($value > $highest) {
                $highest = $value;
            }
        }
        return $highest;
    }

    public function getLegendaryArtifactsCount(): int
    {
        $info = $this->getEggPlayerInfo();

        if (!$info || !object_get($info, 'artifactsDb.inventoryItems')) {
            return 0;
        }

        return collect($info->artifactsDb->inventoryItems)
            ->where('artifact.spec.rarity', 'LEGENDARY')
            ->count()
        ;
    }

    public function getHighestDeflectorAttribute(): string
    {
        return $this->getHighestDeflectorWithoutPercentAttribute() . '%';
    }

    public function getUsernameWithRolesAttribute()
    {
        $roles = $this
            ->roles
            ->where('show_role')
            ->pluck('name')
            ->join(', ')
        ;

        return $this->username . ($roles ? ' (' . $roles . ')' : '');
    }

    public function createUserStat()
    {
        $statData = [
            'soul_eggs'         => $this->getSoulEggsAttribute(),
            'prestige_eggs'     => $this->getEggsOfProphecyAttribute(),
            'golden_eggs'       => $this->getCurrentGoldenEggs(),
            'prestiges'         => $this->getPrestigesAttribute(),
            'drones'            => $this->getDronesAttribute(),
            'elite_drones'      => $this->getEliteDronesAttribute(),
            'total_golden_eggs' => $this->getLifeTimeGoldenEggsAttribute(),
            'record_time'       => Carbon::now(),
            'prophecy_bonus'    => $this->getProphecyBonus(),
            'soul_eggs_bonus'   => $this->getSoulEggsBonus(),
        ];

        $this->userStats()->create($statData);
    }

    public function scopeWithEggIncId($query)
    {
        return $query->where(function ($query) {
            return $query->where('egg_inc_player_id', '!=', '')->orWhereNotNull('egg_inc_player_id');
        });
    }

    public function scopeInShowRoles($query, $guildId)
    {
        if (is_object($guildId)) {
            $guildId = $guildId->id;
        }
        return $query
            ->whereHas('roles', function($query) use ($guildId) {
                $query
                    ->where('show_members_on_roster', 1)
                    ->where('guild_id', $guildId)
                ;
            })
        ;
    }

    public function scopePartOfTeam($query, $guildId)
    {
        if (is_object($guildId)) {
            $guildId = $guildId->id;
        }
        return $query
            ->whereHas('roles', function($query) use ($guildId) {
                $query
                    ->where('part_of_team', 1)
                    ->where('guild_id', $guildId)
                ;
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
