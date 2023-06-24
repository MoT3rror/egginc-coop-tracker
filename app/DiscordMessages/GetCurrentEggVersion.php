<?php
namespace App\DiscordMessages;

use App\Api\EggInc;
use Illuminate\Support\Facades\Artisan;

class GetCurrentEggVersion extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): string
    {        
        $versions = resolve(EggInc::class)->getCurrentVersion();

        return <<<VERSIONS
```
Client Version: $versions->currentClientVersion
App Version: $versions->appVersion
```
VERSIONS;
    }

    public function help(): string
    {
        return '- Get current egg inc version used by bot.';
    }
}
