<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use App\Services\MortageCalculator;
use Illuminate\Http\Request;

class MortageController extends Controller
{
    protected MortageCalculator $mortageCalculator;
    public function __construct()
    {
        $this->mortageCalculator = new MortageCalculator();
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'loan_amount' => 'required|numeric|min:0.01',
            'duration_years' => 'nullable|integer|min:1',
            'duration_months' => 'nullable|integer|min:0',
            'type' => 'required|in:fixed,variable',
            'rate' => 'required_if:type,fixed|numeric|min:0',
            'index_rate' => 'required_if:type,variable|numeric|min:0',
            'spread' => 'required_if:type,variable|numeric|min:0',
        ]);

        if(empty($validated['duration_years']) && empty($validated['duration_months'])) {
            throw ValidationException::withMessages([
                'duration' => 'Deve indicar a duração do empréstimo em anos e/ou meses.',
            ]);
        }

        $months = $validated['duration_months'] ?? $validated['duration_years'] * 12;

        $annualRate = $validated['type'] == 'variable'
            ? $validated['index_rate'] + $validated['spread']
            : $validated['rate'];

        $monthlyPayment = $this->mortageCalculator->calculateMontlyPayment(
            $validated['loan_amount'],
            $annualRate,
            $months
        );

        return response()->json([
            'monthlyPayment' => $monthlyPayment,
            'loan_amount' => (float) $validated['loan_amount'],
            'duration_months' => $months,
            'annual_rate' => (float) $annualRate,
            'method' => 'french_amortization',
            "currency" => "EUR",
            "metadata" => [
                "calculated_at" => now()->toIso8601String(),
                "formula" => "M = P [ i(1 + i)^n ] / [ (1 + i)^n – 1"
            ],
        ]);
    }
}
