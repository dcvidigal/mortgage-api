<?php

namespace Tests\Services;

use App\Services\MortgageCalculator;
use PHPUnit\Framework\TestCase;

class MortgageCalculatorTest extends TestCase
{
    protected MortgageCalculator $mortgageCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mortgageCalculator = new MortgageCalculator;
    }

    /**
     * Testa o cálculo da prestação mensal com parâmetros válidos.
     */
    public function test_calculate_monthly_payment(): void
    {
        $loanAmmount = 200000.00;
        $annualRate = 3.0;
        $months = 360;
        $expectedPayment = 843.21; // Valor esperado calculado previamente
        $calculatedPayment = $this->mortgageCalculator->calculateMontlyPayment($loanAmmount, $annualRate, $months);

        $this->assertEquals($expectedPayment, $calculatedPayment);
    }

    public function test_calculate_monthly_payment_with_zero_interest(): void
    {
        $loanAmmount = 12000.00;
        $annualRate = 0.0;
        $months = 12;

        $expectedPayment = 1000.00; // Valor esperado calculado previamente
        $calculatedPayment = $this->mortgageCalculator->calculateMontlyPayment($loanAmmount, $annualRate, $months);

        $this->assertEquals($expectedPayment, $calculatedPayment);
    }

    public function test_calculate_monthly_payment_with_invalid_parameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mortgageCalculator->calculateMontlyPayment(-10000.00, 5.0, 360);
    }

    /**
     * Testa a geração da tabela de amortização.
     */
    public function test_generate_amortization_schedule(): void
    {
        $loanAmmount = 10000.00;
        $annualRate = 5.0;
        $months = 12;

        $result = $this->mortgageCalculator->generateAmortizationSchedule($loanAmmount, $annualRate, $months);

        $this->assertArrayHasKey('monthly_payment', $result);
        $this->assertArrayHasKey('total_interest', $result);
        $this->assertArrayHasKey('schedule', $result);
        $this->assertCount(12, $result['schedule']);
    }
}
