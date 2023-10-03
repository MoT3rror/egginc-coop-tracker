<?php
namespace App\DiscordMessages;

use App\Models\Coop;
use App\Models\CoopMember;
use Illuminate\Support\Arr;

class JoinCoop extends Base
{
    protected $middlewares = ['requiresGuild'];

    public $guildOnly = true;

    public function message(): string
    {
        $parts = $this->parts;

        if (!Arr::get($parts, 1)) {
            return 'Contract ID required';
        }

        /** @var Coop[] */
        $coops = Coop::query()
            ->guild($this->guild->discord_id)
            ->contract($parts[1])
            ->get()
        ;

        $contract = $this->getContractInfo($parts[1]);
        $coopMaxSize = $contract->getMaxCoopSize();

        $coop = null;
        foreach ($coops as $createdCoop) {
            $coopMembersIn = collect($createdCoop->getCoopInfo()->contributors)->pluck('userName')->all();
            $usersNotIn = [];
            foreach ($createdCoop->members as $member) {
                if (!in_array($member->user->getEggIncUsernameAttribute(), $coopMembersIn)) {
                    $usersNotIn[] = $member->user;
                }
            }

            $currentMemberCount = count($createdCoop->getCoopInfo()->contributors) + count($usersNotIn);

            if ($currentMemberCount <= $coopMaxSize) {
                $coop = $createdCoop;
                break;
            }
        }

        $coopWasCreated = false;
        if (! $coop) {
            $coop = new Coop;
            $coop->guild_id = $this->guild->discord_id;
            $coop->contract = $parts[1];
            $coop->coop = $this->getCoopName(substr($parts[1], 0, 4), count($coops) + 1);
            $coop->save();
            $coopWasCreated = true;
        }

        $user = $this->guild->members->firstWhere('discord_id', $this->authorId);

        $memberExists = CoopMember::query()
            ->user($user->id)
            ->contractGuild($parts[1], $this->guild->discord_id)
            ->first()
        ;

        if ($memberExists) {
            $memberExists->coop->makeChannel();
            $memberExists->delete();
        }

        $coop->addMember($user);

        $coop->makeChannel();

        if (!$coopWasCreated) {
            $coop->sendMessageToChannel('I added <@' . $user->discord_id . '> to this coop.');
        }

        return 'I added you to a coop';
    }

    public function help(): string
    {
        return '{contractID} - Add yourself to coop for contract.';
    }

    public function description(): string
    {
        return 'Add yourself to a coop';
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
