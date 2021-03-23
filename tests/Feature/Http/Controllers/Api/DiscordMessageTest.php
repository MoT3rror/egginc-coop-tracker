<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\EggInc;
use App\Models\Contract;
use App\Models\Coop;
use App\Models\Guild;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Mockery;
use RestCord\DiscordClient;
use RestCord\OverriddenGuzzleClient;
use StdClass;
use Tests\TestCase;

class DiscordMessageTest extends TestCase
{
    use RefreshDatabase;

    private $atBotUser = 'eb!';

    private $guildId = 1;

    private function sendDiscordMessage(string $message, int $authorId = 123456)
    {
        $this->mockGuildCall();

        Role::creating(function($role) {
            $role->show_members_on_roster = true;
            $role->is_admin = $role->discord_id == 1;
        });

        $response = $this->postJson(
            '/api/discord-message',
            [
                'channel'   => [
                    'id'    => 1,
                    'guild' => ['id' => $this->guildId],
                ],
                'content'   => $this->atBotUser . $message,
                'atBotUser' => $this->atBotUser,
                'author'    => [
                    'id'       => $authorId,
                    'username' => 'Test',
                ],
            ]
        );

        if ($response->getStatusCode() == 500) {
            dd($response->getContent());
        }

        return $response
            ->assertStatus(200)
            ->decodeResponseJson('message')
            ->json(['message'])
        ;
    }

    private function mockGuildCall()
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
    }

    public function testLove()
    {
        $message = $this->sendDiscordMessage('love');

        $this->assertEquals('What is this thing called love?', $message);
    }

    public function testHelp()
    {
        $message = $this->sendDiscordMessage('help');

        $expect = <<<HELP
```
eb!help - Display list of available commands.
eb!add {Contract ID} {Coop} {?Coop} - Add coop to tracking, multiple can be added by this command. When multiple is added, the position of the coops is set.
eb!available {Contract ID} - Get who has not complete contract. Will not validate contract ID.
eb!contracts - Display current contracts with IDs.
eb!delete {contractID} {Coop} - Remove coop from tracking
eb!ge Get player golden egg stats.
eb!rank Get player stats/rank.
eb!remind {Contract ID} {Hours} {Minutes}
eb!replace {Contract ID} {Coop} {New Coop} - Replace coop name
eb!set-player-id {Egg Inc Player ID} - Player ID starts with EI (letter i)
eb!status {Contract ID} - Display coop info for contract
eb!s {Contract ID} - Short version of status
eb!tracker {Contract ID} {Coop ID} - Display boost/token info for coop.
eb!unavailable {Contract ID} - Get users that do not have the contract.
```
HELP;
        $this->assertEquals($expect, $message);
    }

    public function testHi()
    {
        $message = $this->sendDiscordMessage('hi');

        $this->assertEquals('Hello <@123456>!', $message);
    }

    public function testHiWithParams()
    {
        $message = $this->sendDiscordMessage('hi FriendA FriendB');

        $expect = <<<HI_MULTI_LINE
Hello <@123456>!
Hello <@FriendA>!
Hello <@FriendB>!
HI_MULTI_LINE;
        $this->assertEquals($expect, $message);
    }

    public function testHiWithParamsNewLine()
    {
        $message = $this->sendDiscordMessage('hi FriendA' . PHP_EOL . 'FriendB');

        $expect = <<<HI_MULTI_LINE
Hello <@123456>!
Hello <@FriendA>!
Hello <@FriendB>!
HI_MULTI_LINE;
        $this->assertEquals($expect, $message);
    }

    public function testCurrentContracts()
    {
        $contract = $this->makeSampleContract(['expiration' => now()->addDays(7)]);

        $message = $this->sendDiscordMessage('contracts');

        $expect = <<<CONTRACTS
```
{$contract->identifier}($contract->name)
```
CONTRACTS;
        $this->assertEquals($expect, $message);
    }

    public function testAdd()
    {
        $contract = $this->makeSampleContract();

        $message = $this->sendDiscordMessage('add ' . $contract->identifier . ' test');
        $expect = 'Coop added successfully.';

        $this->assertEquals($expect, $message);
    }

    public function testReplace()
    {
        $contract = $this->makeSampleContract();

        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('replace ' . $contract->identifier . ' test test2');
        $expect = 'Coop has been replaced.';

        $this->assertEquals($expect, $message);
        $this->assertDatabaseHas('coops', [
            'coop'     => 'test2',
            'contract' => $contract->identifier,
        ]);
    }

    public function testAdminFail()
    {
        $contract = $this->makeSampleContract();

        $message = $this->sendDiscordMessage('delete ' . $contract->identifier . ' test', 654321);
        $expect = 'You are not allowed to do that.';

        $this->assertEquals($expect, $message);   
    }

    /**
     * @depends testAdd
     */
    public function testAddMultiple()
    {
        $contract = $this->makeSampleContract();

        $message = $this->sendDiscordMessage('add ' . $contract->identifier . ' test test2');
        $expect = 'Coops added successfully.';

        $this->assertEquals($expect, $message);

        $coops = Coop::contract($contract->identifier)
            ->guild($this->guildId)
            ->orderBy('position')
            ->get()
        ;
        $this->assertEquals(2, $coops->count());

        foreach ($coops as $coop) {
            switch ($coop->position) {
                case 1:
                    $this->assertEquals('test', $coop->coop);
                    break;
                case 2:
                    $this->assertEquals('test2', $coop->coop);
                    break;
            }
        }
    }

    /**
     * @depends testAdd
     */
    public function testAddMultipleMultiLine()
    {
        $contract = $this->makeSampleContract();

        $message = $this->sendDiscordMessage('add ' . $contract->identifier . ' test' . PHP_EOL . ' test2 ');
        $expect = 'Coops added successfully.';

        $this->assertEquals($expect, $message);

        $coops = Coop::contract($contract->identifier)
            ->guild($this->guildId)
            ->orderBy('position')
            ->get()
        ;
        $this->assertEquals(2, $coops->count());

        foreach ($coops as $coop) {
            switch ($coop->position) {
                case 1:
                    $this->assertEquals('test', $coop->coop);
                    break;
                case 2:
                    $this->assertEquals('test2', $coop->coop);
                    break;
            }
        }
    }

    /**
     * @depends testAddMultiple
     */
    public function testUpdatePosition()
    {
        $this->testAddMultiple();

        $contract = Contract::find(1);

        $message = $this->sendDiscordMessage('add ' . $contract->identifier . ' test2 test');
        $expect = 'Coops added successfully.';

        $this->assertEquals($expect, $message);

        $coops = Coop::contract($contract->identifier)
            ->guild($this->guildId)
            ->orderBy('position')
            ->get()
        ;
        $this->assertEquals(2, $coops->count());

        foreach ($coops as $coop) {
            switch ($coop->position) {
                case 1:
                    $this->assertEquals('test2', $coop->coop);
                    break;
                case 2:
                    $this->assertEquals('test', $coop->coop);
                    break;
            }
        }
    }

    public function testDelete()
    {
        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('delete ' . $contract->identifier . ' test');
        $expect = 'Coop has been deleted.';

        $this->assertEquals($expect, $message);
    }

    public function testStatus()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('status ' . $contract->identifier);
        $message = preg_replace('"(https?://.*)"', '', $message);

        $expect = <<<STATUS
