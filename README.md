# Mortage API

Aplicação Laravel desenvolvida como caso técnico, que implementa uma **API de Cálculo de Empréstimos (Mortgage Calculator)**, com suporte a **taxas fixas e variáveis**, **geração de tabela de amortização**, **exportação de resultados**, **documentação automática (Swagger)** e **autenticação por token Bearer**.

---

## Estrutura e Conceito

A API foi construída em **Laravel 12**, utilizando **Laravel Sail (Docker)** para gestão de ambiente, e organizada segundo boas práticas de modularização — separando **Controllers**, **Services**, **Requests** e **Middleware**.

Os principais endpoints disponibilizam simulações de crédito com base em parâmetros financeiros como montante, prazo, taxa fixa ou variável, e permitem exportar a simulação em CSV.

---

## Endpoints Principais

| Endpoint | Método | Descrição |
|-----------|---------|-----------|
| `/api/mortage/calculate` | POST | Calcula a prestação mensal (taxa fixa ou variável) |
| `/api/mortage/amortization-schedule` | POST | Gera a tabela de amortização (juros, capital e saldo) |
| `/api/mortage/calculate-spread` | POST | Calcula a TAN (index rate + spread) e a prestação variável |
| `/api/mortage/export` | POST | Exporta os resultados em formato CSV |

---

## Estrutura de Validação

Todos os endpoints partilham a mesma estrutura base de validação:

| Campo | Tipo | Obrigatório | Descrição |
|--------|------|-------------|------------|
| **loan_amount** | number | ✅ | Montante do empréstimo em euros |
| **duration_years** | integer | ⛔️ | Prazo em anos (ou `duration_months`) |
| **duration_months** | integer | ⛔️ | Prazo em meses (ou `duration_years`) |
| **type** | string | ✅ | Tipo de taxa: `"fixed"` ou `"variable"` |
| **rate** | number | ⚠️ | TAN fixa em % (obrigatória se `type=fixed`) |
| **index_rate** | number | ⚠️ | Indexante (ex: Euribor) — obrigatório se `type=variable` |
| **spread** | number | ⚠️ | Spread do banco em % — obrigatório se `type=variable` |

---

## Exemplo de Requisição

### Taxa Fixa
```json
POST /api/mortage/calculate
{
  "loan_amount": 150000,
  "duration_years": 15,
  "rate": 3.5,
  "type": "fixed"
}
```

**Resposta:**
```json
{
  "monthlyPayment": 1072.32,
  "loan_amount": 150000,
  "duration_months": 180,
  "annual_rate": 3.5,
  "method": "french_amortization",
  "currency": "EUR",
  "metadata": {
    "calculated_at": "2025-11-02T22:00:00Z",
    "formula": "M = P [ i(1 + i)^n ] / [ (1 + i)^n – 1 ]"
  }
}
```

### Taxa Variável
```json
POST /api/mortage/calculate
{
  "loan_amount": 150000,
  "duration_years": 15,
  "index_rate": 2.0,
  "spread": 1.5,
  "type": "variable"
}
```

---

## Instalação e Execução

1. Clonar o repositório:
   ```bash
   git clone https://github.com/dcvidigal/mortage-api.git
   cd mortage-api
   ```

2. Instalar dependências:
   ```bash
   composer install
   ```

3. Copiar o ficheiro `.env.example`:
   ```bash
   cp .env.example .env
   ```

4. Gerar a chave da aplicação:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

5. Subir o ambiente com Docker (Laravel Sail):
   ```bash
   ./vendor/bin/sail up -d
   ```

6. Executar migrações:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

---

## Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── MortageController.php
│   ├── Middleware/
│   │   └── ApiTokenAuth.php
│   └── Requests/
│       └── ...
├── Services/
│   └── MortgageCalculator.php
└── Swagger/
    └── MortageApiDocs.php

routes/
└── api.php

