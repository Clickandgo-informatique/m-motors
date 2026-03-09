<?php

namespace App\Service;

class PriceCalculator
{
    public function calculate(float $price, float $tax): float
    {
        return $price * (1 + $tax);
    }
}
