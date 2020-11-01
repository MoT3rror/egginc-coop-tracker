<?php
namespace App\Formatters;

use Litipk\BigNumbers\Decimal;

class Egg
{
    public $magnitudes;

    public function __construct()
    {
        $this->magnitudes = json_decode(file_get_contents(base_path('resources/js/magnitudeFormat.json')));
    }

    public function format($eggs): string
    {
        $last = null;
        $eggsInBig = Decimal::create($eggs); 
        foreach ($this->magnitudes as $magnitude) {
            if ($eggsInBig->div(Decimal::create(pow(10, $magnitude->magnitude)))->isLessThan(Decimal::create(1))) {
                break;
            }
            $last = $magnitude;
        }

        if (!$last) {
            return $eggs;
        }

        $decimals = 0;
        if ($last->symbol == 'Q') {
            $decimals = 1;
        }

        return $eggsInBig->div(Decimal::create(pow(10, $last->magnitude)))->floor($decimals) . $last->symbol;
    }
}
