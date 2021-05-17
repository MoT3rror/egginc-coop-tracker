<?php

namespace Tests\Unit\DiscordMessages;

use App\DiscordMessages\Help;

class HelpTest extends Base
{
    public function testMessage()
    {
        $message = $this->makeDiscordMessage(Help::class);
        $expects = <<<HELP
```
eb!add {Contract ID} {Coop} {?Coop} - Add coop to tracking, multiple can be added by this command. When multiple is added, the position of the coops is set.
eb!available {Contract ID} - Get who has not complete contract. Will not validate contract ID.
eb!contracts - Display current contracts with IDs.
eb!coopless {Contract ID} - Find players not in contract.
eb!coop-leaderboard {Contract ID} {sort default=rate} - Display all members of coops order by rate/eggs_laid
eb!delete {contractID} {Coop} - Remove coop from tracking
eb!ge Get player golden egg stats.
eb!help - Display list of available commands.
eb!hi Just say hi.
eb!leaders {Contract ID} {sort default=rate} - Display all members of coops order by rate/eggs_laid
eb!mod-set-player-id {@user} {Egg Inc Player ID} - Player ID starts with EI (letter i)
eb!players-not-in-coop {Contract ID} - Find players not in contract.
eb!rank Get player stats/rank.
eb!remind {Contract ID} {Hours} {Minutes}
eb!replace {Contract ID} {Coop} {New Coop} - Replace coop name
eb!set-player-id {Egg Inc Player ID} - Player ID starts with EI (letter i)
eb!status {Contract ID} - Display coop info for contract
eb!short-status {Contract ID} - Short version of status
eb!tracker {Contract ID} {Coop ID} - Display boost/token info for coop.
eb!unavailable {Contract ID} - Get users that do not have the contract.
```
HELP;
        $actual = $message->message();
        $this->assertEquals($expects, $actual);
    }
}