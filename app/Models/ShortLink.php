<?php
namespace App\Models;
   
class ShortLink extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'link', 'expire'];

    protected $casts = [
        'expire' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($link) {
            $link->code = base64_encode($link->id . '10' . rand(0, 9));
            $link->save();
        });
    }
}
