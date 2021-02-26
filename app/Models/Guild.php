<?php

namespace App\Models;

use Cache;

class Guild extends Model
{
    protected $with = ['members', 'roles'];

    protected $appends = ['is_bot_member_of'];

    private function getBotGuilds(): array
    {
        return Cache::remember('discord-bot-guilds', 60 * 5, function () {
            return $this->getDiscordClient()->user->getCurrentUserGuilds();
        });
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

        $this->syncRoles();
        $this->syncMembers();
        $this->refresh();
    }

    public function syncRoles()
    {
        $roles = $this->getDiscordClient()->guild->getGuildRoles(['guild.id' => (int) $this->discord_id]);

        $currentRoles = $this->roles;
        $currentRolesIds = [];
        foreach ($roles as $role) {
            $currentRole = $currentRoles->firstWhere('discord_id', $role->id);
            if (!$currentRole) {
                $currentRole = new Role;
                $currentRole->guild_id = $this->id;
                $currentRole->discord_id = $role->id;
            }
            $currentRole->name = $role->name;
            $currentRole->save();
            $currentRolesIds[] = $currentRole->id;
        }

        $currentRoles->whereNotIn('id', $currentRolesIds)->each(function($record) {
            $record->delete();
        });
    }

    public function syncMembers()
    {
        $this->load('roles');
        $members = $this->getGuildMembers();
        $users = collect();

        foreach ($members as $member) {
            if ($member->user->bot) {
                continue;
            }

            $user = User::unguarded(function () use ($member) {
                return User::updateOrCreate(
                    ['discord_id' => $member->user->id],
                    ['username' => $member->user->username]
                );
            });
            $user->roles()->sync($this->roles->whereIn('discord_id', $member->roles));
            $users[] = $user;
        }
        $this->members()->sync($users->pluck('id'));
    }

    // need to setup this to run when new server is added and add webhook to monitor members
    public function getGuildMembers(): array
    {
        return $this->getDiscordClient()->guild->listGuildMembers(['guild.id' => (int) $this->discord_id, 'limit' => 100]);
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
            return self::updateOrCreate(['discord_id' => $guild->id], ['name' => $guild->name]);
        });
    }

    public static function findByDiscordGuildId(int $guildId): Guild
    {
        return self::unguarded(function () use ($guildId) {
            $guild = self::firstOrNew(['discord_id' => $guildId]);
            $guildInfo = $guild->getDiscordClient()->guild->getGuild(['guild.id' => $guildId]);
            $guild->name = $guildInfo->name;
            $guild->save();
            return $guild;
        });
    }
}
