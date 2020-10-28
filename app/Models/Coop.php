<?php

namespace App\Models;

use App\Api\EggInc;
use App\Formatters\Egg;
use App\Formatters\TimeLeft;
use Illuminate\Database\Eloquent\Model;

class Coop extends Model
{
    protected $fillable = ['contract', 'coop', 'position'];

    protected static function booted()
    {
        static::creating(function ($coop) {
            $lastCoop = self::query()
                ->guild($coop->guild_id)
                ->contract($coop->contract)
                ->orderBy('position', 'desc')
                ->first()
            ;

            $position = object_get($lastCoop, 'position', 0) + 1;
            $coop->position = $position;
        });
    }

    public function scopeGuild($query, $guildId)
    {
        return $query->where('guild_id', $guildId);
    }

    public function scopeContract($query, $contract)
    {
        return $query->where('contract', $contract);
    }

    public function scopeCoop($query, $coop)
    {
        return $query->where('coop', $coop);
    }

    public function getCoopInfo(): \StdClass
    {
        return resolve(EggInc::class)->getCoopInfo($this->contract, $this->coop);
    }

    public function getCurrentEggs(): int
    {
        return $this->getCoopInfo()->eggs;
    }

    public function getEggsNeeded(): int
    {
        if (!$this->getContractInfo()) {
            return 0;
        }
        return end($this->getContractInfo()->goalsList)->targetAmount;
    }

    public function getProjectedEggs(): int
    {
        return $this->getCurrentEggs() + ($this->getTotalRate() * $this->getTimeLeft());
    }

    public function getProjectedEggsFormatted(): string
    {
        return resolve(Egg::class)->format($this->getProjectedEggs(), 1);
    }

    public function getCurrentEggsFormatted(): string
    {
        return resolve(Egg::class)->format($this->getCurrentEggs(), 1);
    }

    public function getEggsNeededFormatted(): string
    {
        return resolve(Egg::class)->format($this->getEggsNeeded(), 3);
    }

    public function getContractInfo(): ?\StdClass
    {
        return Contract::firstWhere('identifier', $this->contract)->raw_data;
    }

    public function getEggsLeftNeeded(): int
    {
        return $this->getEggsNeeded() - $this->getCurrentEggs();
    }

    public function getEstimateCompletion(): string
    {
        if ($this->getEggsLeftNeeded() < 0) {
            return 'CPLT';
        }
        $seconds = ceil($this->getEggsLeftNeeded() / $this->getTotalRate());

        return resolve(TimeLeft::class)
            ->format($seconds)
        ;
    }

    public function getTotalRate(): int
    {
        return $this->getCoopInfo()->totalRate ?: 1;
    }

    public function getTimeLeft(): int
    {
        return $this->getCoopInfo()->timeLeft;
    }

    public function getMembers(): int
    {
        return count($this->getCoopInfo()->members);
    }

    public function getContractSize(): int
    {
        if (!$this->getContractInfo()) {
            return 0;
        }
        return $this->getContractInfo()->maxCoopSize;
    }

    public function contractModel(): Contract
    {
        return $this->belongsTo(Contract::class, 'contract', 'identifier')->first();
    }
}
