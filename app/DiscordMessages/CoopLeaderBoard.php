<?php
namespace App\DiscordMessages;

use App\Formatters\Egg;
use App\Exceptions\CoopNotFoundException;
use App\Exceptions\DiscordErrorException;
use App\Models\Coop;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class CoopLeaderBoard extends Status
{    
    public function memberData(Collection $coops, bool $hideSimilarText = false): array
    {
        $data = [];
        foreach ($coops as $coop) {
            foreach ($coop->getCoopInfo()->members as $member) {
                $data[] = [
                    'name' => $member->name,
                    'rate' => round($member->eggsPerSecond),
                ];
            }
        }
        return collect($data)->sortBy([
            ['rate', 'desc'],
            ['name', 'desc'],
        ])->map(function ($member) {
            $member['rate'] = resolve(Egg::class)->format($member['rate']);
            return $member;
        })->all();
    }

    public function getStarterMessage(): array
    {
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        $messages = [$contract->name . '(' . $contract->identifier . ')'];

        return $messages;
    }

    public function getTable(Table $table, array $data): array
    {
        $groupOfMessages = [implode("\n", $this->getStarterMessage()) . "\n"];

        foreach (collect($data)->chunk(35) as $index => $chunk) {
            $messages = ['```'];
            foreach ($table->generate($chunk->all()) as $row) {
                $messages[] = $row;
            }
            $messages[] = '```';

            if (!isset($groupOfMessages[$index])) {
                $groupOfMessages[$index] = '';
            }

            $groupOfMessages[$index] .= implode("\n", $messages);
        }

        return $groupOfMessages;
    }

    public function message(): array
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return $coops;
        }
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('Name', Column::ALIGN_LEFT));
        $table->addColumn('rate', new Column('Rate', Column::ALIGN_RIGHT));

        $coopsData = $this->memberData($coops, false);
        return $this->getTable($table, $coopsData);
    }

    public function help(): string
    {
        return 'eb!coop-leaderboard {Contract ID} - Display all members of coops order by rate';
    }
}
