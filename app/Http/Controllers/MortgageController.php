<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use App\Services\MortgageCalculator;
use Illuminate\Http\Request;

class MortgageController extends Controller
{
    protected MortgageCalculator $mortgageCalculator;

    public function __construct()
    {
        $this->mortgageCalculator = new MortgageCalculator();
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

        if (empty($validated['duration_years']) && empty($validated['duration_months'])) {
            throw ValidationException::withMessages([
                'duration' => 'Deve indicar a duração do empréstimo em anos e/ou meses.',
            ]);
        }

        return response(json_encode($this->performCalculation($validated)), 200, ['Content-Type' => 'application/json']);
    }

    public function amortizationSchedule(Request $request)
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

        if (empty($validated['duration_years']) && empty($validated['duration_months'])) {
            throw ValidationException::withMessages([
                'duration' => 'Deve indicar a duração do empréstimo em anos e/ou meses.',
            ]);
        }

        $months = $validated['duration_months'] ?? $validated['duration_years'] * 12;

        $annualRate = $validated['type'] == 'variable'
            ? $validated['index_rate'] + $validated['spread']
            : $validated['rate'];

        $scheduleData = $this->mortgageCalculator->generateAmortizationSchedule(
            $validated['loan_amount'],
            $annualRate,
            $months
        );

        return response()->json([
            'monthlyPayment' => $scheduleData['monthly_payment'],
            'loan_amount' => (float)$validated['loan_amount'],
            'duration_months' => $months,
            'annual_rate' => (float)$annualRate,
            'method' => 'french_amortization',
            "currency" => "EUR",
            "metadata" => [
                "calculated_at" => now()->toIso8601String(),
                "formula" => "M = P [ i(1 + i)^n ] / [ (1 + i)^n – 1"
            ],
            'total_interest' => $scheduleData['total_interest'],
            'schedule' => $scheduleData['schedule']
        ]);
    }

    public function calculateWithSpread(Request $request)
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

        if (empty($validated['duration_years']) && empty($validated['duration_months'])) {
            throw ValidationException::withMessages([
                'duration' => 'Deve indicar a duração do empréstimo em anos e/ou meses.',
            ]);
        }

        $result = $this->performCalculation($validated);
        $result['index_rate'] = $validated['type'] == 'variable' ? (float)$validated['index_rate'] : null;
        $result['spread'] = $validated['type'] == 'variable' ? (float)$validated['spread'] : null;
        return response()->json($result);
    }

    public function export(Request $request)
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

        if (empty($validated['duration_years']) && empty($validated['duration_months'])) {
            throw ValidationException::withMessages([
                'duration' => 'Deve indicar a duração do empréstimo em anos e/ou meses.',
            ]);
        }

        $result = $this->performCalculation($validated);
        $scheduleData = $this->mortgageCalculator->generateAmortizationSchedule(
            $validated['loan_amount'],
            $validated['type'] == 'variable'
                ? $validated['index_rate'] + $validated['spread']
                : $validated['rate'],
            $validated['duration_months'] ?? $validated['duration_years'] * 12
        );

        $result = array_merge($result, $scheduleData);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="amortization_schedule.csv"',
        ];
        return response()->stream(function () use ($result) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Mês', 'Pagamento Principal', 'Pagamento de Juros', 'Saldo Remanescente', 'Valor do Empréstimo', 'Taxa Anual', 'Taxa de Índice', 'Spread']);

            $schedules = $result['schedule'] ?? [];
            foreach ($schedules as $schedule) {

                fputcsv($handle, [
                    $schedule['month'],
                    $schedule['principal_payment'],
                    $schedule['interest_payment'],
                    $schedule['remaining_balance'],
                    $result['loan_amount'],
                    $result['annual_rate'],
                    $result['index_rate'] ?? '',
                    $result['spread'] ?? ''
                ]);

            }
            fclose($handle);
        }, 200, $headers);


    }

    private
    function performCalculation(array $validated): array
    {
        $months = $validated['duration_months'] ?? $validated['duration_years'] * 12;

        $annualRate = $validated['type'] == 'variable'
            ? $validated['index_rate'] + $validated['spread']
            : $validated['rate'];

        $monthlyPayment = $this->mortgageCalculator->calculateMontlyPayment(
            $validated['loan_amount'],
            $annualRate,
            $months
        );

        return [
            'monthlyPayment' => $monthlyPayment,
            'loan_amount' => (float)$validated['loan_amount'],
            'duration_months' => $months,
            'annual_rate' => (float)$annualRate,
            'method' => 'french_amortization',
            "currency" => "EUR",
            "metadata" => [
                "calculated_at" => now()->toIso8601String(),
                "formula" => "M = P [ i(1 + i)^n ] / [ (1 + i)^n – 1"
            ],
        ];
    }
}
