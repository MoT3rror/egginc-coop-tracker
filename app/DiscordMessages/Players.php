<?php
namespace App\DiscordMessages;

use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;

class Players extends Base
{
    protected $middlewares = ['requiresGuild'];

    public function message(): array
    {
        $guild = $this->guild;
        $parts = $this->parts;

        $guild->sync();
        $chuckOfUsers = $guild
            ->members()
            ->withEggIncId()
            ->inShowRoles()
            ->get()
            ->sortBy(function ($user) {
                return $user->getPlayerEarningBonus();
            }, SORT_REGULAR, true)
            ->chunk(10)
        ;

        $table = new Table();
        $table->addColumn('discord', new Column('Discord', Column::ALIGN_LEFT));

        foreach ($parts as $part) {
            switch ($part) {
                case 'egg_id':
                    $table->addColumn('egg_inc', new Column('Egg Inc ID', Column::ALIGN_LEFT));
                    break;
                case 'rank':
                    $table->addColumn('rank', new Column('Rank', Column::ALIGN_LEFT));
                    break;
                case 'earning_bonus': 
                    $table->addColumn('earning_bonus', new Column('EB', Column::ALIGN_LEFT));
                    break;
            }
        }

        $groupOfMessages = [];
        foreach ($chuckOfUsers as $users) {
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'discord'       => $user->username,
                    'egg_inc'       => $user->egg_inc_player_id,
                    'rank'          => str_replace('farmer', '', $user->getPlayerEggRank()),
                    'earning_bonus' => $user->getPlayerEarningBonusFormatted(),
                ];
            }

            $messages = [];
            $messages[] = '```';
            foreach ($table->generate($data) as $row) {
                $messages[] = $row;
            }
            $messages[] = '```';

            $groupOfMessages[] = implode("\n", $messages);
        }
        return $groupOfMessages;
    }
}
