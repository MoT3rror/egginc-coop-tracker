<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as ModelBase;
use RestCord\DiscordClient;

class Model extends ModelBase
{
    public function getDiscordClient(): DiscordClient
    {
        return app()->make('DiscordClientBot');
    }
}