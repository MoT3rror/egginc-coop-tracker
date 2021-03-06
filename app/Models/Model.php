<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as ModelBase;
use RestCord\DiscordClient;

class Model extends ModelBase
{
    use HasFactory;
    use Traits\Appendable;

    public function getDiscordClient(): DiscordClient
    {
        return app()->make('DiscordClientBot');
    }
}