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

class Contributions extends Status
{    
    protected $sort = 'eggs_laid';

    protected $sortByOptions = ['eggs_laid', 'contribution_percent', 'rate'];

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
                    $status = 'A';
                    if (object_get($member, 'timeCheatDetected')) {
                        $status = 'C';
                    }
                    if (!object_get($member, 'active')) {
                        $status = 'Z';
                    }
                    if (object_get($member, 'leech')) {
                        $status = 'L';
                    }

                    $data[] = [
                        'name'                 => $member->name,
                        'rate'                 => round($member->eggsPerSecond * 60 * 60),
                        'eggs_laid'            => round($member->eggsLaid),
                        'contribution'         => round($member->eggsLaid / $coop->getCurrentEggs() * 100) . '%',
                        'status'               => $status,
                        'contribution_percent' => round($member->eggsLaid / $coop->getCurrentEggs() * 100),
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
            $member['rate'] = resolve(Egg::class)->format($member['rate'], 3);
            $member['eggs_laid'] = resolve(Egg::class)->format($member['eggs_laid'], 3);
            $counter++;
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

        foreach (collect($data)->chunk(25) as $index => $chunk) {
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
        $table->addColumn('rate', new Column('Rate', Column::ALIGN_RIGHT));
        $table->addColumn('eggs_laid', new Column('Laid', Column::ALIGN_RIGHT));
        $table->addColumn('contribution', new Column('Cont', Column::ALIGN_RIGHT));
        $table->addColumn('status', new Column('S', Column::ALIGN_LEFT));

        $coopsData = $this->memberData($coops, false);
        return $this->getTable($table, $coopsData);
    }

    public function help(): string
    {
        return '{Contract ID} {order=eggs_laid,contribution_percent,rate} - Display all members of coops order by selected order. Default = eggs_laid';
    }

    public function description(): string
    {
        return 'Display all members of coops order by eggs laid';
    }

    public function options(): array
    {
        $parent = parent::options();

        $parent[] = [
            'type'        => 3,
            'name'        => 'sort',
            'description' => 'Sort (Default = eggs_laid)',
            'required'    => false,
            'choices'     => [
                [
                    'name'  => 'Eggs Laid',
                    'value' => 'eggs_laid',
                ],
                [
                    'name'  => 'Contribution %',
                    'value' => 'contribution_percent',
                ],
                [
                    'name'  => 'Rate',
                    'value' => 'rate',
                ]
            ],
        ];
        return $parent;
    }
}
