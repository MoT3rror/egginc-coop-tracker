<?php

namespace Tests\Unit\Formatters;

use App\Formatters\Egg;
use Tests\TestCase;

class EggTest extends TestCase
{
    public function formattingData()
    {
        return [
            [
                '1.0Q',
                '1,000,000,000,000,000,000',
            ],
            [
                '523q',
                '523,450,000,000,000,000',
            ],
            [
                '1.2Q',
                '1,200,000,000,000,000,000',
            ],
            [
                '150K',
                '150,000',
            ],
            [
                '150',
                '150.000002',
            ]
        ];
    }

    /**
     * @dataProvider formattingData
     */
    public function testFormatting(string $expect, string $number)
    {
        $egg = new Egg;
        $number = (int) str_replace(',', '', $number);
        $this->assertEquals($expect, $egg->format($number));
    }
}
