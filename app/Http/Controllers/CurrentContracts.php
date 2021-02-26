<?php
namespace App\Http\Controllers;

use App\Api\EggInc;
use App\Exceptions\CoopNotFoundException;
use App\Models\Contract;
use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\Guild as GuildModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CurrentContracts extends Controller
{
    public function index()
    {
        $contracts = $this->getContractsInfo();

        return Inertia::render('CurrentContracts', ['contracts' => $contracts]);
    }

    public function status($guildId, $contractId)
    {
        $coops = Coop::contract($contractId)
            ->guild($guildId)
            ->orderBy('position')
            ->get()
        ;

        if ($coops->count() == 0) {
            abort(404);
        }

        $coopsInfo = [];
        foreach ($coops as $coop) {
            try {
                $coopsInfo[] = $coop->getCoopInfo();
            } catch (CoopNotFoundException $e) {
                $coopsInfo[] = [];
            }
        }

        return Inertia::render('ContractStatus', [
            'coopsInfo'    => $coopsInfo,
            'contractInfo' => $this->getContractInfo($contractId),
        ]);
    }

    public function guildStatus($guildId, $contractId, Request $request)
    {
        $guilds = $request->user()->discordGuilds();
        $guild = collect($guilds)
            ->where('id', $guildId)
            ->first()
        ;

        if (!$guild) {
            return redirect()->route('home');
        }

        $coops = Coop::contract($contractId)
            ->guild($guildId)
            ->orderBy('position')
            ->get()
        ;

        if ($coops->count() == 0) {
            abort(404);
        }

        $coopsInfo = [];
        foreach ($coops as $coop) {
            try {
                $coopsInfo[] = $coop->getCoopInfo();
            } catch (CoopNotFoundException $e) {
                $coopsInfo[] = [];
            }
        }

        return Inertia::render('ContractStatus', [
            'coopsInfo'    => $coopsInfo,
            'contractInfo' => $this->getContractInfo($contractId),
        ]);
    }

    private function getContractInfo($identifier)
    {
        $contract = Contract::firstWhere('identifier', $identifier);

        if (!$contract) {
            abort(404, 'Contract not found.');
        }

        return $contract->raw_data;
    }

    public function makeCoops(Request $request, $guildId, $contractId)
    {
        $coops = Coop::contract($contractId)
            ->guild($guildId)
            ->orderBy('position')
            ->get()
        ;

        return Inertia::render('MakeCoops', [
            'contractInfo' => $this->getContractInfo($contractId),
            'guild'        => GuildModel::findByDiscordGuildId($guildId),
            'coopsDb'      => $coops,
        ]);
    }

    public function makeCoopsSave(Request $request, $guildId, $contractId)
    {
        if (!is_array($request->input('coops'))) {
            return abort(405, 'Invalid data.');
        }

        $coops = Coop::contract($contractId)
            ->guild($guildId)
            ->orderBy('position')
            ->get()
        ;
        foreach ($request->input('coops') as $index => $coop) {
            $coopModel = $coops->where('position', $index + 1)->first();
            if (!$coopModel) {
                $coopModel = new Coop;
                $coopModel->contract = $contractId;
                $coopModel->guild_id = $guildId;
                $coopModel->position = $index + 1;
            }
            $coopModel->coop = $coop['name'];
            $coopModel->save();

            foreach ($coop['members'] as $memberIndex => $member) {
                $coopMember = $coopModel->members->get($memberIndex);

                if (!$coopMember) {
                    $coopMember = new CoopMember;
                }
                if ($member) {
                    $coopMember->user_id = $member;
                    $coopModel->members()->save($coopMember);
                } else {
                    $coopMember->delete();
                }
            }
        }

        return 'success';
    }

    public function makeChannels($guildId, $contractId)
    {
        $coops = Coop::contract($contractId)
            ->guild($guildId)
            ->orderBy('position')
            ->get()
        ;

        foreach ($coops as $coop) {
            $coop->makeChannel();
        }

        return 'success';
    }
}
