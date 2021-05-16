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
}
