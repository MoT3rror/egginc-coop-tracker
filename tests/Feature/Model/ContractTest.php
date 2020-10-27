<?php

namespace Tests\Feature\Models;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    public function testGetActiveContracts()
    {
        $contract = factory(Contract::class)
            ->create(['expiration' => now()->addDays(7)])
        ;

        $contracts = Contract::getAllActiveContracts();

        $this->assertEquals($contract->id, $contracts->find($contract)->id);

        $contract->expiration = now()->addDays(-7);
        $contract->save();

        $contracts = Contract::getAllActiveContracts();

        $this->assertNull($contracts->find($contract));
    }

    public function testGetContractsRawFormat()
    {
        factory(Contract::class)
            ->create(['expiration' => now()->addDays(7)])
        ;

        $contracts = Contract::getAllActiveContracts();

        $this->assertEquals([json_decode(file_get_contents(base_path('tests/files/halloween-2020.json')))], $contracts->getInRawFormat());
    }
}
