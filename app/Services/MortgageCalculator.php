<?php

namespace App\Services;

class MortgageCalculator
{
    /*
     * Calcula a prestação mensal segundo a fórmula de amortização francesa.
     * @param float $loanAmmount Valor total do empréstimo.
     * @param float $annualRate Taxa de juros anual (em percentual, ex:
     * 5 para 5%).
     * @param int $months Número total de meses para pagamento.
     * @return float Valor da prestação mensal.
     * */
    public function calculateMontlyPayment(float $loanAmmount, float $annualRate, int $months): float
    {
        if ($loanAmmount <= 0 || $annualRate < 0 || $months <= 0) {
            throw new \InvalidArgumentException('Parâmetros inválidos para cálculo da prestação mensal.');
        }

        $monthlyRate = $annualRate / 100 / 12;

        if ($monthlyRate == 0.0) {
            return round($loanAmmount / $months, 2);
        }

        // formula da amortização francesa: M = P [ i(1 + i)^n ] / [ (1 + i)^n – 1 ]
        $numerator = $monthlyRate * pow(1 + $monthlyRate, $months);
        $denominator = pow(1 + $monthlyRate, $months) - 1;
        $monthlyPayment = $loanAmmount * ($numerator / $denominator);

        return round($monthlyPayment, 2);
    }

    public function generateAmortizationSchedule(float $loanAmmount, float $annualRate, int $months): array
    {
        $schedule = [];
        $monthlyRate = $annualRate / 100 / 12;

        $monthlyPayment = $this->calculateMontlyPayment($loanAmmount, $annualRate, $months);

        $remainingBalance = $loanAmmount;
        $totalInterest = 0.0;
        for( $month = 1; $month <= $months; $month++ ) {
            $interestPayment = round($remainingBalance * $monthlyRate, 2);
            $principalPayment = round($monthlyPayment - $interestPayment, 2);
            $remainingBalance = round($remainingBalance - $principalPayment, 2);
            $totalInterest += $interestPayment;

            $schedule[] = [
                'month' => $month,
                'principal_payment' => $principalPayment,
                'interest_payment' => $interestPayment,
                'remaining_balance' => max($remainingBalance, 0),
            ];
        }

        return ['monthly_payment' => $monthlyPayment, 'total_interest' => round($totalInterest, 2), 'schedule' => $schedule];
    }
}
