<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use PHPUnit\Framework\TestCase;

class CalculationServiceTest extends TestCase
{
    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CalculationService();
    }

    /**
     * Test Basic Tax Calculation (e.g. PPN 11%)
     */
    public function test_calculate_ppn_standard()
    {
        $amount = 1000000; // 1 Juta
        $rate = 11; // 11%
        $type = 'PPN';

        $result = $this->calculator->calculateTax($amount, $rate, $type);

        // Expect: 110,000
        $this->assertEquals(110000, $result['tax_amount']);
        $this->assertEquals(11, $result['tax_rate']);
    }

    /**
     * Test Withholding Tax (PPh 23 - 2%)
     */
    public function test_calculate_pph_standard()
    {
        $amount = 5000000; 
        $rate = 2; // 2%
        $type = 'PPh';

        $result = $this->calculator->calculateTax($amount, $rate, $type);

        // Expect: 100,000
        $this->assertEquals(100000, $result['tax_amount']);
    }

    /**
     * Test Zero Rate
     */
    public function test_calculate_zero_tax()
    {
        $amount = 100000;
        $rate = 0;
        $type = 'PPN';

        $result = $this->calculator->calculateTax($amount, $rate, $type);

        $this->assertEquals(0, $result['tax_amount']);
    }

    /**
     * Test Gross Up Calculation
     * Formula: Gross = Net / (1 - Rate)
     * Case: Ingin Net 10.000.000, PPh 21 (5%) ditanggung perusahaan (Gross Up)
     */
    public function test_gross_up_calculation()
    {
        $netAmount = 10000000;
        $rate = 5; // 5%

        // 10jt / (1 - 0.05) = 10jt / 0.95 = 10,526,315.78... -> Ceil: 10,526,316
        $grossAmount = $this->calculator->calculateGrossUp($netAmount, $rate);

        $this->assertEquals(10526316, $grossAmount);
    }
}