Ion Drive II
Teams on Track: 1/1

```
Coop 5 | 600q | E Time | Proj/T
------ | ---- | ------ | ------
test 5 | 746q | CPLT   | 746q X
```
STATUS;

        $this->assertEquals($expect, $message);
    }

    public function testStatusWithoutLink()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        // have to call twice because guild is not created yet. guess I could create
        $this->sendDiscordMessage('status ' . $contract->identifier);
        $guild = Guild::find(1);
        $guild->show_link_on_status = 0;
        $guild->save();
        $message = $this->sendDiscordMessage('status ' . $contract->identifier);

        $expect = <<<STATUS
Ion Drive II
Teams on Track: 1/1
```
Coop 5 | 600q | E Time | Proj/T
------ | ---- | ------ | ------
test 5 | 746q | CPLT   | 746q X
```
STATUS;

        $this->assertEquals($expect, $message);
    }

    public function testStatusWithLargeEggRate()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/clean-crypto-grizzlycoin.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('status ' . $contract->identifier);
        $message = preg_replace('"(https?://.*)"', '', $message);

        $expect = <<<STATUS
Ion Drive II
Teams on Track: 1/1

```
Coop 5  | 600q | E Time | Proj/T 
------- | ---- | ------ | -------
test 13 | 771q | CPLT   | 10.9Q X
```
STATUS;

        $this->assertEquals($expect, $message);
    }

    public function testStatusCompletedCoop()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('status ' . $contract->identifier);
        $message = preg_replace('"(https?://.*)"', '', $message);

        $expect = <<<STATUS
Ion Drive II
Teams on Track: 1/1

```
Coop 5 | 600q | E Time | Proj/T
------ | ---- | ------ | ------
test 5 | 746q | CPLT   | 746q X
```
STATUS;

        $this->assertEquals($expect, $message);
    }

    public function testRemind()
    {
        \Queue::fake();

        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);
        $message = $this->sendDiscordMessage('remind ' . $contract->identifier . ' 1 30');
        $this->assertEquals('Reminders set.', $message);
        \Queue::assertPushed(\App\Jobs\RemindCoopStatus::class, 2);
    }

    public function testShortStatus()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $this->makeSampleCoop($contract, 'honeyadv1');
        $this->makeSampleCoop($contract, 'honeyadv2');
        $this->makeSampleCoop($contract, 'honeyadv3');

        $message = $this->sendDiscordMessage('s ' . $contract->identifier);
        $expect = <<<STATUS
Ion Drive II
```
C 5 | 600q | E Time | Proj/T
--- | ---- | ------ | ------
1 5 | 746q | CPLT   | 746q X
2 5 | 746q | CPLT   | 746q X
3 5 | 746q | CPLT   | 746q X
```
STATUS;

        $this->assertEquals($expect, $message);
    }

    public function testSetPlayerId()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $message = $this->sendDiscordMessage('set-player-id 12345');
        $expect = <<<RANK
