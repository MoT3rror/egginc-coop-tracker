<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStats extends Model
{
    use HasFactory;

    protected $casts = [
        'prestige_eggs'     => 'integer',
        'golden_eggs'       => 'integer',
        'total_golden_eggs' => 'integer',
        'prestiges'         => 'integer',
        'drones'            => 'integer',
        'elite_drones'      => 'integer',
        'record_time'       => 'datetime',
    ];

    protected $fillable = ['prestige_eggs', 'soul_eggs', 'golden_eggs', 'prestiges', 'drones', 'elite_drones', 'record_time', 'total_golden_eggs', 'prophecy_bonus', 'soul_eggs_bonus',];

    protected $appends = ['soul_eggs_float', 'earning_bonus'];


    public function getSoulEggsFloatAttribute(): float
    {
        return (float) $this->soul_eggs;
    }

    public function getEarningBonusAttribute(): float
    {
        return floor(((.1 + $this->soul_eggs_bonus * .01) * (1.05 + $this->prophecy_bonus * .01) ** $this->prestige_eggs) * 100) * $this->getSoulEggsFloatAttribute();
    }
}
