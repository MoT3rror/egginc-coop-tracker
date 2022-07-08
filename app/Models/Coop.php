<?php

namespace App\Models;

use App\Api\EggInc;
use App\Formatters\Egg;
use App\Formatters\TimeLeft;
use Exception;
use GuzzleHttp\Command\Exception\CommandClientException;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

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

    public function isComplete(): bool
    {
        return $this->getEggsLeftNeeded() < 0;
    }

    public function getEstimateCompletion(): string
    {
        if ($this->isComplete()) {
            return 'CPLT';
        }

        if ($this->getTotalRate() <= 0) {
            return 'NA';
        }

        $seconds = ceil($this->getEggsLeftNeeded() / $this->getTotalRate());

        if ($seconds > 60 * 60 * 24 * 30) {
            return 'months';
        }

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

    public function getDeflectorTotal(): int
    {
        $deflector = 0;
        foreach ($this->getCoopInfo()->members as $member) {
            $buffs = object_get($member, 'buffHistory');
            if ($buffs && object_get($buffs[count($buffs) - 1], 'eggLayingRate')) {
                $deflector += (object_get($buffs[count($buffs) - 1], 'eggLayingRate') - 1) * 100;
            }
        }
        return $deflector;
    }

    public function getMembers(): int
    {
        return count($this->getCoopInfo()->members);
    }

    public function getCreator(): string
    {
        return str_replace(
            '_',
            '', 
            object_get(collect($this->getCoopInfo()->members)
                ->where('id', $this->getCoopInfo()->creatorId)
                ->first(), 'name', '')
            )
        ;
    }

    public function getContractSize(): int
    {
        if (!$this->getContractInfo()) {
            return 0;
        }
        return $this->getContractInfo()->maxCoopSize;
    }

    public function getChannelPermissions(): array
    {
        // view and send
        $allow = $this->guild()->group_permissions ?? 3072;
        // view
        $deny = 1024;

        $permissions = [
            [
                'id'    => config('services.discord.client_id'),
                'allow' => 3088,
                'type'  => 1,
            ]
        ];

        if ($this->guild()->role_to_add_to_coop) {
            foreach ($this->guild()->role_to_add_to_coop as $role) {
                if (!trim($role)) {
                    continue;
                }
                $permissions[] = [
                    'id'    => $role,
                    'allow' => $allow,
                ];
            }
        }

        if ($this->guild()->roles->where('name', '@everyone')->first()) {
            $permissions[] = [
                'id'   => $this->guild()->roles->where('name', '@everyone')->first()->discord_id,
                'deny' => $deny,
            ];
        }

        foreach ($this->members as $member) {
            $permissions[] = [
                'id'    => $member->user->discord_id,
                'allow' => $allow,
                'type'  => 1,
            ];
        }
        return $permissions;
    }

    public function getInitialMessage(): array
    {
        $messages = [];
        $message = [$this->contractModel()->name . ' - ' . $this->coop, 'Members: ' . count($this->members) . '/' . $this->contractModel()->getMaxCoopSize()];

        foreach ($this->members->chunk(30) as $chunk) {
            foreach ($chunk as $member) {
                $roles = $member->user->roles->where('guild_id', $this->guild()->id)->where('show_role', true)->pluck('name')->join(', ');
                $message[] = '<@' . $member->user->discord_id . '> - ' . $member->user->getPlayerEggRank() . ($roles ? ' - ' . $roles : '') . ' - ' . $member->user->getHighestDeflectorAttribute();
            }
            $messages[] = implode(PHP_EOL, $message);
            $message = [];
        }

        return $messages;
    }

    public function makeChannel()
    {
        $this->load('members');
        if ($this->channel_id) {
            try {
                $channelInfo = [
                    'channel.id'            => (int) $this->channel_id,
                    'name'                  => $this->coop,
                    'permission_overwrites' => $this->getChannelPermissions(),
                ];
                $this->getDiscordClient()->channel->modifyChannel($channelInfo);
            } catch (ClientException $e) {
                Log::info(
                    'failed to update channel 1',
                    ['request' => (string) $e->getRequest()->getBody(), 'response' => (string) $e->getResponse()->getBody()]
                );
                throw $e;
            } catch (CommandException $e) {
                Log::info(
                    'failed to update channel 2',
                    [
                        'channelInfo' => $channelInfo,
                        'request' => var_export($e->getRequest(), true),
                        'response' => (string) $e->getResponse()->getBody()
                    ]
                );
                throw $e;
            }
            return;
        }

        try {
            $result = $this->getDiscordClient()->guild->createGuildChannel([
                'guild.id'              => (int) $this->guild_id,
                'name'                  => $this->coop,
                'permission_overwrites' => $this->getChannelPermissions(),
                'parent_id'             => (int) $this->guild()->coop_channel_parent,
                'position'              => $this->position,
            ]);
        } catch (ClientException $e) {
            Log::info('failed to create channel', ['request' => $e->getRequest(), 'response' => (string) $e->getResponse()->getBody()]);
            throw $e;
        }

        $this->channel_id = $result['id'];
        $this->save();

        $this->sendInitialMessage();
    }

    public function sendInitialMessage()
    {
        $lastMessage = null;
        foreach ($this->getInitialMessage() as $message) {
            $lastMessage = $this->sendMessageToChannel($message);
        }

        if ($lastMessage && $this->guild()->create_thread_on_new_channel) {
            $this->discordHttpClient()
                ->post('/channels/' . $this->channel_id . '/messages/' . $lastMessage['id'] . '/threads', ['name' => 'Bot Commands'])
            ;
        }
    }

    public function sendMessageToChannel($message)
    {
        return $this->getDiscordClient()->channel->createMessage([
            'channel.id' => (int) $this->channel_id,
            'content'    => $message,
        ]);
    }

    public function deleteChannel()
    {
        if ($this->channel_id) {
            try {
                $this->getDiscordClient()->channel->deleteOrcloseChannel([
                    'channel.id' => (int) $this->channel_id,
                ]);
            } catch (CommandClientException $exception) {
                
            }
            $this->channel_id = null;
            $this->save();
        }
    }

    public function addMember(User $user)
    {
        $coopMember = new CoopMember;
        $coopMember->user_id = $user->id;
        $this->members()->save($coopMember);
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
