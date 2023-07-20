<?php

namespace App\Models;

use App\Collections\ContractCollection;
use App\Formatters\Egg;
use Illuminate\Database\Eloquent\Collection;

class Contract extends Model
{
    protected $casts = [
        'raw_data'   => 'object',
        'expiration' => 'datetime',
    ];

    public static function getAllActiveContracts($additionalDays = 0)
    {
        return self::query()
            ->whereDate('expiration', '>', now()->subDays($additionalDays))
            ->orderBy('updated_at', 'DESC')
            ->get()
        ;
    }

    public function newCollection(array $models = []): Collection
    {
        return new ContractCollection($models);
    }

    public function getMaxCoopSize()
    {
        return $this->raw_data->maxCoopSize;
    }

    public function getEggsNeededFormatted(string $grade): string
    {
        return resolve(Egg::class)->format($this->getEggsNeeded($grade));
    }

    public function getEggsNeeded(string $grade): int
    {
        $goals = collect($this->raw_data->gradeSpecs)
            ->where('grade', $grade)
            ->first()
        ;
        
        return end($goals->goals)->targetAmount;
    }
}
