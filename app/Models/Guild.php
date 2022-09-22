<?php

namespace App\Models;

use App\Jobs\CleanUpMembers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class Guild extends Model
{
    use Traits\Appendable;

    protected $with = ['members', 'roles'];

    protected $appends = ['is_bot_member_of'];

    protected $casts = [
        'last_sync'                    => 'datetime:Y-m-d',
        'coop_channel_parent'          => 'string',
        'role_to_add_to_coop'          => 'array',
        'show_link_on_status'          => 'integer',
        'create_thread_on_new_channel' => 'boolean',
        'admin_users'                  => 'json',
    ];

    protected $attributes = [
        'tracker_sort_by'     => 'earning_bonus',
        'show_link_on_status' => true,
        'admin_users'         => '[]',
    ];

    private function getBotGuilds(): array
    {
        return app()->make('DiscordBotGuilds')->toArray();
    }

    public function getIsBotMemberOfAttribute(): bool
    {
        return (bool) collect($this->getBotGuilds())
            ->where('id', $this->discord_id)
            ->first()
        ;
    }

    public function sync()
    {
        if (!$this->getIsBotMemberOfAttribute()) {
            return;
        }

        if ($this->last_sync && $this->last_sync->greaterThan(now()->subHours(3))) {
            return;
        }

        $this->syncRoles();
        $this->syncMembers();
        $this->last_sync = now();
        $this->save();
        $this->refresh();
        
        CleanUpMembers::dispatch($this);
    }

    public function syncRoles()
    {
        $roles = $this->getDiscordClient()->guild->getGuildRoles(['guild.id' => (int) $this->discord_id]);

        $currentRoles = $this->roles;
        $currentRolesIds = [];
        foreach ($roles as $role) {
            $currentRole = $currentRoles->firstWhere('discord_id', $role['id']);
            if (!$currentRole) {
                $currentRole = new Role;
                $currentRole->guild_id = $this->id;
                $currentRole->discord_id = $role['id'];
            }
            if ($currentRole->name !== $role['name']) {
                $currentRole->name = $role['name'];
                $currentRole->save();
            }
            $currentRolesIds[] = $currentRole->id;
        }

        $currentRoles->whereNotIn('id', $currentRolesIds)->each(function($record) {
            $record->delete();
        });
        $this->unsetRelation('roles');
    }

    public function syncMembers()
    {
        $members = $this->getGuildMembers();
        $users = [];

        foreach ($members as $member) {
            if (Arr::get('user.bot', $member)) {
                continue;
            }

            $user = User::unguarded(function () use ($member) {
                return User::firstOrNew(
                    ['discord_id' => $member['user']['id']],
                );
            });

            if ($user->username !== $member['user']['username']) {
                $user->username = $member['user']['username'];
                $user->save();
            }

            $currentGuildRoles = $user->roles->where('guild_id', $this->id);

            foreach ($member['roles'] as $role) {
                if (!$currentGuildRoles->where('discord_id', $role)->first()) {
                    $user->roles()->attach($this->roles->where('discord_id', $role)->first());
                    continue;
                }
                $currentGuildRoles = $currentGuildRoles->except($currentGuildRoles->where('discord_id', $role)->first()->id);
            }
            
            foreach ($currentGuildRoles as $currentGuildRole) {
                $user->roles()->detach($currentGuildRole);
            }

            $users[] = $user->id;
            unset($user);
        }
        $this->members()->sync($users);
    }

    public function cleanUpOldMembers()
    {
        foreach ($this->roles as $role) {
            foreach ($role->members as $member) {
                if (!in_array($role->guild_id, $member->guilds->pluck('id')->all())) {
                    $role->members()->detach($member);
                }
            }
        }
    }

    public function getChannelCategories(): array
    {
        $channels = $this->getDiscordClient()->guild->getGuildChannels(['guild.id' => (int) $this->discord_id])->toArray();
        return collect($channels)
            ->where('type', 4)
            ->map(function ($channel) {
                $channel = (array) $channel;
                $channel['id'] = (string) $channel['id'];
                return $channel;
            })
            ->all()
        ;
    }

    // need to setup this to run when new server is added and add webhook to monitor members
    public function getGuildMembers(): array
    {
        return $this->getDiscordClient()->guild->listGuildMembers(['guild.id' => (int) $this->discord_id, 'limit' => 100])->toArray();
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public static function findByDiscordGuild($guild): Guild
    {
        return self::unguarded(function () use ($guild) {
            return self::updateOrCreate(['discord_id' => $guild['id']], ['name' => $guild['name']]);
        });
    }

    public static function findByDiscordGuildId(int $guildId): Guild
    {
        return self::unguarded(function () use ($guildId) {
            $guild = self::firstOrNew(['discord_id' => $guildId]);

            if ($guild->last_sync && $guild->last_sync->greaterThan(now()->subHours(3))) {
                return $guild;
            }

            $guildInfo = $guild->getDiscordClient()->guild->getGuild(['guild.id' => $guildId]);
            $guild->name = $guildInfo['name'];
            $guild->save();
            return $guild;
        });
    }

    public function getMembersAvailableForContract(string $contractId): Collection
    {
        return $this
            ->members()
            ->withEggIncId()
            ->inShowRoles($this)
            ->get()
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
            ->filter(function ($user) use ($contractId) {
                return !$user->hasCompletedContract($contractId);
            })
        ;
    }
}
