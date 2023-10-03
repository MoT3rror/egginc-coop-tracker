<?php
namespace App\DiscordMessages;

use App\Exceptions\DiscordErrorException;
use App\Models\Contract;
use App\Models\Guild;
use App\Models\User;

class Base
{
    public $authorId;

    public $authorName;

    public $user;

    public $guildId;

    public $channelId;

    public ?Guild $guild = null;

    protected $middlewares = [];

    public $parts;

    public $skipMiddleWareChecks;

    public $globalSlash = false;

    public $guildOnly = false;

    public $channelType;

    public $channelParent;

    public function __construct(int $authorId, string $authorName, ?int $guildId = null, ?int $channelId = null, $parts = [], $skipMiddleWareChecks = false)
    {
        $this->authorId = $authorId;
        $this->authorName = $authorName;
        $this->guildId = $guildId;
        $this->parts = $parts;
        $this->channelId = $channelId;
        
        $this->setUser();
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

    private function setUser()
    {
        $this->user = User::unguarded(function () {
            return User::firstOrCreate(
                [
                    'discord_id' => $this->authorId,
                ],
                [
                    'username' => $this->authorName,
                ]
            );
        });
    }

    private function setGuild(): Guild
    {
        return Guild::findByDiscordGuildId($this->guildId);
    }
    
    public function isAdmin()
    {
        $this->requiresGuild();
        
        $this->guild->sync();

        if ($this->guild->admin_users) {
            if (in_array($this->authorId, $this->guild->admin_users)) {
                return;
            }
        }

        $adminRoles = $this->guild->roles()
            ->where('is_admin', 1)
            ->whereHas('members', function ($query) {
                return $query->where('discord_id', $this->authorId);   
            })
            ->get()
            ->count() > 0
        ;
        if ($adminRoles) {
            return;
        }
        throw new DiscordErrorException('You are not allowed to do that.');
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

    public function getAvailableContractOptions(): array
    {
        return Contract::getAllActiveContracts(7)
            ->map(function ($contract) {
                return [
                    'name'  => $contract->name,
                    'value' => $contract->identifier,
                ];
            })
            ->all()
        ;
    }

    public function cleanAt(string $text): string
    {
        return str_replace(['<@!', '<@', '>', '&'], '', $text);
    }

    public function setChannelType(string $type)
    {
        $this->channelType = $type;
    }

    public function setChannelParent(string $parent)
    {
        $this->channelParent = $parent;
    }

    public function getCoopName($prefix, $number): string
    {
        $randomCharacters = 'abcdefghjkmnpqrstuvwy';

        $randomIndex = mt_rand(0, strlen($randomCharacters) - 1);

        return $prefix . $number . $randomCharacters[$randomIndex];
    }
}
