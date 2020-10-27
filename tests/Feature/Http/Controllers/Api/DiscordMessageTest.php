<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Contract;
use App\Models\Coop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordMessageTest extends TestCase
{
    use RefreshDatabase;

    private $atBotUser = 'eb!';

    private function sendDiscordMessage(string $message): string
    {
        $response = $this->postJson(
            '/api/discord-message',
            [
                'channel'   => ['guild' => ['id' => 1]],
                'content'   => $this->atBotUser . $message,
                'atBotUser' => $this->atBotUser,
                'author'    => ['id' => 723977563650654259],
            ]
        );

        return $response
            ->assertStatus(200)
            ->decodeResponseJson('message')
        ;
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
eb!help - Displays list of commands
eb!contracts - Display current contracts with IDs
eb!status contractId - Display coop info for contract
eb!add {contractID} {Coop} - Add coop to tracking
eb!delete {contractID} {Coop} - Remove coop from tracking
```
HELP;
        $this->assertEquals($expect, $message);
    }

    public function testHi()
    {
        $message = $this->sendDiscordMessage('hi');

        $this->assertEquals('Hello <@723977563650654259>!', $message);
    }

    public function testCurrentContracts()
    {
        $contract = factory(Contract::class)
            ->create(['expiration' => now()->addDays(7)])
        ;

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
        $contract = factory(Contract::class)->create();

        $message = $this->sendDiscordMessage('add ' . $contract->identifier . ' test');
        $expect = 'Coop added successfully.';

        $this->assertEquals($expect, $message);
    }

    public function testDelete()
    {
        $contract = factory(Contract::class)->create();
        $coop = Coop::make([
            'contract' => $contract->identifier,
            'guild_id' => 1,
            'coop'     => 'test',
        ]);
        $coop->guild_id = 1;
        $coop->save();

        $message = $this->sendDiscordMessage('delete ' . $contract->identifier . ' test');
        $expect = 'Coop has been deleted.';

        $this->assertEquals($expect, $message);
    }
}
