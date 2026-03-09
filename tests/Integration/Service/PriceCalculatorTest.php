<?php

namespace App\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use App\Service\PriceCalculator;

class PriceCalculatorTest extends TestCase
{
    public function testCalculate(): void
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculate(100, 0.2);

        $this->assertEquals(120, $result);
    }
}
