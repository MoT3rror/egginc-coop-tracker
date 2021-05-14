<?php
namespace App\Api;

use App\Exceptions\CoopNotFoundException;
use App\Exceptions\UserNotFoundException;
use Cache;
use Exception;
use Illuminate\Support\Facades\Http;
use Log;
use mikehaertl\shellcommand\Command;

class EggInc
{
    public function getHttpClient()
    {
        return Http::baseUrl(config('services.egg_inc_api.url'));
    }

    public function getCoopInfo(string $contract, string $coop): \StdClass
    {
        $cacheKey = $contract . '-' . $coop;

        return Cache::remember($cacheKey, 60 * 5, function () use ($contract, $coop) {
            $response = $this->getHttpClient()->get('getCoopStatus', ['contract' => $contract, 'coop' => $coop]);
            $output = json_decode($response->body());
            if (!$output) {
                throw new CoopNotFoundException;
            }
            Log::channel('coop')->info(json_encode($output));

            if (!isset($output->members)) {
                throw new CoopNotFoundException;
            }

            return $output;
        });
    }

    public function getCurrentContracts(): array
    {
        $response = $this->getHttpClient()->get('Periodicals');
        $json = json_decode($response->body());
        return $json->contracts->contracts;
    }

    public function getPlayerInfo(string $playerId): \StdClass
    {
        if (!$playerId || substr($playerId, 0, 2) != 'ei') {
            throw new UserNotFoundException('User not found');
        }

        return Cache::remember('egg-player-info-' . $playerId, 60 * 60 * 4, function () use ($playerId) {
            $response = $this->getHttpClient()->get('getPlayerInfo', ['playerId' => strtoupper($playerId)]);
            $player = json_decode($response->body());

            if (!$player || !isset($player->approxTimestamp) || !$player->approxTimestamp) {
                throw new UserNotFoundException('User not found');
            }

            $player->received_at = time();

            return $player;
        });
    }
}
