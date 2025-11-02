<?php

namespace Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MortageCalculationTest extends TestCase
{
    /**
     * Testa amortização de taxa fixa
     */
    public function test_fixed_rate_mortage_calculation(): void{
        $response = $this->postJson('/api/mortage/calculate', [
            'loan_amount' => 200000,
            'duration_years' => 30,
            'rate' => 3.0,
            'type' => 'fixed',
        ]);
        $response
            ->assertStatus(200)
            ->assertJson([
                'monthlyPayment' => 843.21,
                'loan_amount' => 200000.0,
                'duration_months' => 360,
                'annual_rate' => 3.0,
                'method' => 'french_amortization',
                "currency" => "EUR",
            ]);
    }

    /**
     * Testa amortização de taxa variável
     */
    public function test_variable_rate_mortage_calculation(): void
    {
        $response = $this->postJson('/api/mortage/calculate', [
            'loan_amount' => 150000,
            'duration_years' => 15,
            'index_rate' => 2.0,
            'spread' => 1.5,
            'type' => 'variable',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'monthlyPayment' => 1072.32,
                'loan_amount' => 150000.0,
                'duration_months' => 180,
                'annual_rate' => 3.5,
                'method' => 'french_amortization',
                "currency" => "EUR",
            ]);
    }

    /**
     * Testa validação de parâmetros inválidos
     */
    public function test_mortage_calculation_with_invalid_parameters(): void
    {
        $response = $this->postJson('/api/mortage/calculate', [
            'loan_amount' => -50000,
            'duration_years' => 10,
            'rate' => 4.0,
            'type' => 'fixed',
        ]);
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['loan_amount']);
    }

    /*
     * Testa validação quando a duração não é fornecida
     * */
    public function test_mortage_calculation_without_duration(): void
    {
        $response = $this->postJson('/api/mortage/calculate', [
            'loan_amount' => 100000,
            'type' => 'fixed',
            'rate' => 5.0,
        ]);
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration']);
    }

    /**
     * Testa cálculo com duração em meses
     * */
    public function test_mortage_calculation_with_duration_in_months(): void
    {
        $response = $this->postJson('/api/mortage/calculate', [
            'loan_amount' => 50000,
            'duration_months' => 24,
            'rate' => 4.0,
            'type' => 'fixed',
        ]);
        $response
            ->assertStatus(200)
            ->assertJson([
                'monthlyPayment' => 2171.25,
                'loan_amount' => 50000,
                'duration_months' => 24,
                'annual_rate' => 4,
                'method' => 'french_amortization',
                "currency" => "EUR",
            ]);
    }

    /*
     * Testa campos obrigatórios
     * */
    public function test_mortage_calculation_required_fields(): void
    {
        $response = $this->postJson('/api/mortage/calculate', []);
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['loan_amount', 'type']);

    }
}
