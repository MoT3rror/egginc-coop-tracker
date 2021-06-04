<?php
namespace App\DiscordMessages;

use kbATeam\MarkdownTable\Column;
use kbATeam\MarkdownTable\Table;
use Illuminate\Support\Arr;

class Players extends Base
{
    protected $middlewares = ['requiresGuild', 'isAdmin'];

    public function message(): array
    {
        $guild = $this->guild;
        $parts = $this->parts;

        $guild->sync();
        $chuckOfUsers = $guild
            ->members()
            ->withEggIncId()
            ->inShowRoles($this->guild)
            ->get()
            ->sortBy(function ($user) use ($parts) {
                switch (Arr::get($parts, 1)) {
                    case 'egg_id':
                        return $user->egg_inc_player_id;
                    case 'highest_deflector':
                        return $user->getHighestDeflectorWithoutPercentAttribute();
                    case 'eb_player':
                        return $user->getEggIncUsernameAttribute();
                    case 'pe':
                        return $user->getEggsOfProphecyAttribute();
                    case 'soul_eggs':
                        return $user->getSoulEggsAttribute();
                    case 'prestiges':
                        return $user->getPrestigesAttribute();
                    case 'rank':
                    case 'earning_bonus':
                    default:
                        return $user->getPlayerEarningBonus();
                }
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
                    $table->addColumn('earning_bonus', new Column('EB', Column::ALIGN_RIGHT));
                    break;
                case 'highest_deflector':
                    $table->addColumn('highest_deflector', new Column('Deflector', Column::ALIGN_LEFT));
                    break;
                case 'eb_player':
                    $table->addColumn('eb_player', new Column('EB Player', Column::ALIGN_LEFT));
                    break;
                case 'pe':
                    $table->addColumn('pe', new Column('PE', Column::ALIGN_LEFT));
                    break;
                case 'soul_eggs':
                    $table->addColumn('soul_eggs', new Column('Soul Eggs', Column::ALIGN_RIGHT));
                    break;
                case 'prestiges':
                    $table->addColumn('prestiges', new Column('Prestiges', Column::ALIGN_RIGHT));
                    break;
            }
        }

        $groupOfMessages = [];
        foreach ($chuckOfUsers as $users) {
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'discord'           => $user->username,
                    'egg_inc'           => $user->egg_inc_player_id,
                    'rank'              => str_replace('farmer', '', $user->getPlayerEggRank()),
                    'earning_bonus'     => $user->getPlayerEarningBonusFormatted(),
                    'highest_deflector' => $user->getHighestDeflectorAttribute(),
                    'eb_player'         => $user->getEggIncUsernameAttribute(),
                    'pe'                => $user->getEggsOfProphecyAttribute(),
                    'soul_eggs'         => $user->getSoulEggsFormattedAttribute(),
                    'prestiges'         => $user->getPrestigesAttribute(),
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

    public function help(): string
    {
        return '{columns} - List players with columns requested. Example columns: egg_id, rank, earning_bonus, highest_deflector, eb_player, pe, soul_eggs, prestiges';
    }
}
