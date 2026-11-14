<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\HR\EmployeeOffboarding;
use Carbon\Carbon;

class EndOfServiceCalculator
{
    /**
     * Calculate Saudi Labor Law End of Service Benefit (EOSB) and final settlement.
     *
     * Rules (Saudi Labor Law Articles 80, 81, 84, 85, 87, 107):
     * - Calculation wage base: basic salary + housing allowance (or allowances_amount when housing_allowance_amount is not specified).
     * - Full EOSB (Articles 84, 81, 87):
     *   - Half month's wage for each of the first 5 years.
     *   - One full month's wage for each subsequent year (pro-rated for fractions of a year).
     * - Multiplier by termination type:
     *   - employer_termination / contract_expiry / article_81 / article_87 (or is_article_87=true): 100% (1.0)
     *   - article_80: 0% (0.0)
     *   - resignation (Article 85, unless is_article_87=true):
     *     - < 2 years: 0% (0.0)
     *     - >= 2 and < 5 years: 1/3 (0.3333)
     *     - >= 5 and < 10 years: 2/3 (0.6667)
     *     - >= 10 years: 100% (1.0)
     * - Statutory additions:
     *   - Unused leave payout: (calculation_salary / 30) * unused_leave_days
     *   - Repatriation ticket: repatriation_ticket_amount (for non-Saudi / employer-funded return ticket)
     *   - Statutory compensation (Article 107 / Article 81 unlawful termination for non-Saudi):
     *     explicit statutory_compensation_amount or 15 days' wage per year of service when include_statutory_compensation is true.
     *
     * @param array<string, mixed> $input
     * @return array<string, int|float|bool>
     */
    public function calculate(array $input): array
    {
        $hireDate = Carbon::parse((string) $input['hire_date'])->startOfDay();
        $terminationDate = Carbon::parse((string) $input['termination_date'])->startOfDay();

        $serviceDays = $terminationDate->greaterThanOrEqualTo($hireDate)
            ? (int) $hireDate->diffInDays($terminationDate)
            : 0;

        $serviceYears = round($serviceDays / 365.0, 4);

        $baseSalary = max(0.0, (float) ($input['base_salary'] ?? 0));
        $allowances = max(0.0, (float) ($input['allowances_amount'] ?? 0));

        // Unified Wage Base Rule: calculation_salary = basic_salary + housing_allowance.
        // If housing_allowance_amount is explicitly provided (even as 0.0), use it strictly so non-housing allowances
        // (transport/phone/variable) are excluded.
        // Explicit Legacy Fallback: only when a legacy caller/test omits housing_allowance_amount altogether,
        // fall back to allowances_amount for backward compatibility.
        $housingAllowance = array_key_exists('housing_allowance_amount', $input) && $input['housing_allowance_amount'] !== null
            ? max(0.0, (float) $input['housing_allowance_amount'])
            : $allowances;

        $calculationSalary = round($baseSalary + $housingAllowance, 2);

        $firstFiveYears = min($serviceYears, 5.0);
        $remainingYears = max(0.0, $serviceYears - 5.0);

        $fullEosb = round(
            ($firstFiveYears * ($calculationSalary / 2.0)) + ($remainingYears * $calculationSalary),
            2
        );

        $terminationType = (string) ($input['termination_type'] ?? EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION);
        $isArticle87 = ! empty($input['is_article_87']) || $terminationType === EmployeeOffboarding::TYPE_ARTICLE_87;
        $multiplier = $this->resolveMultiplier($terminationType, $serviceYears, $isArticle87);
        $eosbAmount = round($fullEosb * $multiplier, 2);

        $unusedLeaveDays = max(0.0, (float) ($input['unused_leave_days'] ?? 0));
        $dailyWage = $calculationSalary / 30.0;
        $unusedLeavePayout = round($dailyWage * $unusedLeaveDays, 2);

        $repatriationTicket = round(max(0.0, (float) ($input['repatriation_ticket_amount'] ?? 0)), 2);

        if (array_key_exists('statutory_compensation_amount', $input) && $input['statutory_compensation_amount'] !== null) {
            $statutoryCompensation = round(max(0.0, (float) $input['statutory_compensation_amount']), 2);
        } elseif (! empty($input['include_statutory_compensation']) && $serviceYears > 0) {
            // 15 days' wage per year of service (Article 77 / Article 81 / Article 107 compensation)
            $statutoryCompensation = round(($calculationSalary / 2.0) * $serviceYears, 2);
        } else {
            $statutoryCompensation = 0.0;
        }

        $advancesDeduction = max(0.0, (float) ($input['advances_deduction'] ?? 0));
        $custodyDeduction = max(0.0, (float) ($input['custody_deduction'] ?? 0));
        $otherAdditions = max(0.0, (float) ($input['other_additions'] ?? 0));
        $otherDeductions = max(0.0, (float) ($input['other_deductions'] ?? 0));

        $grossEntitlement = $eosbAmount + $unusedLeavePayout + $repatriationTicket + $statutoryCompensation + $otherAdditions;
        $totalDeductions = $advancesDeduction + $custodyDeduction + $otherDeductions;
        $netSettlement = round($grossEntitlement - $totalDeductions, 2);

        return [
            'is_article_87' => $isArticle87,
            'service_days' => $serviceDays,
            'service_years' => $serviceYears,
            'base_salary' => round($baseSalary, 2),
            'allowances_amount' => round($allowances, 2),
            'housing_allowance_amount' => round($housingAllowance, 2),
            'calculation_salary' => $calculationSalary,
            'eosb_multiplier' => round($multiplier, 4),
            'full_eosb_amount' => $fullEosb,
            'eosb_amount' => $eosbAmount,
            'unused_leave_days' => round($unusedLeaveDays, 2),
            'unused_leave_payout' => $unusedLeavePayout,
            'repatriation_ticket_amount' => $repatriationTicket,
            'statutory_compensation_amount' => $statutoryCompensation,
            'advances_deduction' => round($advancesDeduction, 2),
            'custody_deduction' => round($custodyDeduction, 2),
            'other_additions' => round($otherAdditions, 2),
            'other_deductions' => round($otherDeductions, 2),
            'net_settlement' => $netSettlement,
        ];
    }

    public function resolveMultiplier(string $terminationType, float $serviceYears, bool $isArticle87 = false): float
    {
        if ($isArticle87 || $terminationType === EmployeeOffboarding::TYPE_ARTICLE_87) {
            return 1.0;
        }

        if ($terminationType === EmployeeOffboarding::TYPE_ARTICLE_80) {
            return 0.0;
        }

        if ($terminationType === EmployeeOffboarding::TYPE_RESIGNATION) {
            if ($serviceYears < 2.0) {
                return 0.0;
            }

            if ($serviceYears < 5.0) {
                return 1.0 / 3.0;
            }

            if ($serviceYears < 10.0) {
                return 2.0 / 3.0;
            }

            return 1.0;
        }

        return 1.0;
    }
}
