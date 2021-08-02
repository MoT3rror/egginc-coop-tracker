<?php
namespace App\DiscordMessages;

use App\Exceptions\CoopNotFoundException;
use App\SimilarText;
use Illuminate\Database\Eloquent\Collection;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Unfilled extends Status
{
    public function coopData(Collection $coops, bool $hideSimilarText = false): array
    {
        $similarText = new SimilarText;
        $similarPart = $similarText->similar($coops->pluck('coop')->all());

        $data = [];
        foreach ($coops as $coop) {
            $this->totalTeams++;
            $coopName = $hideSimilarText ? str_replace($similarPart, '', $coop->coop) : $coop->coop;
            try {
                $isOnTrack = $coop->getIsOnTrackAttribute();
                if ($isOnTrack) {
                    $this->teamsOnTrack++;
                }
                
                if ($coop->contractModel()->getMaxCoopSize() <= $coop->getMembers() || $coop->isComplete()) {
                    continue;
                }

                $data[] = [
                    'name'      => $coopName . ' ' . $coop->getMembers() . '',
                    'progress'  => $coop->getCurrentEggsFormatted(),
                    'time-left' => $coop->getEstimateCompletion(),
                    'projected' => $coop->getProjectedEggsFormatted() . ($isOnTrack ? ' X' : ''),
                ];
            } catch (CoopNotFoundException $e) {
                $data[] = [
                    'name'     => $coop->coop,
                    'progress' => 'NA',
                ];
            }
        }
        return $data;
    }

    public function help(): string
    {
        return '{Contract ID} - Show coops that are not filled.';
    }

    public function description(): string
    {
        return 'Coops not filled.';
    }
}
