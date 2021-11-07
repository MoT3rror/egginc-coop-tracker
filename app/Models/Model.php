<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Support\Facades\Http;
use RestCord\DiscordClient;

class Model extends ModelBase
{
    use HasFactory;
    use Traits\Appendable;

    public function getDiscordClient(): DiscordClient
    {
        return app()->make('DiscordClientBot');
    }

    public function discordHttpClient()
    {
        return $currentlySet = Http::withHeaders([
            'Authorization' => 'Bot ' . config('services.discord.token'),
        ])
            ->withOptions([
                'http_errors' => true,
            ])
            ->baseUrl('https://discord.com/api/v9/')
        ;
    }
}