tests/
├── Feature/
│   └── MortageCalculationTest.php
└── Unit/
    └── MortgageCalculatorTest.php
```

---

## Documentação (Swagger)

A documentação automática é gerada com **L5-Swagger**.

### Endereço local
```
http://localhost/api/documentation
```

### Geração manual
```bash
./vendor/bin/sail artisan l5-swagger:generate
```

O ficheiro gerado fica em:
```
storage/api-docs/api-docs.json
```

---

## Autenticação

A API implementa autenticação simples via **Bearer Token**.

Header obrigatório:
```
Authorization: Bearer <API_TOKEN>
```

Definido em `.env`:
```env
API_TOKEN=meu_token_seguro
```

---

## Testes Automatizados

O projeto inclui testes **unitários** e **de integração** com PHPUnit.

Executar:
```bash
./vendor/bin/sail artisan test
```

ou
```bash
vendor/bin/phpunit
```

### Testes implementados:
| Categoria | Ficheiro | Cobertura |
|------------|-----------|-----------|
| Cálculo de empréstimo fixo | `Feature/MortageCalculationTest.php` | ✅ |
| Cálculo de empréstimo variável | `Feature/MortageCalculationTest.php` | ✅ |
| Geração de tabela de amortização | `Feature/MortageCalculationTest.php` | ✅ |
| Exportação CSV | `Feature/MortageCalculationTest.php` | ✅ |
| Teste unitário do serviço de cálculo | `Unit/MortgageCalculatorTest.php` | ✅ |

---

## Integração Contínua (GitHub Actions)

O projeto inclui pipeline de **CI/CD** configurado para:
- Análise estática com **PHPStan**
- Verificação de estilo com **PHP-CS-Fixer**
- Execução de testes unitários e de integração

Exemplo (`.github/workflows/ci.yml`):

---

## Decisões Técnicas

- **Service Layer dedicada**: toda a lógica financeira centralizada em `MortgageCalculator`.
- **Validação robusta** via `Request::validate()`, garantindo coerência entre endpoints.
- **Autenticação leve**: middleware `ApiTokenAuth` com exceção automática para ambiente de teste.
- **Arquitetura extensível**: endpoints e lógica facilmente adaptáveis a novos produtos financeiros.
- **Documentação desacoplada**: Swagger definido em `app/Swagger/MortageApiDocs.php`, evitando anotações em cada controlador.

---

## Limitações Conhecidas

| Categoria | Descrição |
|------------|------------|
| **Precisão decimal** | O arredondamento de prestações pode variar ligeiramente conforme a fórmula e arredondamento aplicado. |
| **Falta de persistência** | Os cálculos não são guardados em base de dados, apenas processados em runtime. |
| **Autenticação simplificada** | Token estático definido em `.env`, sem sistema de utilizadores. |
| **Swagger estático** | É necessário regenerar manualmente após alterações nos endpoints (`artisan l5-swagger:generate`). |

---

## Bónus Implementados

| Bónus | Descrição |
|--------|------------|
| **Tabela de Amortização** | Geração completa mês a mês (juros, capital, saldo). |
| **Taxas Variáveis** | Suporte a cálculos TAN = Euribor + Spread. |
| **Exportação CSV** | Endpoint para exportar resultados simulados. |
| **Swagger / OpenAPI** | Documentação automática com L5-Swagger. |
| **Autenticação Bearer** | Proteção de endpoints via token configurável. |
| **CI (GitHub Actions)** | Testes e validações automáticas a cada push. |

---

## Resumo

Este projeto foi desenvolvido para demonstrar competências em arquitetura de APIs modernas com **Laravel**, incluindo:
- Estrutura limpa e modular
- Testes automatizados
- Documentação integrada
- Pipeline de CI funcional

Focado em clareza, escalabilidade e boas práticas de engenharia.

---

**Autor:** Diogo Vidigal  
**Propósito:** Caso Técnico Laravel – Mortgage Calculator  
**Licença:** MIT
