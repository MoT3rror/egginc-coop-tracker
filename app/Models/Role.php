<?php

namespace App\Models;

class Role extends Model
{
    protected $casts = [
        'show_members_on_roster' => 'boolean',
        'show_role'              => 'boolean',
        'is_admin'               => 'boolean',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class);
    }
}
