<?php

namespace App\Services;

class MortageCalculator
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
}
