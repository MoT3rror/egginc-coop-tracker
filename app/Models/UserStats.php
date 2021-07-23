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

    protected $fillable = ['prestige_eggs', 'soul_eggs', 'golden_eggs', 'prestiges', 'drones', 'elite_drones', 'record_time', 'total_golden_eggs'];
}
