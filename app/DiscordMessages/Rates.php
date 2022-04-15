<?php
namespace App\DiscordMessages;

use App\Exceptions\CoopNotFoundException;
use App\Models\Coop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Rates extends Base
{
    protected $middlewares = ['requiresGuild'];

    protected $totalTeams = 0;

    protected $teamsOnTrack = 0;

    public $guildOnly = true;
    
    public function coops(string $contract): Collection
    {
        return Coop::contract($contract)
            ->guild($this->guildId)
            ->orderBy('position')
            ->get()
        ;
    }

    public function validate()
    {
        $parts = $this->parts;
        if (!Arr::get($parts, 1)) {
            return 'Contract ID required.';
        }

        $coops = $this->coops($parts[1]);

        if ($coops->count() == 0) {
            return 'Invalid contract ID or no coops setup.';
        }

        return $coops;
    }

    public function coopData(Collection $coops): array
    {
        $data = [];
        foreach ($coops as $coop) {
            try {
                $data[] = [
                    'name' => $coop->coop,
                    'rate' => $coop->getTotalRateFormatted(),
                ];
            } catch (CoopNotFoundException $e) {
                $data[] = [
                    'name' => $coop->coop,
                    'rate' => 'NA',
                ];
            }
        }
        return $data;
    }

    public function getStarterMessage(): array
    {
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        return [
            $contract ? $contract->name : $parts[1],
        ];
    }

    public function getTable(Table $table, array $data): array
    {
        $groupOfMessages = [$this->getStarterMessage()];
        $tables = collect($data)->chunk(35);
        foreach ($tables as $key => $chunk) {
            $groupOfMessages[$key][] = '```';
            foreach ($table->generate($chunk->all()) as $row) {
                $groupOfMessages[$key][] = $row;
            }
            $groupOfMessages[$key][] = '```';
        }

        foreach ($groupOfMessages as $key => $value) {
            $groupOfMessages[$key] = implode(PHP_EOL, $value);
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
        $contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('Coop ' . $contract->getMaxCoopSize() . '', Column::ALIGN_LEFT));
        $table->addColumn('rate', new Column('Rate', Column::ALIGN_LEFT));

        $coopsData = $this->coopData($coops, false);
        return $this->getTable($table, $coopsData);
    }

    public function help(): string
    {
        $this->isAdmin();
        return '{Contract ID} - Display coop info for contract';
    }

    public function description(): string
    {
        return 'Display coop rates for contract';
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
        ];
    }
}
