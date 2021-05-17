<?php

namespace Tests;

use App\Models\Contract;
use App\Models\Coop;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use RestCord\DiscordClient;
use RestCord\OverriddenGuzzleClient;
use StdClass;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function makeSampleContract(array $data = []): Contract
    {
        return Contract::factory()->create($data);
    }

    public function makeSampleCoop(?Contract $contract = null, string $coopName = 'test'): Coop
    {
        if (!$contract) {
            $contract = $this->makeSampleContract();
        }
        $coop = Coop::make([
            'contract' => $contract->identifier,
            'coop'     => $coopName,
        ]);
        $coop->guild_id = 1;
        $coop->save();

        return $coop;
    }

    public function mockGuildCall()
    {
        app()->bind(DiscordClient::class, function () {
            return Mockery::mock(DiscordClient::class, function ($mock) {
                $guildCall = Mockery::mock(OverriddenGuzzleClient::class, function ($mock) {
                    $guild = new StdClass;
                    $guild->name = 'Test';

                    $mock
                        ->shouldReceive('getGuild')
                        ->andReturn($guild)
                    ;

                    $role = new StdClass;
                    $role->id = 1;
                    $role->name = 'Admin';

                    $role2 = new StdClass;
                    $role2->id = 2;
                    $role2->name = 'Everybody';

                    $roles = [$role, $role2];

                    $mock
                        ->shouldReceive('getGuildRoles')
                        ->andReturn($roles);
                    ;

                    $member = new StdClass;
                    $member->user = new StdClass;
                    $member->user->bot = false;
                    $member->user->id = 123456;
                    $member->user->username = 'Test';
                    $member->roles = [1, 2];

                    $member2 = new StdClass;
                    $member2->user = new StdClass;
                    $member2->user->bot = false;
                    $member2->user->id = 654321;
                    $member2->user->username = 'Test 2';
                    $member2->roles = [2];

                    $members = [$member, $member2];

                    $mock
                        ->shouldReceive('listGuildMembers')
                        ->andReturn($members)
                    ;
                });

                $mock->guild = $guildCall;

                $userCall = Mockery::mock(OverriddenGuzzleClient::class, function ($mock) {
                    $user = new StdClass;
                    $user->id = 123456;
                    $user->username = 'Test';
                    $user->email = 'test@example.com';

                    $mock
                        ->shouldReceive('getUser')
                        ->andReturn($user)
                    ;

                    $member = new StdClass;
                    $member->id = 1;

                    $mock->shouldReceive('getCurrentUserGuilds')->andReturn([$member]);
                });

                $mock->user = $userCall;
            });
        });

        Role::creating(function($role) {
            $role->show_members_on_roster = true;
            $role->is_admin = $role->discord_id == 1;
            $role->part_of_team = true;
        });
    }
}
