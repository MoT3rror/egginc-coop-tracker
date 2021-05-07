<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;
use App\Models\Contract;
use App\Models\Guild;

class Base
{
    public $authorId;

    public $authorName;

    public $guildId;

    public $channelId;

    public $guild = null;

    protected $middlewares = [];

    public $parts;

    public $skipMiddleWareChecks;

    public $globalSlash = false;

    public $guildOnly = false;

    public function __construct(int $authorId, string $authorName, ?int $guildId = null, ?int $channelId = null, $parts = [], $skipMiddleWareChecks = false)
    {
        $this->authorId = $authorId;
        $this->authorName = $authorName;
        $this->guildId = $guildId;
        $this->parts = $parts;
        $this->channelId = $channelId;

        if ($this->guildId) {
            $this->guild = $this->setGuild();
        }
        if ($skipMiddleWareChecks) {
            return;
        }

        foreach ($this->middlewares as $middleware) {
            $this->$middleware();
        }
    }

    private function setGuild(): Guild
    {
        return Guild::findByDiscordGuildId($this->guildId);
    }
    
    private function isAdmin()
    {
        $this->guild->sync();
        $admin = false;
        foreach ($this->guild->roles as $role) {
            if ($role->is_admin && $role->members->contains('discord_id', $this->authorId))  {
                $admin = true;
                break;
            }
        }
        if (!$admin) {
            throw new DiscordErrorException('You are not allowed to do that.');
        }
    }

    private function requiresGuild()
    {
        if (!$this->guildId) {
            throw new DiscordErrorException('This command must be run in a server.');
        }
    }

    public function getContractInfo(string $identifier): Contract
    {
        $contract = Contract::firstWhere('identifier', $identifier);

        if (!$contract) {
            throw new DiscordErrorException('Contract not found.');
        }

        return $contract;
    }

    public function help(): string
    {
        return '';
    }

    public function description(): string
    {
        return '';
    }

    public function options(): array
    {
        return [];
    }
}
