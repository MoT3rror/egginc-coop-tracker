<?php
namespace App\DiscordMessages;

use App\Exceptions\CoopNotFoundException;
use App\Exceptions\DiscordErrorException;
use App\Models\Coop;
use App\SimilarText;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Status extends Base
{
    protected $middlewares = ['requiresGuild'];
    
    private function coops(string $contract): Collection
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

    public function coopData(Collection $coops, bool $hideSimilarText = false): array
    {
        $similarText = new SimilarText;
        $similarPart = $similarText->similar($coops->pluck('coop')->all());

        $data = [];
        foreach ($coops as $coop) {
            $coopName = $hideSimilarText ? str_replace($similarPart, '', $coop->coop) : $coop->coop;
            try {
                $data[] = [
                    'name'      => $coopName . ' ' . $coop->getMembers() . '',
                    'progress'  => $coop->getCurrentEggsFormatted(),
                    'time-left' => $coop->getEstimateCompletion(),
                    'projected' => $coop->getProjectedEggsFormatted(),
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

    public function getStarterMessage(): array
    {
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        return [
            $contract ? $contract->name : $parts[1],
            URL::signedRoute('contract-status', ['guildId' => $this->guildId, 'contractId' => $parts[1]], 60 * 60),
        ];
    }

    public function getTable(Table $table, array $data): string
    {
        $messages = $this->getStarterMessage();
        $messages[] = '```';
        foreach ($table->generate($data) as $row) {
            $messages[] = $row;
        }
        $messages[] = '```';

        return implode("\n", $messages);
    }

    public function message(): string
    {
        $coops = $this->validate();
        if (is_string($coops)) {
            return $coops;
        }
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('Coop ' . $contract->getMaxCoopSize() . '', Column::ALIGN_LEFT));
        $table->addColumn('progress', new Column($contract->getEggsNeededFormatted(), Column::ALIGN_LEFT));
        $table->addColumn('time-left', new Column('E Time', Column::ALIGN_LEFT));
        $table->addColumn('projected', new Column('Proj', Column::ALIGN_LEFT));

        $coopsData = $this->coopData($coops, false);
        return $this->getTable($table, $coopsData);
    }

    public function help(): string
    {
        return 'eb!status {Contract ID} - Display coop info for contract';
    }
}
