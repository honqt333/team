<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeAssetCustody;
use App\Models\HR\EmployeeOffboarding;
use App\Services\HR\EndOfServiceCalculator;
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

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'termination_type' => ['required', Rule::in(EmployeeOffboarding::TERMINATION_TYPES)],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['required', 'date', 'after_or_equal:hire_date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'allowances_amount' => ['nullable', 'numeric', 'min:0'],
            'unused_leave_days' => ['nullable', 'numeric', 'min:0'],
            'advances_deduction' => ['nullable', 'numeric', 'min:0'],
            'custody_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_additions' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'custody_cleared' => ['boolean'],
            'finance_cleared' => ['boolean'],
            'it_access_revoked' => ['boolean'],
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

        $calculated = $this->calculator->calculate($validated);

        $draft = $employee->offboardings()
            ->where('status', EmployeeOffboarding::STATUS_DRAFT)
            ->latest('id')
            ->first();

        $payload = array_merge($calculated, [
            'tenant_id' => $employee->tenant_id,
            'center_id' => $employee->center_id,
            'employee_id' => $employee->id,
            'custody_cleared' => (bool) ($validated['custody_cleared'] ?? false),
            'finance_cleared' => (bool) ($validated['finance_cleared'] ?? false),
            'it_access_revoked' => (bool) ($validated['it_access_revoked'] ?? false),
            'notes' => $validated['notes'] ?? null,
            'employee_signature' => $validated['employee_signature'] ?? $draft?->employee_signature,
            'hr_signature' => $validated['hr_signature'] ?? $draft?->hr_signature,
            'status' => EmployeeOffboarding::STATUS_DRAFT,
        ]);

        if ($draft) {
            $draft->update($payload);
            $draft->logActivity('updated', ['net_settlement' => $draft->net_settlement]);
        } else {
            $payload['created_by'] = $request->user()?->id;
            $draft = EmployeeOffboarding::create($payload);
            $draft->logActivity('created', ['net_settlement' => $draft->net_settlement]);
        }

        return back()->with('success', __('hr.offboarding_draft_saved'));
    }

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
