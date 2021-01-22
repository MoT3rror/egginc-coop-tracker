<?php
namespace App\Api;

use App\Exceptions\CoopNotFoundException;
use App\Exceptions\UserNotFoundException;
use Cache;
use mikehaertl\shellcommand\Command;

class EggInc
{
    public function getCoopInfo(string $contract, string $coop): \StdClass
    {
        $cacheKey = $contract . '-' . $coop;

        return Cache::remember($cacheKey, 60 * 5, function () use ($contract, $coop) {
            $appInfoCommand = new Command([
                'command' => 'node ./js/egg-inc.js getCoopStatus --contract ' . $contract . ' --coop ' . $coop,
                'procCwd' => base_path(),
            ]);

            if (!$appInfoCommand->execute()) {
                throw new Exception('Unable to get coop data');
            }
            $output = json_decode($appInfoCommand->getOutput());

            if (!$output) {
                throw new CoopNotFoundException;
            }
            return $output;
        });
    }

    public function getCurrentContracts(): array
    {
        $contractCommand = new Command([
            'command' => 'node ./js/egg-inc.js getAllActiveContracts',
            'procCwd' => base_path(),
        ]);

        $contracts = [];
        if ($contractCommand->execute()) {
            $contracts = json_decode($contractCommand->getOutput());
        }

        if (!$contracts) {
            throw new \Exception('Could not load contracts');
        }
        return $contracts;
    }

    public function getPlayerInfo(string $playerId): \StdClass
    {
        return Cache::remember('egg-player-info-' . $playerId, 60 * 60 * 1, function () use ($playerId) {
            $appInfoCommand = new Command([
                'command' => 'node ./js/egg-inc.js getPlayerInfo --playerId ' . $playerId,
                'procCwd' => base_path(),
            ]);

            if (!$appInfoCommand->execute()) {
                throw new Exception('Unable to get player info');
            }

            $player = json_decode($appInfoCommand->getOutput());

            if (!$player->approxTimestamp) {
                throw new UserNotFoundException('User not found');
            }

            $player->received_at = time();

            return $player;
        });
    }
}
