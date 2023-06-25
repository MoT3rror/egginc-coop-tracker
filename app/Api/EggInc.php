<?php
namespace App\Api;

use App\Exceptions\CoopNotFoundException;
use App\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;

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
            // Log::channel('coop')->info(json_encode($output));

            if (!isset($output->contributors)) {
                throw new CoopNotFoundException;
            }

            return $output;
        });
    }

    public function getCurrentVersion(): \StdClass
    {
        $response = $this->getHttpClient()->get('current-version');
        $json = json_decode($response->body());
        return $json;
    }

    public function getCurrentContracts(?string $clientVersion = null, ?string $appVersion = null): \StdClass
    {
        $response = $this->getHttpClient()
            ->get(
                'Periodicals',
                [
                    'clientVersion' => $clientVersion,
                    'appVersion'    => $appVersion,
                ],
            )
        ;
        $json = json_decode($response->body());
        return $json;
    }

    public function getPlayerInfo(string $playerId): \StdClass
    {
        if (!$playerId || substr(strtolower($playerId), 0, 2) != 'ei') {
            throw new UserNotFoundException('User not found');
        }

        return Cache::remember('egg-player-info-' . $playerId, 60 * 60 * 4, function () use ($playerId) {
            $response = $this->getHttpClient()->get('getPlayerInfo', ['playerId' => strtoupper($playerId)]);
            $json = $response->body();
            try {
                $player = json_decode($json, null, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $player = null;
            }    

            if (!$player || !isset($player->approxTime) || !$player->approxTime) {
                throw new UserNotFoundException('User not found');
            }

            $player->received_at = time();

            return $player;
        });
    }
}
