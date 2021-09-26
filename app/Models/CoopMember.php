<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoopMember extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function scopeUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeContractGuild($query, string $contract, int $guildId)
    {
        return $query
            ->select('coop_members.*')
            ->join('coops', 'coops.id', '=', 'coop_members.coop_id')
            ->where('coops.contract', $contract)
            ->where('coops.guild_id', $guildId)
        ;
    }
}