```
MoT3rror
Soul Eggs: 18.732Q
Prophecy Eggs: 147
Earning Bonus: 3.415o
Farmer Role: Yottafarmer 2
Group Role: 
Total Soul Eggs Needed for Next Rank: 54.850Q
Total Prophecy Eggs Needed for Next Rank: 159
Current Golden Eggs: 136,318,185
Total Golden Eggs: 170,060,259
Drones/Elite: 56,680/2,543
Prestiges: 223
Boosts Used: 1,593
```
RANK;
        $this->assertEquals($message, $expect);

        $this->assertDatabaseHas('guilds', ['discord_id' => 1, 'name' => 'Test']);
        $this->assertDatabaseHas('users', ['discord_id' => 123456, 'egg_inc_player_id' => '12345']);
    }
    
    /**
     * depends testSetPlayerId
     */
    public function testListPlayersWithEggId()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();

        $message = $this->sendDiscordMessage('players egg_id');
        $expect = <<<PLAYERS
```
Discord | Egg Inc ID
------- | ----------
Test    | 12345     
```
PLAYERS;

        $this->assertEquals([$expect], $message);
    }

    public function testListPlayerWithRank()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();

        $message = $this->sendDiscordMessage('players rank');
        $expect = <<<PLAYERS
```
Discord | Rank   
------- | -------
Test    | Yotta 2
```
PLAYERS;

        $this->assertEquals([$expect], $message);
    }

    public function testListPlayerEarningBonus()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();

        $message = $this->sendDiscordMessage('players earning_bonus');
        $expect = <<<PLAYERS
```
Discord |     EB
------- | -----:
Test    | 3.415o
```
PLAYERS;

        $this->assertEquals([$expect], $message);
    }

    public function testListPlayersBonusAndRank()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();

        $message = $this->sendDiscordMessage('players earning_bonus rank');
        $expect = <<<PLAYERS
```
Discord |     EB | Rank   
------- | -----: | -------
Test    | 3.415o | Yotta 2
```
PLAYERS;

        $this->assertEquals([$expect], $message);
    }

    public function testRank()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();

        $message = $this->sendDiscordMessage('rank');
        $expect = <<<RANK
```
MoT3rror
Soul Eggs: 18.732Q
Prophecy Eggs: 147
Earning Bonus: 3.415o
Farmer Role: Yottafarmer 2
Group Role: 
Total Soul Eggs Needed for Next Rank: 54.850Q
Total Prophecy Eggs Needed for Next Rank: 159
Current Golden Eggs: 136,318,185
Total Golden Eggs: 170,060,259
Drones/Elite: 56,680/2,543
Prestiges: 223
Boosts Used: 1,593
```
RANK;
        $this->assertEquals($expect, $message);
    }

    public function testRankNoUser()
    {
        $message = $this->sendDiscordMessage('rank');
        $expect = 'Egg Inc Player ID not set. Use `eb!set-player-id {id}` to set.';

        $this->assertEquals($expect, $message);
    }

    public function ntestWhoHasCompleteContract()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();
        $message = $this->sendDiscordMessage('available valentines-2019');

        $expect = '- Test';

        $this->assertEquals($expect, $message);
    }

    public function testWhoHasCompleteContractWithAlreadyComplete()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();
        $message = $this->sendDiscordMessage('available laurel-v-yanny');

        $expect = 'All users have completed the contract.';

        $this->assertEquals($expect, $message);
    }

    public function testHelpless()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['12345'])
                ->andReturn($player)
            ;
        }));

        $this->testSetPlayerId();
        $message = $this->sendDiscordMessage('unavailable laurel-v-yanny');

        $expect = '- Test';

        $this->assertEquals($expect, $message);
    }

    public function testTracker()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('tracker ' . $contract->identifier . ' test');
        $expect = <<<STATUS
Ion Drive II({$contract->identifier}) - test
Eggs: 746q
Rate: 11.4q/hr Need: 0
Projected Eggs: 746q/600q
Estimate/Time Left: CPLT/Past Due
Members: 5/5
```
Boosted/Name | Rate  | Tokens
------------ | ----- | ------
X SukiDevil  | 3.77q | 83    
X elbee1     | 3.77q | 102   
X SuchPerson | 3.77q | 93    
  27ThePulse | 4T    | 67    
  Parasbabü  | 108T  | 56    
```
STATUS;

        $this->assertEquals([0 => $expect], $message);
    }

    public function testPlayersNotInCoop()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['EI57612441763184'])
                ->andReturn($player)
            ;

            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;
        }));

        $this->sendDiscordMessage('set-player-id EI57612441763184');
        
        $contract = $this->makeSampleContract();
        $coop = $this->makeSampleCoop($contract);

        $message = $this->sendDiscordMessage('players-not-in-coop ' . $contract->identifier);
        $expect = <<<STATUS
- Test ()
STATUS;

        $this->assertEquals($expect, $message);
    }
}
