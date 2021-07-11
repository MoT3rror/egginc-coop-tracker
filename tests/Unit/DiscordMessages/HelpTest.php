<?php

namespace Tests\Unit\DiscordMessages;

use App\DiscordMessages\Help;

class HelpTest extends Base
{
    public function testMessage()
    {
        $message = $this->makeDiscordMessage(Help::class);
        $expects = [<<<HELP
```
eb!add {Contract ID} {Coop} {?Coop} - Add coop to tracking, multiple can be added by this command. When multiple is added, the position of the coops is set.
eb!atrisk {Contract ID} - Coop Member Status.
eb!available {Contract ID} - Get who has not complete contract. Will not validate contract ID.
eb!boot-warning {Contract ID} - Coop Member Status.
eb!contracts - Display current contracts with IDs.
eb!contributions {Contract ID} - Display all members of coops order by eggs laid
eb!coopless {Contract ID} - Find players not in contract.
eb!coop-leaderboard {Contract ID} {sort default=rate} - Display all members of coops order by rate/eggs_laid
eb!delete {contractID} {Coop} - Remove coop from tracking
eb!delete-channels {Contract ID} - Delete coop channels in mass.
eb!ge Get player golden egg stats.
eb!get-my-id - Get current set Egg Player ID.
eb!get-player-id - Get current set Egg Player ID of user.
eb!help - Display list of available commands.
eb!hi Just say hi.
eb!leaders {Contract ID} {sort default=rate} - Display all members of coops order by rate/eggs_laid
eb!mod-set-player-id {@user} {Egg Inc Player ID} - Player ID starts with EI (letter i)
eb!players {columns} - List players with columns requested. Example columns: egg_id, rank, earning_bonus, highest_deflector, eb_player, pe, soul_eggs, prestiges, se_divide_by_prestiges. Sorts by the first column.
eb!players-not-in-coop {Contract ID} - Find players not in contract.
eb!rank Get player stats/rank.
eb!remind {Contract ID} {Hours} {Minutes}
eb!replace {Contract ID} {Coop} {New Coop} - Replace coop name
eb!rocket-tracker - Get current status of rockets. Time left might be off by a couple of minutes because of backup/sync times.
eb!set-player-id {Egg Inc Player ID} - Player ID starts with EI (letter i)
eb!short-status {Contract ID} - Short version of status
```
HELP,
            <<<HELP
```
eb!status {Contract ID} - Display coop info for contract
eb!subscribe-to-rockets - Subscribe to notifications when your rockets come back.
eb!tracker {Contract ID} {Coop ID} - Display boost/token info for coop.
eb!unavailable {Contract ID} - Get users that do not have the contract.
eb!unsubscribe-to-rockets - Unsubscribe to notifications when your rockets come back.
```
HELP,
];
        $actual = $message->message();
        $this->assertEquals($expects, $actual);
    }
}