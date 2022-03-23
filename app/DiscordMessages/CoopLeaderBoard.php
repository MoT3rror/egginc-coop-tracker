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
    protected $sort = 'rate';

    protected $sortByOptions = ['rate', 'eggs_laid'];

    public $guildOnly = true;

    public function memberData(Collection $coops, bool $hideSimilarText = false): array
    {
        $membersIds = $this->guild->members->pluck('egg_inc_player_id')->map(function ($id) {
            return strtolower($id);
        })->all();

        $data = [];
        foreach ($coops as $coop) {
            try {
                foreach ($coop->getCoopInfo()->members as $member) {
                    if (!in_array(strtolower($member->id), $membersIds)) {
                        continue;
                    }

                    $data[] = [
                        'name'      => $member->name,
                        'rate'      => round($member->eggsPerSecond * 60 * 60),
                        'eggs_laid' => round($member->eggsLaid),
                    ];
                }
            } catch (CoopNotFoundException $e) {}
        }
        $counter = 1;
        return collect($data)->sortBy([
            [$this->sort, 'desc'],
            ['name', 'desc'],
        ])->map(function ($member) use (&$counter) {
            $member['name'] = $counter . '. ' . $member['name'];
            $member[$this->sort] = resolve(Egg::class)->format($member[$this->sort], 3);
            $counter++;
            return $member;
        })->slice(0, 20)->all();
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
            return [$coops];
        }

        $parts = $this->parts;

        if (in_array(Arr::get($parts, 2, ''), $this->sortByOptions)) {
            $this->sort = $parts[2];
        }

        $contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('Name', Column::ALIGN_LEFT));
        if ($this->sort == 'rate') {
            $table->addColumn('rate', new Column('Rate', Column::ALIGN_RIGHT));
        }
        if ($this->sort == 'eggs_laid') {
            $table->addColumn('eggs_laid', new Column('Laid', Column::ALIGN_RIGHT));   
        }

        $coopsData = $this->memberData($coops, false);
        return $this->getTable($table, $coopsData);
    }

    public function help(): string
    {
        $this->isAdmin();
        return '{Contract ID} {sort default=rate} - Display all members of coops order by rate/eggs_laid';
    }

    public function description(): string
    {
        return 'Display all members of coops order by rate/eggs_laid';
    }

    public function options(): array
    {
        $contracts = $this->getAvailableContractOptions();

        return [
            [
                'type'        => 3,
                'name'        => 'contract_id',
                'description' => 'Contract ID',
                'required'    => true,
                'choices'     => $contracts,
            ],
            [
                'type'        => 3,
                'name'        => 'sort_by',
                'description' => 'Sort Table by',
                'required'    => false,
                'choices'     => [
                    [
                        'name'  => 'Rate',
                        'value' => 'rate',
                    ],
                    [
                        'name'  => 'Eggs Laid',
                        'value' => 'eggs_laid',
                    ]
                ],
            ]
        ];
    }
}
