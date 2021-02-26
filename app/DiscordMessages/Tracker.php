<?php
namespace App\DiscordMessages;

use App\Exceptions\CoopNotFoundException;
use App\Exceptions\DiscordErrorException;
use App\Formatters\Egg;
use App\Models\Coop;
use App\SimilarText;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Tracker extends Base
{
    public $contract;

    public $coop;

    public function validate()
    {
        $parts = $this->parts;
        if (!Arr::get($parts, 1)) {
            return 'Contract ID required.';
        }

        if (!Arr::get($parts, 2)) {
            return 'Coop ID required.';
        }

        return true;
    }

    public function coopData(): array
    {
        $this->coop = new Coop;
        $this->coop->contract = $this->parts['1'];
        $this->coop->coop = $this->parts['2'];

        $data = [];
        $members = collect($this->coop->getCoopInfo()->members)->sortByDesc('earningBonusOom');
        foreach ($members as $member) {
            $showDecimals = $member->eggsPerSecond * 60 * 60 > (1 * pow(10, 15)) ? 2 : 0;
            $boosted = $member->eggsPerSecond * 60 * 60 > (1.2 * pow(10, 15));
            $data[] = [
                'name'    => ($boosted ? 'X ' : '  ') .  $member->name,
                'rate'    => resolve(Egg::class)->format($member->eggsPerSecond * 60 * 60, $showDecimals),
                'tokens'  => $member->tokens,
            ];
        }

        return $data;
    }

    public function starterMessage(): array
    {
        $messages = [];
        $messages[] = $this->contract->name . '(' . $this->contract->identifier . ') - ' . $this->parts[2];
        $messages[] = 'Eggs: ' . $this->coop->getCurrentEggsFormatted();
        $messages[] = 'Rate: ' . $this->coop->getTotalRateFormatted() . '/hr Need: '. $this->coop->getNeededRateFormatted();
        $messages[] = 'Projected Eggs: ' . $this->coop->getProjectedEggsFormatted() . '/' . $this->coop->getEggsNeededFormatted(); 

        return $messages;
    }

    public function getTable(Table $table, array $data): string
    {
        $messages = $this->starterMessage();
        $messages[] = '```';
        foreach ($table->generate($data) as $row) {
            $messages[] = $row;
        }
        $messages[] = '```';

        return implode("\n", $messages);
    }

    public function message(): string
    {
        $errorMessage = $this->validate();
        if (is_string($errorMessage)) {
            return $errorMessage;
        }

        $parts = $this->parts;
        $this->contract = $this->getContractInfo($parts[1]);

        $table = new Table();
        $table->addColumn('name', new Column('Boosted/Name', Column::ALIGN_LEFT));
        $table->addColumn('rate', new Column('Rate', Column::ALIGN_LEFT));
        $table->addColumn('tokens', new Column('Tokens', Column::ALIGN_LEFT));

        try {
            $coopData = $this->coopData();
        } catch (\App\Exceptions\CoopNotFoundException $e) {
            return 'Coop not found.';
        }
        return $this->getTable($table, $coopData);
    }

    public function help(): string
    {
        return 'eb!tracker {Contract ID} {Coop ID} - Display boost/token info for coop.';
    }
}