<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeAssetCustody;
use App\Models\HR\EmployeeOffboarding;
use App\Services\HR\EndOfServiceCalculator;
use App\Support\TenancyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeOffboardingController extends Controller
{
    public function __construct(
        private readonly EndOfServiceCalculator $calculator
    ) {}

    /**
     * Save or update a draft offboarding & EOSB settlement for an employee.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'termination_type' => ['required', 'string', Rule::in(EmployeeOffboarding::TERMINATION_TYPES)],
            'is_article_87' => ['nullable', 'boolean'],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['required', 'date', 'after_or_equal:hire_date'],
            'use_manual_salary' => ['nullable', 'boolean'],
            'manual_salary_reason' => ['nullable', 'required_if:use_manual_salary,true,1', 'string', 'max:500'],
            'base_salary' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'allowances_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'housing_allowance_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'unused_leave_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'repatriation_ticket_amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'statutory_compensation_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'include_statutory_compensation' => ['nullable', 'boolean'],
            'advances_deduction' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'custody_deduction' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'other_additions' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'other_deductions' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'custody_cleared' => ['nullable', 'boolean'],
            'finance_cleared' => ['nullable', 'boolean'],
            'it_access_revoked' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'employee_signature' => ['nullable', 'string'],
            'hr_signature' => ['nullable', 'string'],
        ]);

        $existingFinalized = $employee->offboardings()
            ->where('status', EmployeeOffboarding::STATUS_FINALIZED)
            ->first();

        if ($existingFinalized) {
            return back()->with('error', __('hr.offboarding_already_finalized'));
        }

        $employee->loadMissing(['allowances', 'nationality']);

        $calculatedBaseSalary = round((float) ($employee->base_salary ?? 0), 2);
        $calculatedAllowancesAmount = round($employee->calculateTotalAllowances(), 2);
        // Unified rule: housing allowance is strictly the sum of housing-designated allowances (0.0 if none exist).
        $calculatedHousingAllowance = round($employee->calculateHousingAllowance(), 2);

        $useManualSalary = $request->boolean('use_manual_salary');

        if ($useManualSalary) {
            $effectiveBaseSalary = (float) ($validated['base_salary'] ?? $calculatedBaseSalary);
            $effectiveAllowances = (float) ($validated['allowances_amount'] ?? $calculatedAllowancesAmount);
            $effectiveHousingAllowance = array_key_exists('housing_allowance_amount', $validated) && $validated['housing_allowance_amount'] !== null
                ? (float) $validated['housing_allowance_amount']
                : $calculatedHousingAllowance;
            $manualSalaryReason = trim((string) ($validated['manual_salary_reason'] ?? ''));
        } else {
            $effectiveBaseSalary = $calculatedBaseSalary;
            $effectiveAllowances = $calculatedAllowancesAmount;
            $effectiveHousingAllowance = $calculatedHousingAllowance;
            $manualSalaryReason = null;
        }

        $includeStatutoryCompensation = $request->boolean('include_statutory_compensation')
            || ($validated['termination_type'] === EmployeeOffboarding::TYPE_ARTICLE_81 && $employee->isNonSaudi());

        $calculatorInput = array_merge($validated, [
            'base_salary' => $effectiveBaseSalary,
            'allowances_amount' => $effectiveAllowances,
            'housing_allowance_amount' => $effectiveHousingAllowance,
            'is_article_87' => $request->boolean('is_article_87') || $validated['termination_type'] === EmployeeOffboarding::TYPE_ARTICLE_87,
            'include_statutory_compensation' => $includeStatutoryCompensation,
        ]);

        $calculated = $this->calculator->calculate($calculatorInput);

        $tenantId = TenancyContext::tenantId() ?? $employee->tenant_id;
        $centerId = $employee->center_id ?? TenancyContext::centerId();

        $draft = $employee->offboardings()
            ->where('status', EmployeeOffboarding::STATUS_DRAFT)
            ->latest('id')
            ->first();

        // Pin initial calculated baseline on existing drafts so recalculations never silently overwrite the original reference
        $pinnedCalculatedBaseSalary = ($draft && $draft->calculated_base_salary !== null)
            ? (float) $draft->calculated_base_salary
            : $calculatedBaseSalary;

        $pinnedCalculatedAllowancesAmount = ($draft && $draft->calculated_allowances_amount !== null)
            ? (float) $draft->calculated_allowances_amount
            : $calculatedAllowancesAmount;

        $previousDraftSnapshot = $draft ? [
            'base_salary' => (float) $draft->base_salary,
            'allowances_amount' => (float) $draft->allowances_amount,
            'housing_allowance_amount' => (float) $draft->housing_allowance_amount,
            'calculation_salary' => (float) $draft->calculation_salary,
            'net_settlement' => (float) $draft->net_settlement,
        ] : null;

        $payload = array_merge($calculated, [
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'employee_id' => $employee->id,
            'termination_type' => $validated['termination_type'],
            'hire_date' => $validated['hire_date'],
            'termination_date' => $validated['termination_date'],
            'use_manual_salary' => $useManualSalary,
            'manual_salary_reason' => $manualSalaryReason,
            'calculated_base_salary' => $pinnedCalculatedBaseSalary,
            'calculated_allowances_amount' => $pinnedCalculatedAllowancesAmount,
            'custody_cleared' => (bool) ($validated['custody_cleared'] ?? false),
            'finance_cleared' => (bool) ($validated['finance_cleared'] ?? false),
            'it_access_revoked' => (bool) ($validated['it_access_revoked'] ?? false),
            'notes' => $validated['notes'] ?? null,
            'employee_signature' => $validated['employee_signature'] ?? ($draft?->employee_signature),
            'hr_signature' => $validated['hr_signature'] ?? ($draft?->hr_signature),
            'status' => EmployeeOffboarding::STATUS_DRAFT,
        ]);

        if ($draft) {
            $draft->update($payload);
            $offboarding = $draft;
            $action = 'updated';
        } else {
            $payload['created_by'] = $request->user()?->id ?? auth()->id();
            $offboarding = EmployeeOffboarding::create($payload);
            $action = 'created';
        }

        $activityContext = [
            'termination_type' => $offboarding->termination_type,
            'net_settlement' => $offboarding->net_settlement,
            'use_manual_salary' => $useManualSalary,
        ];

        if ($useManualSalary) {
            $activityContext['manual_salary_reason'] = $manualSalaryReason;
            $activityContext['calculated_vs_manual'] = [
                'pinned_calculated_base_salary' => $pinnedCalculatedBaseSalary,
                'current_employee_base_salary' => $calculatedBaseSalary,
                'manual_base_salary' => $offboarding->base_salary,
                'pinned_calculated_allowances_amount' => $pinnedCalculatedAllowancesAmount,
                'current_employee_allowances_amount' => $calculatedAllowancesAmount,
                'manual_allowances_amount' => $offboarding->allowances_amount,
            ];
        }

        if ($previousDraftSnapshot !== null && (
            $previousDraftSnapshot['calculation_salary'] !== (float) $offboarding->calculation_salary
            || $previousDraftSnapshot['net_settlement'] !== (float) $offboarding->net_settlement
            || $pinnedCalculatedBaseSalary !== $calculatedBaseSalary
        )) {
            $activityContext['recalculation_diff'] = [
                'previous' => $previousDraftSnapshot,
                'current' => [
                    'base_salary' => (float) $offboarding->base_salary,
                    'allowances_amount' => (float) $offboarding->allowances_amount,
                    'housing_allowance_amount' => (float) $offboarding->housing_allowance_amount,
                    'calculation_salary' => (float) $offboarding->calculation_salary,
                    'net_settlement' => (float) $offboarding->net_settlement,
                ],
                'pinned_calculated_base_salary' => $pinnedCalculatedBaseSalary,
                'live_employee_base_salary' => $calculatedBaseSalary,
            ];
        }

        $offboarding->logActivity($action, $activityContext);

        return back()->with('success', __('hr.offboarding_draft_saved'));
    }

    /**
     * Finalize offboarding clearance, enforce custody checks, and terminate employee & revoke access.
     */
    public function finalize(Request $request, Employee $employee, EmployeeOffboarding $offboarding): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_if((int) $offboarding->employee_id !== (int) $employee->id, 404);

        if ($offboarding->status === EmployeeOffboarding::STATUS_FINALIZED) {
            return back()->with('error', __('hr.offboarding_already_finalized'));
        }

        $validated = $request->validate([
            'employee_signature' => ['nullable', 'string'],
            'hr_signature' => ['nullable', 'string'],
            'finance_cleared' => ['boolean'],
            'it_access_revoked' => ['boolean'],
        ]);

        $hasUnreturnedCustody = EmployeeAssetCustody::query()
            ->where('employee_id', $employee->id)
            ->where('status', EmployeeAssetCustody::STATUS_ISSUED)
            ->exists();

        if ($hasUnreturnedCustody && (float) $offboarding->custody_deduction <= 0) {
            return back()
                ->withErrors(['custody_cleared' => __('hr.offboarding_unreturned_custody_error')])
                ->with('error', __('hr.offboarding_unreturned_custody_error'));
        }

        DB::transaction(function () use ($request, $employee, $offboarding, $validated) {
            $offboarding->update([
                'custody_cleared' => true,
                'finance_cleared' => (bool) ($validated['finance_cleared'] ?? true),
                'it_access_revoked' => (bool) ($validated['it_access_revoked'] ?? true),
                'employee_signature' => $validated['employee_signature'] ?? $offboarding->employee_signature,
                'hr_signature' => $validated['hr_signature'] ?? $offboarding->hr_signature,
                'status' => EmployeeOffboarding::STATUS_FINALIZED,
                'finalized_by' => $request->user()?->id,
                'finalized_at' => now(),
            ]);

            $employee->update([
                'status' => 'terminated',
                'termination_date' => $offboarding->termination_date,
                'termination_reason' => $offboarding->termination_type,
            ]);

            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }

            $offboarding->logActivity('finalized', [
                'termination_type' => $offboarding->termination_type,
                'net_settlement' => $offboarding->net_settlement,
            ]);
        });

        return back()->with('success', __('hr.offboarding_finalized_success'));
    }

    public function print(Employee $employee, EmployeeOffboarding $offboarding): Response
    {
        $this->authorize('view', $employee);
        abort_if((int) $offboarding->employee_id !== (int) $employee->id, 404);

        $employee->load(['jobTitle', 'department', 'center', 'tenant']);
        $offboarding->load(['createdBy:id,name', 'finalizedBy:id,name']);

        return Inertia::render('HR/Employees/OffboardingPrint', [
            'employee' => $employee,
            'offboarding' => $offboarding,
            'tenant' => $employee->tenant,
        ]);
    }
}
