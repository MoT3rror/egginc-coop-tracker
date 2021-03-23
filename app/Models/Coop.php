<?php

namespace App\Models;

use App\Api\EggInc;
use App\Formatters\Egg;
use App\Formatters\TimeLeft;

class Coop extends Model
{
    protected $fillable = ['contract', 'coop', 'position'];

    protected $with = ['members'];

    protected static function booted()
    {
        static::creating(function ($coop) {
            $lastCoop = self::query()
                ->guild($coop->guild_id)
                ->contract($coop->contract)
                ->orderBy('position', 'desc')
                ->first()
            ;

            $position = object_get($lastCoop, 'position', 0) + 1;
            $coop->position = $position;
        });
    }

    public function scopeGuild($query, $guildId)
    {
        return $query->where('guild_id', $guildId);
    }

    public function scopeContract($query, $contract)
    {
        return $query->where('contract', $contract);
    }

    public function scopeCoop($query, $coop)
    {
        return $query->where('coop', $coop);
    }

    public function scopeChannelId($query, $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function getCoopInfo(): \StdClass
    {
        return resolve(EggInc::class)->getCoopInfo($this->contract, $this->coop);
    }

    public function getCurrentEggs(): float
    {
        return $this->getCoopInfo()->eggsLaid;
    }

    public function getEggsNeeded(): int
    {
        return $this->contractModel()->getEggsNeeded();
    }

    public function getProjectedEggs(): float
    {
        if ($this->getTimeLeft() < 0) { // if no time left to make more eggs, return what is available
            return $this->getCurrentEggs();
        }
        return $this->getCurrentEggs() + ($this->getTotalRate() * $this->getTimeLeft()); // make a projection
    }

    public function getIsOnTrackAttribute(): bool
    {
        return $this->getTotalRate() > $this->getNeededRate();
    }

    public function getProjectedEggsFormatted(): string
    {
        return resolve(Egg::class)->format($this->getProjectedEggs());
    }

    public function getCurrentEggsFormatted(): string
    {
        return resolve(Egg::class)->format($this->getCurrentEggs());
    }

    public function getEggsNeededFormatted(): string
    {
        return resolve(Egg::class)->format($this->getEggsNeeded());
    }

    public function getTotalRateFormatted(): string
    {
        return resolve(Egg::class)->format($this->getTotalRate() * 60 * 60, 1);
    }

    public function getNeededRateFormatted(): string
    {
        return resolve(Egg::class)->format($this->getNeededRate() * 60 * 60, 1);
    }

    public function getContractInfo(): ?\StdClass
    {
        return Contract::firstWhere('identifier', $this->contract)->raw_data;
    }

    public function getEggsLeftNeeded(): int
    {
        return $this->getEggsNeeded() - $this->getCurrentEggs();
    }

    public function getEstimateCompletion(): string
    {
        if ($this->getEggsLeftNeeded() < 0) {
            return 'CPLT';
        }
        $seconds = ceil($this->getEggsLeftNeeded() / $this->getTotalRate());

        return resolve(TimeLeft::class)
            ->format($seconds)
        ;
    }

    public function getTotalRate(): int
    {
        $rate = 0;

        foreach ($this->getCoopInfo()->members as $member) {
            $rate += $member->eggsPerSecond;
        }

        return $rate;
    }

    public function getNeededRate(): int
    {
        if ($this->getEggsLeftNeeded() <= 0 || $this->getTimeLeft() <= 0) {
            return 0;
        }

        return $this->getEggsLeftNeeded() / $this->getTimeLeft();
    }

    public function getTimeLeft(): int
    {
        return $this->getCoopInfo()->secondsUntilProductionDeadline;
    }

    public function getTimeLeftFormatted(): string
    {
        if ($this->getTimeLeft() <= 0) {
            return 'Past Due';
        }

        return resolve(TimeLeft::class)
            ->format($this->getTimeLeft())
        ;
    }

    public function getMembers(): int
    {
        return count($this->getCoopInfo()->members);
    }

    public function getContractSize(): int
    {
        if (!$this->getContractInfo()) {
            return 0;
        }
        return $this->getContractInfo()->maxCoopSize;
    }

    public function makeChannel()
    {
        if ($this->channel_id) {
            return;
        }

        $permissions = [
            [
                'id'    => $this->guild()->role_to_add_to_coop,
                // view and send
                'allow' => 3072,
            ],
            [
                'id'   => $this->guild()->roles->where('name', '@everyone')->first()->discord_id,
                // view
                'deny' => 1024,
            ],
            [
                'id'    => config('services.discord.client_id'),
                'allow' => 3072,
                'type'  => 1,
            ]
        ];
        $message = [$this->contractModel()->name . ' - ' . $this->coop];

        foreach ($this->members as $member) {
            $permissions[] = [
                'id'    => $member->user->discord_id,
                'allow' => 3072,
                'type'  => 1,
            ];
            $message[] = '<@' . $member->user->discord_id . '> - ' . $member->user->getPlayerEggRank() . ' - ' . $member->user->roles->where('guild_id', $this->guild()->id)->pluck('name')->join(', ');
        }

        $result = $this->getDiscordClient()->guild->createGuildChannel([
            'guild.id'              => $this->guild_id,
            'name'                  => $this->coop,
            'permission_overwrites' => $permissions,
            'parent_id'             => (int) $this->guild()->coop_channel_parent,
            'position'              => $this->position, 
        ]);

        $this->channel_id = $result->id;
        $this->save();

        $this->getDiscordClient()->channel->createMessage([
            'channel.id' => $this->channel_id,
            'content'    => implode(PHP_EOL, $message),
        ]);
    }

    public function contractModel(): Contract
    {
        return $this->belongsTo(Contract::class, 'contract', 'identifier')->first();
    }

    public function guild(): Guild
    {
        return $this->belongsTo(Guild::class, 'guild_id', 'discord_id')->first();
    }

    public function members()
    {
        return $this->hasMany(CoopMember::class);
    }
}
