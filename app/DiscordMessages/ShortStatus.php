<?php
namespace App\DiscordMessages;

use App\SimilarText;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class ShortStatus extends Status
{
    public function message(): string
    {
        $coops = $this->validate();
        $parts = $this->parts;
        $contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('C ' . $contract->getMaxCoopSize() . '', Column::ALIGN_LEFT));
        $table->addColumn('progress', new Column($contract->getEggsNeededFormatted(), Column::ALIGN_LEFT));
        $table->addColumn('time-left', new Column('E Time', Column::ALIGN_LEFT));
        $table->addColumn('projected', new Column('Proj', Column::ALIGN_LEFT));

        $coopsData = $this->coopData($coops, true);
        return $this->getTable($table, $coopsData);
    }

    public function getStarterMessage(): array
    {
        $contract = $this->getContractInfo($this->parts[1]);
        return [
            $contract ? $contract->name : $this->parts[1],
        ];
    }

    public function help(): string
    {
        return 'eb!s {Contract ID} - Short version of status';
    }
}
