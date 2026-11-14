<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HR\Allowance;
use App\Models\HR\Attendance;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeContract;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\HR\Leave;
use App\Models\HR\Payroll;
use App\Support\TenancyContext;
use Inertia\Inertia;

class HRController extends Controller
{
    /**
     * HR Command Center Dashboard with live KPIs, alerts, and quick access cards.
     */
    public function index()
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $today = now()->toDateString();
        $expiryCutoff = now()->addDays(30)->toDateString();

        $totalEmployees = Employee::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->count();
        $activeEmployees = Employee::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->active()
            ->count();

        $attendanceToday = Attendance::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereDate('date', $today)
            ->selectRaw("
                SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
            ")
            ->first();

        $pendingLeavesCount = Leave::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->whereHas('employee', fn ($q) => $q->where('center_id', $centerId))
            ->count();

        $expiringDocumentsQuery = EmployeeDocument::where('tenant_id', $tenantId)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $expiryCutoff)
            ->whereHas('employee', fn ($q) => $q->where('center_id', $centerId)->active());

        $expiringDocumentsCount = (clone $expiringDocumentsQuery)->count();
        $expiringDocuments = (clone $expiringDocumentsQuery)
            ->with(['employee:id,name_ar,name_en,employee_number'])
            ->orderBy('expiry_date')
            ->take(5)
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'kind' => 'document',
                'title' => $doc->title ?: $doc->type,
                'expiry_date' => $doc->expiry_date?->toDateString(),
                'is_expired' => $doc->expiry_date?->isPast() ?? false,
                'employee_id' => $doc->employee_id,
                'employee_name' => $doc->employee?->display_name,
            ]);

        $expiringContractsQuery = EmployeeContract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $expiryCutoff)
            ->whereHas('employee', fn ($q) => $q->where('center_id', $centerId)->active());

        $expiringContractsCount = (clone $expiringContractsQuery)->count();
        $expiringContracts = (clone $expiringContractsQuery)
            ->with(['employee:id,name_ar,name_en,employee_number'])
            ->orderBy('end_date')
            ->take(5)
            ->get()
            ->map(fn ($contract) => [
                'id' => $contract->id,
                'kind' => 'contract',
                'title' => $contract->contract_number,
                'expiry_date' => $contract->end_date?->toDateString(),
                'is_expired' => $contract->end_date?->isPast() ?? false,
                'employee_id' => $contract->employee_id,
                'employee_name' => $contract->employee?->display_name,
            ]);

        $currentPayroll = Payroll::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('period', now()->format('Y-m'))
            ->first();

        // Stats for dashboard cards & KPIs
        $stats = [
            'employees' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
            ],
            'attendance' => [
                'present_today' => (int) ($attendanceToday?->present_count ?? 0),
                'late_today' => (int) ($attendanceToday?->late_count ?? 0),
                'absent_today' => (int) ($attendanceToday?->absent_count ?? 0),
            ],
            'leaves' => [
                'pending' => $pendingLeavesCount,
            ],
            'compliance' => [
                'expiring_documents' => $expiringDocumentsCount,
                'expiring_contracts' => $expiringContractsCount,
                'total_alerts' => $expiringDocumentsCount + $expiringContractsCount,
            ],
            'payroll' => [
                'current_period' => now()->format('Y-m'),
                'status' => $currentPayroll?->status ?? 'none',
                'net_total' => (float) ($currentPayroll?->net_total ?? 0),
            ],
            'settings' => [
                'employee_types' => EmployeeType::where('tenant_id', $tenantId)->count(),
                'job_titles' => JobTitle::where('tenant_id', $tenantId)->count(),
                'allowances' => Allowance::where('tenant_id', $tenantId)->count(),
                'deductions' => Deduction::where('tenant_id', $tenantId)->count(),
            ],
        ];

        $recentEmployees = Employee::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['jobTitle', 'department', 'nationality'])
            ->latest()
            ->take(5)
            ->get();

        $departmentsStats = Department::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->active()
            ->withCount(['employees' => function ($q) use ($centerId) {
                $q->where('center_id', $centerId); // Ensure employee is also in this center
            }])
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name, // Accessor handles locale
                    'count' => $dept->employees_count,
                ];
            });

        $complianceAlerts = $expiringDocuments
            ->concat($expiringContracts)
            ->sortBy('expiry_date')
            ->values()
            ->take(6);

        return Inertia::render('HR/Index', [
            'stats' => $stats,
            'recentEmployees' => $recentEmployees,
            'departmentsStats' => $departmentsStats,
            'complianceAlerts' => $complianceAlerts,
        ]);
    }
}
