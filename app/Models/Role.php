<?php

namespace App\Models;

class Role extends Model
{
    protected $casts = [
        'show_members_on_roster' => 'boolean',
        'show_role'              => 'boolean',
        'is_admin'               => 'boolean',
        'part_of_team'           => 'boolean',
        'discord_id'             => 'string',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeDiscordId($query, int $discordId)
    {
        return $query->where('discord_id', $discordId);
    }

    public function scopeGuildId($query, int $guildId)
    {
        return $query->where('guild_id', $guildId);
    }
}
