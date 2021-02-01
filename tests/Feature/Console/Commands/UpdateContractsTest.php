<?php

namespace Tests\Feature\Console\Commands;

use App\Api\EggInc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateContractsTest extends TestCase
{
    use RefreshDatabase;

    public function testHandle()
    {
        $this->instance(EggInc::class, Mockery::mock(EggInc::class, function ($mock) {
            $contracts = [json_decode(file_get_contents(base_path('tests/files/ion-production-2021.json')))];

            $mock
                ->shouldReceive('getCurrentContracts')
                ->once()
                ->andReturn($contracts)
            ;
        }));

        $this
            ->artisan('contracts:update')
        ;

        $this->assertDatabaseHas('contracts', ['identifier' => 'ion-production-2021']);
    }
}
