<?php
namespace App\DiscordMessages;

use App\Formatters\Egg;
use App\Models\Coop;
use Illuminate\Support\Arr;
use Cache;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Tracker extends Base
{
    public $contract;

    public $coop;

    public $globalSlash = true;

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

    public function guildSortBy(): string
    {
        switch (object_get($this->guild, 'tracker_sort_by')) {
            case 'eggs_per_second':
                return 'eggsPerSecond';
            case 'earning_bonus':
            default:
                return 'earningBonusOom';
        }
    }

    public function coopData(): array
    {
        $data = [];
        $members = collect($this->coop->getCoopInfo()->contributors)->sortByDesc($this->guildSortBy());
        foreach ($members as $member) {
            $showDecimals = $member->contributionRate * 60 * 60 > (1 * pow(10, 15)) ? 2 : 0;
            $boosted = $member->contributionRate * 60 * 60 > (1.2 * pow(10, 15));
            $status = 'A';
            if (!object_get($member, 'active')) {
                $status = 'Z';
            }
            if (object_get($member, 'leech')) {
                $status = 'L';
            }
            $deflector = 0;
            $buffs = object_get($member, 'buffHistory');
            if ($buffs && object_get($buffs[count($buffs) - 1], 'eggLayingRate')) {
                $deflector = (object_get($buffs[count($buffs) - 1], 'eggLayingRate') - 1) * 100;
            }
            $data[] = [
                'name'    => ($boosted ? 'X ' : '  ') .  e(substr($member->userName, 0, 20)),
                'rate'    => resolve(Egg::class)->format($member->contributionRate * 60 * 60, $showDecimals),
                'tokens'  => object_get($member, 'boostTokens', 0),
                'status'  => $status,
                'deflector' => $deflector . '%',
            ];
        }

        return $data;
    }

    public function starterMessage(): array
    {
        $messages = [];
        $messages[] = $this->contract->name . '(' . $this->contract->identifier . ') - ' . $this->coop->coop;
        $messages[] = 'Eggs: ' . $this->coop->getCurrentEggsFormatted();
        $messages[] = 'Rate: ' . $this->coop->getTotalRateFormatted() . '/hr Need: '. $this->coop->getNeededRateFormatted();
        $messages[] = 'Projected Eggs: ' . $this->coop->getProjectedEggsFormatted() . '/' . $this->coop->getEggsNeededFormatted();
        $messages[] = 'Estimate/Time Left: ' . $this->coop->getEstimateCompletion() . '/' . $this->coop->getTimeLeftFormatted();
        $messages[] = 'Members: ' . $this->coop->getMembers() . '/' . $this->contract->getMaxCoopSize();
        $messages[] = 'Creator: `' . $this->coop->getCreator() . '`';
        $messages[] = 'Deflector: ' . $this->coop->getDeflectorTotal() . '%';
        $messages[] = 'Grade: ' . $this->coop->getGrade();

        return $messages;
    }

    public function getTable(Table $table, array $data): array
    {
        $groupOfMessages = [implode("\n", $this->starterMessage()) . "\n"];

        $ids = [];
        foreach (collect($data)->chunk(30) as $index => $chunk) {
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

        if ($this->coop->id) {
            $coopMembersIn = collect($this->coop->getCoopInfo()->contributors)->pluck('userName')->all();
            $usersNotIn = collect([]);
            foreach ($this->coop->members as $member) {
                if (!in_array($member->user->getEggIncUsernameAttribute(), $coopMembersIn)) {
                    $usersNotIn[] = $member->user;
                }
            }

            if (count($usersNotIn) > 0) {
                $groupOfMessages[$index] .= PHP_EOL . 'Missing:' . PHP_EOL . '- ' . $usersNotIn->implode('username_with_egg_name', PHP_EOL . '- ');
            }
        } 

        return $groupOfMessages;
    }

    public function message(): array
    {
        if (count($this->parts) == 1) {
            $this->coop = Coop::query()
                ->channelId($this->channelType == 'GUILD_PUBLIC_THREAD' ? $this->channelParent : $this->channelId)
                ->first()
            ;
            if ($this->coop) {
                $this->parts[1] = $this->coop->contract;
                $this->parts[2] = $this->coop->coop;
            }
        }

        $errorMessage = $this->validate();
        if (is_string($errorMessage)) {
            return [$errorMessage];
        }

        $this->contract = $this->getContractInfo($this->parts[1]);
        if (!$this->coop) {
            $this->coop = new Coop;
            $this->coop->contract = $this->contract->identifier;
            $this->coop->coop = $this->parts['2'];
        }
        try {
            $cacheKey = $this->contract->identifier . '-' . $this->parts['2'];

            Cache::forget($cacheKey);
            $this->coop->getCoopInfo()->contributors;
        } catch (\Exception $e) {
            report($e);
            return ['Coop not created'];
        }

        $table = new Table();
        $table->addColumn('name', new Column('Boosted/Name', Column::ALIGN_LEFT));
        $table->addColumn('rate', new Column('Rate', Column::ALIGN_LEFT));
        $table->addColumn('tokens', new Column('T', Column::ALIGN_LEFT));
        $table->addColumn('status', new Column('S', Column::ALIGN_LEFT));
        $table->addColumn('deflector', new Column('D', Column::ALIGN_LEFT));

        try {
            $coopData = $this->coopData();
        } catch (\App\Exceptions\CoopNotFoundException $e) {
            return ['Coop not found.'];
        }
        return $this->getTable($table, $coopData);
    }

    public function help(): string
    {
        return '{Contract ID} {Coop ID} - Display boost/token info for coop.';
    }

    public function description(): string
    {
        return 'Display boost/token info for coop.';
    }

    public function options(): array
    {
        $contracts = $this->getAvailableContractOptions();

        return [
            [
                'type'        => 3,
                'name'        => 'contract_id',
                'description' => 'Contract ID',
                'required'    => false,
                'choices'     => $contracts,
            ],
            [
                'type'        => 3,
                'name'        => 'coop_id',
                'description' => 'Coop',
                'required'    => false,
            ]
        ];
    }
}