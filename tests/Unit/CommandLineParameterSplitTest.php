<?php
namespace Tests\Unit;

use Tests\TestCase;

class CommandLineParameterSplitTest extends TestCase
{
    public function splitTests()
    {
        return [
            [
                'command a b',
                ['command', 'a', 'b'],
            ],
            [
                'cmd test' . PHP_EOL . 'test2',
                ['cmd', 'test', 'test2'],
            ],
            [
                'cmd test' . PHP_EOL . ' test2',
                ['cmd', 'test', 'test2'],
            ]
        ];
    }

    /**
     * @dataProvider splitTests
     */
    public function testSplitParameters($input, $expected)
    {
        // $actual = explode(' ', $input); // split on space
        // $actual = preg_split('/\r\n|\r|\n| /', $input); // split on space or new lines
        $actual = preg_split('/\r\n|\r|\n| /', $input, -1, PREG_SPLIT_NO_EMPTY); // split on space or new lines

        $this->assertEquals($expected, $actual);
    }
}
