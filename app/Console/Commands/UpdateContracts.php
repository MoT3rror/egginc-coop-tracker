<?php

namespace App\Console\Commands;

use App\Api\EggInc;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update {clientVersion?} {appVersion?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download latest contracts from Egg Inc';

    protected $eggInc;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EggInc $eggInc)
    {
        parent::__construct();
        $this->eggInc = $eggInc;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $contractData = $this->eggInc->getCurrentContracts(
            $this->argument('clientVersion'),
            $this->argument('appVersion'),
        );

        $contracts = $contractData->contracts->contracts;
        
        foreach ($contracts as $contract) {
            Contract::unguarded(function () use ($contract) {
                Contract::updateOrCreate(
                    ['identifier' => $contract->identifier],
                    [
                        'name'       => $contract->name,
                        'raw_data'   => $contract,
                        'expiration' => Carbon::createFromTimestamp($contract->expirationTime),
                    ]
                );
            });
        }

        if ($contractData->contracts->warningMessage) {
            throw new \Exception($contractData->contracts->warningMessage);
        }
    }
}
