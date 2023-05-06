<?php
namespace App\DiscordMessages;

use App\Formatters\Egg;
use App\Models\Coop;
use Illuminate\Support\Arr;
use Cache;
use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Coopad extends Base
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
            return ['ecoopad ' . $this->contract->identifier . ' ' . $this->parts['2']];
        } catch (\Exception $e) {
            report($e);
            return ['Coop not created'];
        }
    }

    public function help(): string
    {
        return '{Contract ID} {Coop ID} - Display coopad message.';
    }

    public function description(): string
    {
        return 'Display coopad message.';
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