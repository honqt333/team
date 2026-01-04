<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HR\Allowance;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\HR\Payroll;
use App\Support\TenancyContext;
use Inertia\Inertia;

class HRController extends Controller
{
    /**
     * HR Dashboard with cards linking to sub-pages.
     */
    public function index()
    {
        $tenantId = TenancyContext::tenantId();

        $centerId = TenancyContext::centerId();

        // Stats for dashboard cards
        $stats = [
            'employees' => [
                'total' => Employee::where('tenant_id', $tenantId)->where('center_id', $centerId)->count(),
                'active' => Employee::where('tenant_id', $tenantId)->where('center_id', $centerId)->active()->count(),
            ],
            'attendance' => [
                'present_today' => 0, // Will be calculated when attendance is implemented
            ],
            'payroll' => [
                'current_period' => now()->format('Y-m'),
                'status' => Payroll::where('tenant_id', $tenantId)
                    ->where('center_id', $centerId)
                    ->where('period', now()->format('Y-m'))
                    ->first()?->status ?? 'none',
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
            ->withCount(['employees' => function($q) use ($centerId) {
                $q->where('center_id', $centerId); // Ensure employee is also in this center
            }])
            ->get()
            ->map(function($dept) {
                return [
                    'name' => $dept->name, // Accessor handles locale
                    'count' => $dept->employees_count,
                ];
            });

        return Inertia::render('HR/Index', [
            'stats' => $stats,
            'recentEmployees' => $recentEmployees,
            'departmentsStats' => $departmentsStats,
        ]);
    }
}
