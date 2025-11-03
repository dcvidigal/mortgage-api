<?php

namespace App\Http\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Simulador de Crédito Habitação API",
 *     version="1.0.0",
 *     description="API para cálculo de prestações, taxas variáveis e exportação de simulações de crédito."
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8080",
 *     description="Servidor local de desenvolvimento (Laravel Sail)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * -------------
 * ENDPOINTS
 * -------------
 */

/**
 * @OA\Post(
 *     path="/api/mortgage/calculate",
 *     tags={"Mortgage"},
 *     summary="Calcula a prestação mensal",
 *     description="Calcula o valor da prestação mensal com base no tipo de taxa (fixa ou variável) e prazo.",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"loan_amount","type"},
 *             @OA\Property(property="loan_amount", type="number", example=150000, minimum=0.01, description="Montante do empréstimo (€)"),
 *             @OA\Property(property="duration_years", type="integer", nullable=true, minimum=1, example=15, description="Prazo do empréstimo em anos"),
 *             @OA\Property(property="duration_months", type="integer", nullable=true, minimum=0, example=180, description="Prazo do empréstimo em meses"),
 *             @OA\Property(property="type", type="string", enum={"fixed","variable"}, example="fixed", description="Tipo de taxa — fixa ou variável"),
 *             @OA\Property(property="rate", type="number", nullable=true, example=3.5, description="TAN fixa (%). Obrigatório se type=fixed"),
 *             @OA\Property(property="index_rate", type="number", nullable=true, example=2.0, description="Euribor (%). Obrigatório se type=variable"),
 *             @OA\Property(property="spread", type="number", nullable=true, example=1.5, description="Spread do banco (%). Obrigatório se type=variable")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cálculo efetuado com sucesso",
 *         @OA\JsonContent(
 *             @OA\Property(property="monthlyPayment", type="number", example=1072.32),
 *             @OA\Property(property="loan_amount", type="number", example=150000),
 *             @OA\Property(property="duration_months", type="integer", example=180),
 *             @OA\Property(property="annual_rate", type="number", example=3.5),
 *             @OA\Property(property="method", type="string", example="french_amortization"),
 *             @OA\Property(property="currency", type="string", example="EUR")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Erro de validação dos parâmetros")
 * )
 *
 * @OA\Post(
 *     path="/api/mortgage/amortization-schedule",
 *     tags={"Mortgage"},
 *     summary="Gera o plano de amortização completo",
 *     description="Lista mês a mês com juros, capital amortizado e saldo restante.",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"loan_amount","type"},
 *             @OA\Property(property="loan_amount", type="number", example=150000),
 *             @OA\Property(property="duration_years", type="integer", nullable=true, example=15),
 *             @OA\Property(property="duration_months", type="integer", nullable=true, example=180),
 *             @OA\Property(property="type", type="string", enum={"fixed","variable"}, example="fixed"),
 *             @OA\Property(property="rate", type="number", nullable=true, example=3.5),
 *             @OA\Property(property="index_rate", type="number", nullable=true, example=2.0),
 *             @OA\Property(property="spread", type="number", nullable=true, example=1.5)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Plano de amortização gerado com sucesso",
 *         @OA\JsonContent(
 *             @OA\Property(property="loan_amount", type="number", example=150000),
 *             @OA\Property(property="duration_months", type="integer", example=180),
 *             @OA\Property(
 *                 property="schedule",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="month", type="integer", example=1),
 *                     @OA\Property(property="interest", type="number", example=350.00),
 *                     @OA\Property(property="principal", type="number", example=722.32),
 *                     @OA\Property(property="remaining", type="number", example=149277.68)
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/mortgage/calculate-spread",
 *     tags={"Mortgage"},
 *     summary="Calcula prestação com taxa variável (Euribor + Spread)",
 *     description="Calcula a prestação mensal considerando index_rate + spread como TAN efetiva.",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"loan_amount","type","index_rate","spread"},
 *             @OA\Property(property="loan_amount", type="number", example=150000),
 *             @OA\Property(property="duration_years", type="integer", nullable=true, example=15),
 *             @OA\Property(property="duration_months", type="integer", nullable=true, example=180),
 *             @OA\Property(property="type", type="string", enum={"variable"}, example="variable"),
 *             @OA\Property(property="index_rate", type="number", example=2.0, description="Euribor (%)"),
 *             @OA\Property(property="spread", type="number", example=1.5, description="Spread (%)")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cálculo efetuado com sucesso",
 *         @OA\JsonContent(
 *             @OA\Property(property="monthlyPayment", type="number", example=1071.97),
 *             @OA\Property(property="annual_rate", type="number", example=3.5),
 *             @OA\Property(property="currency", type="string", example="EUR")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/mortgage/export",
 *     tags={"Mortgage"},
 *     summary="Exporta simulação em CSV",
 *     description="Exporta o plano de amortização em formato CSV para download.",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"loan_amount","type"},
 *             @OA\Property(property="loan_amount", type="number", example=100000),
 *             @OA\Property(property="duration_years", type="integer", nullable=true, example=10),
 *             @OA\Property(property="duration_months", type="integer", nullable=true, example=120),
 *             @OA\Property(property="type", type="string", enum={"fixed","variable"}, example="fixed"),
 *             @OA\Property(property="rate", type="number", nullable=true, example=4.0),
 *             @OA\Property(property="index_rate", type="number", nullable=true, example=2.0),
 *             @OA\Property(property="spread", type="number", nullable=true, example=1.5)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Ficheiro CSV gerado com sucesso",
 *         @OA\MediaType(
 *             mediaType="text/csv",
 *             @OA\Schema(type="string", format="binary")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Erro de validação dos parâmetros")
 * )
 */
class MortgageApiDocs {}
