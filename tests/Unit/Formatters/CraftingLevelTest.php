<?php

namespace Tests\Unit\Formatters;

use App\Formatters\CraftingLevel;
use Tests\TestCase;

class CraftingLevelTest extends TestCase
{
    public function formattingData()
    {
        return [
            [
                30,
                1000000000000000000,
            ],
            [
                3,
                5000,
            ],
            [
                20,
                553418838,
            ],
        ];
    }

    /**
     * @dataProvider formattingData
     */
    public function testXpToLevel(int $expect, int $number)
    {
        $egg = new CraftingLevel;
        $this->assertEquals($expect, $egg->xpToLevel($number));
    }
}
