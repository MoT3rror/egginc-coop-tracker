<?php
namespace App\DiscordMessages;

use App\Formatters\Egg;
use App\Models\User;
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
            ->sortBy(function (User $user) use ($parts) {
                switch (Arr::get($parts, 1)) {
                    case 'egg_id':
                        return $user->egg_inc_player_id;
                    case 'highest_deflector':
                        return $user->getHighestDeflectorWithoutPercentAttribute();
                    case 'eb_player':
                        return strtolower($user->getEggIncUsernameAttribute());
                    case 'pe':
                        return $user->getEggsOfProphecyAttribute();
                    case 'soul_eggs':
                        return $user->getSoulEggsAttribute();
                    case 'prestiges':
                        return $user->getPrestigesAttribute();
                    case 'se_divide_by_prestiges':
                        return $user->getSoulEggsAttribute() / $user->getPrestigesAttribute();
                    case 'legendary_artifacts':
                        return $user->getLegendaryArtifactsCount();
                    case 'backup_time':
                        return $user->getBackupTime();
                    case 'crafting_xp':
                    case 'crafting_level':
                        return $user->getCraftingXpAttribute();
                    case 'rank':
                    case 'earning_bonus':
                    default:
                        return $user->getPlayerEarningBonus();
                }
            }, SORT_REGULAR, Arr::get($parts, 1) !== 'eb_player')
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
                case 'se_divide_by_prestiges':
                    $table->addColumn('se_divide_by_prestiges', new Column('SE/Prestiges', Column::ALIGN_RIGHT));
                    break;
                case 'legendary_artifacts':
                    $table->addColumn('legendary_artifacts', new Column('LA', Column::ALIGN_RIGHT));
                    break;
                case 'backup_time':
                    $table->addColumn('backup_time', new Column('Backup', Column::ALIGN_LEFT));
                    break;
                case 'crafting_xp':
                    $table->addColumn('crafting_xp', new Column('Crafting XP', Column::ALIGN_RIGHT));
                    break;
                case 'crafting_level':
                    $table->addColumn('crafting_level', new Column('Crafting Level', Column::ALIGN_RIGHT));
                    break;
            }
        }

        if (count($chuckOfUsers) == 0) {
            return ['This guild has no players.'];
        }

        $groupOfMessages = [];
        foreach ($chuckOfUsers as $users) {
            $data = [];
            foreach ($users as $user) {
                if (!$user->getEggPlayerInfo()) {
                    continue;
                }

                $seDivideByPrestiges = resolve(Egg::class)->format($user->getSoulEggsAttribute() / $user->getPrestigesAttribute(), 3);
                $data[] = [
                    'discord'                => $user->username,
                    'egg_inc'                => strtoupper($user->egg_inc_player_id),
                    'rank'                   => str_replace('farmer', '', $user->getPlayerEggRank()),
                    'earning_bonus'          => $user->getPlayerEarningBonusFormatted(),
                    'highest_deflector'      => $user->getHighestDeflectorAttribute(),
                    'eb_player'              => $user->getEggIncUsernameAttribute(),
                    'pe'                     => $user->getEggsOfProphecyAttribute(),
                    'soul_eggs'              => $user->getSoulEggsFormattedAttribute(),
                    'prestiges'              => $user->getPrestigesAttribute(),
                    'se_divide_by_prestiges' => $seDivideByPrestiges,
                    'legendary_artifacts'    => $user->getLegendaryArtifactsCount(),
                    'backup_time'            => $user->getBackupTimeFormatted(),
                    'crafting_xp'            => $user->getCraftingXpAttribute(),
                    'crafting_level'         => $user->getCraftingLevelAttribute(),
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
        return '{columns} - List players with columns requested. Example columns: egg_id, rank, earning_bonus, highest_deflector, eb_player, pe, soul_eggs, prestiges, se_divide_by_prestiges. Sorts by the first column.';
    }
}
