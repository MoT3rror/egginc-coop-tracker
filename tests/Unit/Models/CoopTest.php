<?php

namespace Tests\Unit\Models;

use App\Api\EggInc;
use App\Models\Contract;
use App\Models\Coop;
use App\Models\CoopMember;
use App\Models\Guild;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RestCord\DiscordClient;
use StdClass;
use Tests\TestCase;

class CoopTest extends TestCase
{
    use RefreshDatabase;

    public function setupCoop(): Coop
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $coopInfo = json_decode(file_get_contents(base_path('tests/files/ion-production-2021-test-coop.json')));

            $mock
                ->shouldReceive('getCoopInfo')
                ->andReturn($coopInfo)
            ;

            $player = json_decode(file_get_contents(base_path('tests/files/mot3rror-player-info.json')));

            $mock
                ->shouldReceive('getPlayerInfo')
                ->withArgs(['1234567'])
                ->andReturn($player)
            ;
        }));

        $contract = Contract::factory()->create();
        $guild = Guild::factory()->create(['coop_channel_parent' => 123456]);
        $coop = new Coop;
        $coop->fill([
            'contract' => $contract->identifier,
            'coop'     => 'test',
        ]);
        $coop->guild_id = $guild->discord_id;
        $coop->save();
        $everyoneRole = Role::factory()->create([
            'guild_id'   => $guild->id,
            'discord_id' => 123456,
            'name'       => '@everyone',
        ]);
        $testRole = Role::factory()->create([
            'guild_id'   => $guild->id,
            'discord_id' => 1234567,
            'name'       => 'test',
            'show_role'  => true,
        ]);
        $user = User::factory()->create([
            'discord_id'        => 1234567,
            'egg_inc_player_id' => '1234567',
        ]);
        $user->roles()->saveMany([$everyoneRole, $testRole]);
        $coopMember = new CoopMember;
        $coopMember->user_id = $user->id;
        $coop->members()->save($coopMember);
        $coop->save();
        $coop->refresh();
        return $coop;
    }

    private function permissions(): array
    {
        return [
            [
                'id'    => config('services.discord.client_id'),
                'allow' => 3072,
                'type'  => 1,
            ],
            [
                'id'    => '123456',
                'deny'  => 1024,
            ],
            [
                'id'    => '1234567',
                'allow' => 3072,
                'type'  => 1,
            ],
        ];
    }

    private function initialMessage(): string
    {
        return 'Ion Drive II - test' . PHP_EOL . '<@1234567> - Yottafarmer 2 - test - 0%';
    }

    public function testPermissions()
    {
        $coop = $this->setupCoop();

        $expected = $this->permissions();
        $actual = $coop->getChannelPermissions();

        $this->assertEquals($expected, $actual);
    }

    public function testInitialMessage()
    {
        $coop = $this->setupCoop();

        $expected = $this->initialMessage();
        $actual = $coop->getInitialMessage();

        $this->assertEquals([$expected], $actual);
    }

    public function testMakeChannels(): Coop
    {
        $coop = $this->setupCoop();
        $discordClient = Mockery::mock(DiscordClient::class);
        $discordClient->guild = Mockery::mock(\RestCord\Interfaces\Guild::class, function ($mock) use ($coop) {
            $returnResult = new StdClass;
            $returnResult->id = 123456;

            $mock
                ->shouldReceive('createGuildChannel')
                ->with([
                    'guild.id'              => $coop->guild()->discord_id,
                    'name'                  => $coop->coop,
                    'permission_overwrites' => $this->permissions(),
                    'parent_id'             => 123456,
                    'position'              => 1,
                ])
                ->andReturn($returnResult)
            ;
        });

        $discordClient->channel = Mockery::mock(\RestCord\Interfaces\Channel::class, function ($mock) {
            $mock
                ->shouldReceive('createMessage')
                ->with([
                    'channel.id' => 123456,
                    'content'    => $this->initialMessage(),
                ])
            ;
        });

        $this->instance('DiscordClientBot', $discordClient);

        $coop->makeChannel();
        return $coop;
    }

    public function testUpdateChannel()
    {
        $coop = $this->testMakeChannels();

        $discordClient = Mockery::mock(DiscordClient::class);

        $discordClient->channel = Mockery::mock(\RestCord\Interfaces\Channel::class, function ($mock) use ($coop) {
            $mock
                ->shouldReceive('modifyChannel')
                ->with([
                    'channel.id'            => 123456,
                    'name'                  => $coop->coop,
                    'permission_overwrites' => $this->permissions(),
                ])
            ;
        });

        $this->instance('DiscordClientBot', $discordClient);
        $coop->makeChannel();
    }
}
