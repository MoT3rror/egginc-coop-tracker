<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateContractsFromOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-from-old-source';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update from old contracts in storage/old_contracts.csv';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csv = fopen(storage_path('old_contracts.csv'), 'r+');
        fgetcsv($csv);
        while (($data = fgetcsv($csv)) !== false) {
            $contract = json_decode($data['24'], false);
            if (!$contract) {
                continue;
            }

            Contract::unguarded(function () use ($contract) {
                Contract::updateOrCreate(
                    ['identifier' => $contract->id],
                    [
                        'name'       => $contract->name,
                        'raw_data'   => $contract,
                        'expiration' => Carbon::createFromTimestamp($contract->expiry_timestamp),
                    ]
                );
            });
        }
    }
}
