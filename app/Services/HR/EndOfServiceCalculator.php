<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\HR\EmployeeOffboarding;
use Carbon\Carbon;

class EndOfServiceCalculator
{
    /**
     * Calculate Saudi Labor Law End-of-Service Benefit (EOSB) & final settlement.
     */
    public function calculate(array $input): array
    {
        $hireDate = Carbon::parse($input['hire_date'])->startOfDay();
        $terminationDate = Carbon::parse($input['termination_date'])->startOfDay();

        $serviceDays = $terminationDate->gte($hireDate)
            ? (int) $hireDate->diffInDays($terminationDate)
            : 0;

        $serviceYears = round($serviceDays / 365.0, 4);

        $baseSalary = max(0.0, (float) ($input['base_salary'] ?? 0));
        $allowancesAmount = max(0.0, (float) ($input['allowances_amount'] ?? 0));
        $calculationSalary = round($baseSalary + $allowancesAmount, 2);

        $fullEosb = $this->calculateFullEosb($calculationSalary, $serviceYears);
        $terminationType = (string) ($input['termination_type'] ?? EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION);
        $multiplier = $this->resolveMultiplier($terminationType, $serviceYears);
        $eosbAmount = round($fullEosb * $multiplier, 2);

        $unusedLeaveDays = max(0.0, (float) ($input['unused_leave_days'] ?? 0));
        $dailyWage = $calculationSalary / 30.0;
        $unusedLeavePayout = round($dailyWage * $unusedLeaveDays, 2);

        $advancesDeduction = max(0.0, (float) ($input['advances_deduction'] ?? 0));
        $custodyDeduction = max(0.0, (float) ($input['custody_deduction'] ?? 0));
        $otherAdditions = max(0.0, (float) ($input['other_additions'] ?? 0));
        $otherDeductions = max(0.0, (float) ($input['other_deductions'] ?? 0));

        $netSettlement = round(
            $eosbAmount
            + $unusedLeavePayout
            + $otherAdditions
            - $advancesDeduction
            - $custodyDeduction
            - $otherDeductions,
            2
        );

        return [
            'hire_date' => $hireDate->format('Y-m-d'),
            'termination_date' => $terminationDate->format('Y-m-d'),
            'termination_type' => $terminationType,
            'service_days' => $serviceDays,
            'service_years' => $serviceYears,
            'base_salary' => round($baseSalary, 2),
            'allowances_amount' => round($allowancesAmount, 2),
            'calculation_salary' => $calculationSalary,
            'eosb_multiplier' => round($multiplier, 4),
            'full_eosb_amount' => $fullEosb,
            'eosb_amount' => $eosbAmount,
            'unused_leave_days' => round($unusedLeaveDays, 2),
            'unused_leave_payout' => $unusedLeavePayout,
            'advances_deduction' => round($advancesDeduction, 2),
            'custody_deduction' => round($custodyDeduction, 2),
            'other_additions' => round($otherAdditions, 2),
            'other_deductions' => round($otherDeductions, 2),
            'net_settlement' => $netSettlement,
        ];
    }

    private function calculateFullEosb(float $salary, float $years): float
    {
        if ($salary <= 0 || $years <= 0) {
            return 0.0;
        }

        $firstFiveYears = min(5.0, $years);
        $remainingYears = max(0.0, $years - 5.0);

        $amount = ($firstFiveYears * ($salary * 0.5)) + ($remainingYears * $salary);

        return round($amount, 2);
    }

    private function resolveMultiplier(string $terminationType, float $years): float
    {
        return match ($terminationType) {
            EmployeeOffboarding::TYPE_ARTICLE_80 => 0.0,
            EmployeeOffboarding::TYPE_RESIGNATION => match (true) {
                $years < 2.0 => 0.0,
                $years < 5.0 => 1.0 / 3.0,
                $years < 10.0 => 2.0 / 3.0,
                default => 1.0,
            },
            default => 1.0, // employer_termination & article_81
        };
    }
}
