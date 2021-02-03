<?php

namespace App\Models;

use App\Collections\ContractCollection;
use App\Formatters\Egg;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $casts = [
        'raw_data'   => 'object',
        'expiration' => 'datetime',
    ];

    public static function getAllActiveContracts()
    {
        return self::query()
            ->whereDate('expiration', '>', now())
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

    public function getEggsNeededFormatted(): string
    {
        return resolve(Egg::class)->format($this->getEggsNeeded());
    }

    public function getEggsNeeded(): int
    {
        if (isset($this->raw_data->goalsList)) {
            return end($this->raw_data->goalsList)->targetAmount;
        }

        return end($this->raw_data->rewards)->goal;
    }
}
