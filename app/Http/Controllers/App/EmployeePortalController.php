<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\HR\PayrollItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Models\WorkOrderPhoto;
use App\Services\WorkOrderLaborService;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RuntimeException;

class EmployeePortalController extends Controller
{
    /**
     * Get the authenticated employee record.
     */
    protected function getEmployee()
    {
        $user = auth()->user();

        if (! $user->employee) {
            abort(403, __('messages.no_employee_linked'));
        }

        return $user->employee;
    }

    /**
     * Employee Dashboard - Overview of key info.
     */
    public function dashboard()
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();
        $employee->load(['jobTitle', 'department', 'center', 'defaultShift']);

        // Current month stats
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Attendance summary
        $attendanceStats = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(TIMESTAMPDIFF(MINUTE, check_in, check_out)) as total_minutes
            ')
            ->first();

        // Leave balance (simplified - would need proper leave balance calculation)
        $usedLeaves = Leave::where('employee_id', $employee->id)
            ->whereYear('start_date', $now->year)
            ->where('status', 'approved')
            ->sum('days');

        // Pending leave requests
        $pendingLeaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        // Recent payslips
        $recentPayslips = PayrollItem::where('employee_id', $employee->id)
            ->with('payrollRun:id,period_start,period_end')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return Inertia::render('EmployeePortal/Dashboard', [
            'employee' => $employee,
            'stats' => [
                'attendance' => [
                    'present' => $attendanceStats->present_days ?? 0,
                    'absent' => $attendanceStats->absent_days ?? 0,
                    'late' => $attendanceStats->late_days ?? 0,
                    'total_hours' => round(($attendanceStats->total_minutes ?? 0) / 60, 1),
                ],
                'leaves' => [
                    'used' => $usedLeaves,
                    'pending' => $pendingLeaves,
                    'annual_balance' => 21 - $usedLeaves, // Simplified - 21 days annual
                ],
            ],
            'recentPayslips' => $recentPayslips,
        ]);
    }

    /**
     * Employee Profile - Personal information.
     */
    public function profile()
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();
        $employee->load([
            'jobTitle',
            'employeeType',
            'department',
            'nationality',
            'center',
            'defaultShift',
        ]);

        return Inertia::render('EmployeePortal/Profile', [
            'employee' => $employee,
        ]);
    }

    /**
     * Employee Attendance - Personal attendance records.
     */
    public function attendance(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();

        // Default to current month
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('shift:id,name_ar,name_en,start_time,end_time')
            ->orderBy('date')
            ->get();

        // Calculate monthly summary
        $summary = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'early_leave' => $attendance->where('status', 'early_leave')->count(),
            'total_hours' => round($attendance->sum(function ($a) {
                if ($a->check_in && $a->check_out) {
                    return Carbon::parse($a->check_out)->diffInMinutes(Carbon::parse($a->check_in)) / 60;
                }

                return 0;
            }), 1),
        ];

        return Inertia::render('EmployeePortal/Attendance', [
            'attendance' => $attendance,
            'summary' => $summary,
            'month' => $month,
            'employee' => $employee->only(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Employee Leaves - Leave requests and history.
     */
    public function leaves(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();

        $leaves = Leave::where('employee_id', $employee->id)
            ->with(['approvedBy:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10);

        // Leave balance by type (simplified)
        $usedByType = Leave::where('employee_id', $employee->id)
            ->whereYear('start_date', Carbon::now()->year)
            ->where('status', 'approved')
            ->selectRaw('type, SUM(days) as used_days')
            ->groupBy('type')
            ->pluck('used_days', 'type');

        return Inertia::render('EmployeePortal/Leaves', [
            'leaves' => $leaves,
            'usedByType' => $usedByType,
            'employee' => $employee->only(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Request a new leave.
     */
    public function requestLeave(Request $request)
    {
        $this->authorize('createOwn', Leave::class);

        $employee = $this->getEmployee();

        $validated = $request->validate([
            'type' => 'required|string|in:annual,sick,unpaid,emergency,maternity,paternity,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        Leave::create([
            'tenant_id' => TenancyContext::tenantId(),
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        return back()->with('success', __('hr.leaves.request_submitted'));
    }

    /**
     * Employee Payslips - Salary history.
     */
    public function payslips(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();

        $payslips = PayrollItem::where('employee_id', $employee->id)
            ->with(['payrollRun:id,period_start,period_end,status'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return Inertia::render('EmployeePortal/Payslips', [
            'payslips' => $payslips,
            'employee' => $employee->only(['id', 'name_ar', 'name_en', 'base_salary']),
        ]);
    }

    /**
     * View single payslip details.
     */
    public function showPayslip(PayrollItem $payslip)
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();

        // Ensure this payslip belongs to the employee
        if ($payslip->employee_id !== $employee->id) {
            abort(403);
        }

        $payslip->load(['payrollRun:id,period_start,period_end,status', 'employee.jobTitle', 'employee.department']);

        return Inertia::render('EmployeePortal/PayslipShow', [
            'payslip' => $payslip,
        ]);
    }

    /**
     * Technician's assigned work orders (My Tasks)
     */
    public function tasks(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);

        $employee = $this->getEmployee();
        $status = $request->get('status', 'active');
        $search = $request->get('search');
        $userId = $employee->user_id;

        $workOrdersQuery = WorkOrder::query()
            ->where('center_id', $employee->center_id)
            ->whereHas('items', function ($query) use ($userId) {
                $query->whereHas('technicians', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                });
            })
            ->with([
                'customer',
                'vehicle.customer',
                'vehicle.make',
                'vehicle.model',
                'items' => fn ($q) => $q
                    ->whereHas('technicians', function ($query) use ($userId) {
                        $query->where('users.id', $userId);
                    })
                    ->with([
                        'parts',
                        'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
                        'photos',
                        'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
                        'laborEntries' => fn ($lq) => $lq->where('user_id', $userId)->latest(),
                    ]),
            ]);

        if (! empty($search)) {
            $workOrdersQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('plate_number', 'like', "%{$search}%")
                            ->orWhere('vin', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name_ar', 'like', "%{$search}%")
                                    ->orWhere('name_en', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($status === 'active') {
            $workOrdersQuery->whereHas('items', function ($query) use ($userId) {
                $query->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                    ->whereIn('status', [
                        WorkOrderItem::STATUS_PENDING,
                        WorkOrderItem::STATUS_IN_PROGRESS,
                        WorkOrderItem::STATUS_ON_HOLD,
                        WorkOrderItem::STATUS_READY_FOR_QC,
                    ]);
            });
        } elseif ($status === 'completed') {
            $workOrdersQuery->whereHas('items', function ($query) use ($userId) {
                $query->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                    ->where('status', WorkOrderItem::STATUS_COMPLETED);
            });
        } elseif ($status !== 'all') {
            $workOrdersQuery->whereHas('items', function ($query) use ($userId, $status) {
                $query->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                    ->where('status', $status);
            });
        }

        $workOrders = $workOrdersQuery->orderByDesc('created_at')->paginate(15)->withQueryString();

        $counts = [
            'active' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->whereIn('status', [
                    WorkOrderItem::STATUS_PENDING,
                    WorkOrderItem::STATUS_IN_PROGRESS,
                    WorkOrderItem::STATUS_ON_HOLD,
                    WorkOrderItem::STATUS_READY_FOR_QC,
                ])->count(),
            'in_progress' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->where('status', WorkOrderItem::STATUS_IN_PROGRESS)->count(),
            'pending' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->where('status', WorkOrderItem::STATUS_PENDING)->count(),
            'ready_for_qc' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->where('status', WorkOrderItem::STATUS_READY_FOR_QC)->count(),
            'completed' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->where('status', WorkOrderItem::STATUS_COMPLETED)->count(),
            'all' => WorkOrderItem::query()
                ->where('center_id', $employee->center_id)
                ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
                ->count(),
        ];

        return Inertia::render('EmployeePortal/MyTasks', [
            'workOrders' => $workOrders,
            'statusFilter' => $status,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'employee' => $employee->only(['id', 'name_ar', 'name_en']),
            'authUserId' => $userId,
        ]);
    }

    /**
     * Update work order item status (Technician)
     */
    public function updateItemStatus(Request $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', WorkOrderItem::STATUSES),
            'notes' => 'nullable|string|max:500',
        ]);

        $newStatus = $validated['status'];

        if (! $item->canChangeStatusTo($newStatus)) {
            $message = __('messages.cannot_change_item_status');

            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $item->update(['status' => $newStatus]);

        $workOrder->logActivity(
            'item_status_updated',
            __('work_orders.activities.actions.item_status_updated', [
                'title' => $item->title,
                'status' => __('work_orders.item_status.'.$newStatus),
            ])
        );

        $message = __('messages.item_status_updated');

        return $request->expectsJson()
            ? response()->json(['success' => $message, 'item' => $item->fresh(['parts', 'technicians:id,name', 'photos'])])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Mark work order item as completed (Technician)
     */
    public function completeItem(Request $request, WorkOrder $workOrder, WorkOrderItem $item, WorkOrderLaborService $laborService)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'confirm' => 'required|string',
        ]);

        if ($validated['confirm'] !== 'complete') {
            return $request->expectsJson()
                ? response()->json(['error' => __('messages.action_not_confirmed')], 422)
                : redirect()->back()->with('error', __('messages.action_not_confirmed'));
        }

        $userId = $request->user()->id;

        // 1. End any active or paused labor session for this technician
        $openEntry = WorkOrderLaborEntry::query()
            ->where('work_order_item_id', $item->id)
            ->where('user_id', $userId)
            ->whereIn('status', [WorkOrderLaborEntry::STATUS_RUNNING, WorkOrderLaborEntry::STATUS_PAUSED])
            ->first();

        if ($openEntry) {
            try {
                $laborService->end($openEntry);
            } catch (RuntimeException $e) {
                // Ignore if already ended
            }
        }

        // 2. Mark this technician's completion in pivot table
        $item->technicians()->updateExistingPivot($userId, [
            'completed_at' => now(),
        ]);

        // 3. Check if all assigned technicians on this service have completed
        $remainingTechs = $item->technicians()
            ->wherePivotNull('completed_at')
            ->get();

        $allCompleted = $remainingTechs->isEmpty();

        if ($allCompleted) {
            // All technicians are done -> mark the item completed
            $item->update([
                'status' => WorkOrderItem::STATUS_COMPLETED,
                'completed_at' => now(),
                'paused_at' => null,
            ]);

            // Calculate actual duration in minutes from all completed labor entries
            $totalSeconds = $item->laborEntries()->get()->sum(fn ($e) => $e->effectiveDurationSeconds());
            $item->update([
                'actual_duration' => (int) round($totalSeconds / 60),
            ]);

            // If all items on the work order are completed, auto-advance the work order
            if ($workOrder->allItemsCompleted()) {
                if (in_array($workOrder->status, [WorkOrder::STATUS_OPEN, WorkOrder::STATUS_IN_PROGRESS])) {
                    $workOrder->update(['status' => WorkOrder::STATUS_READY_FOR_QC]);
                }
            }

            $workOrder->logActivity(
                'item_completed',
                "تم إكمال الخدمة ({$item->title}) بالكامل من جميع الفنيين"
            );

            $message = 'تم إكمال الخدمة بنجاح من جميع الفنيين المكلفين';
        } else {
            // Other technicians are still working
            if ($item->status === WorkOrderItem::STATUS_PENDING) {
                $item->update(['status' => WorkOrderItem::STATUS_IN_PROGRESS]);
            }

            $techNames = $remainingTechs->pluck('name')->join('، ');
            $workOrder->logActivity(
                'tech_completed',
                "أنهى الفني ({$request->user()->name}) دوره في الخدمة ({$item->title})"
            );

            $message = "تم إنهاء دورك في الخدمة بنجاح، بانتظار استكمال باقي الفنيين ({$techNames})";
        }

        $freshItem = $item->fresh([
            'parts',
            'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
            'photos',
            'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
            'laborEntries' => fn ($lq) => $lq->where('user_id', $userId)->latest(),
        ]);

        return $request->expectsJson()
            ? response()->json([
                'success' => $message,
                'item' => $freshItem,
                'all_completed' => $allCompleted,
            ])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Update technician notes for an item
     */
    public function updateNotes(Request $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'technician_notes' => 'nullable|string|max:2000',
        ]);

        $content = trim($validated['technician_notes'] ?? '');

        // 1. Update technician_notes column on the item
        $item->update(['technician_notes' => $content]);

        // 2. Also reflect in itemNotes (WorkOrderItemNote) so it appears on the main work order Notes tab!
        if (! empty($content)) {
            $item->itemNotes()->create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'work_order_id' => $workOrder->id,
                'user_id' => $request->user()->id,
                'content' => $content,
            ]);

            $workOrder->logActivity(
                'note_added',
                "ملاحظة فنية من ({$request->user()->name}) على الخدمة ({$item->title}): {$content}"
            );
        }

        $freshItem = $item->fresh([
            'parts',
            'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
            'photos',
            'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
            'laborEntries' => fn ($lq) => $lq->where('user_id', $request->user()->id)->latest(),
        ]);

        return response()->json([
            'success' => __('messages.notes_saved'),
            'item' => $freshItem,
        ]);
    }

    /**
     * Upload photos for an item
     */
    public function uploadMedia(Request $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $this->authorize('update', $item);

        $request->validate([
            'files.*' => 'required|image|max:5120', // 5MB max per image
            'files' => 'required|array|min:1|max:10',
            'type' => 'nullable|string|in:general,before,after,damage',
        ]);

        $type = $request->input('type', WorkOrderPhoto::TYPE_GENERAL);
        $createdPhotos = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store("work_orders/{$workOrder->id}/items/{$item->id}", 'public');

            $photo = $workOrder->photos()->create([
                'work_order_item_id' => $item->id,
                'path' => $path,
                'type' => $type,
                'caption' => $item->title,
            ]);

            $createdPhotos[] = $photo;
        }

        $workOrder->logActivity(
            'photos_uploaded',
            __('work_orders.activities.actions.photos_uploaded')
        );

        return response()->json([
            'success' => __('messages.photos_uploaded_successfully'),
            'photos' => $item->photos()->get(),
            'item' => $item->fresh(['parts', 'technicians:id,name', 'photos']),
        ]);
    }

    /**
     * Delete a photo from an item
     */
    public function deletePhoto(Request $request, WorkOrder $workOrder, WorkOrderItem $item, WorkOrderPhoto $photo)
    {
        $this->authorize('update', $item);

        if ($photo->work_order_id !== $workOrder->id || ($photo->work_order_item_id && $photo->work_order_item_id !== $item->id)) {
            abort(403);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json([
            'success' => __('messages.photo_deleted'),
            'photos' => $item->photos()->get(),
            'item' => $item->fresh(['parts', 'technicians:id,name', 'photos']),
        ]);
    }

    /**
     * Update checklist for an item
     */
    public function updateChecklist(Request $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'checklist' => 'required|array',
            'checklist.*.text' => 'required|string|max:500',
            'checklist.*.completed' => 'boolean',
        ]);

        $item->update([
            'checklist' => $validated['checklist'],
        ]);

        return response()->json([
            'success' => __('messages.checklist_saved'),
            'item' => $item->fresh(['parts', 'technicians:id,name', 'photos']),
        ]);
    }

    /**
     * Start timer for an item
     */
    public function startTimer(Request $request, WorkOrder $workOrder, WorkOrderItem $item, WorkOrderLaborService $laborService)
    {
        $this->authorize('update', $item);
        $user = $request->user();

        // 1. Start session in WorkOrderLaborService
        try {
            $entry = $laborService->start($workOrder, $item, $user);
        } catch (RuntimeException $e) {
            $errorMsg = __("messages.{$e->getMessage()}");

            if ($errorMsg === "messages.{$e->getMessage()}") {
                $errorMsg = $e->getMessage();
            }

            return response()->json(['error' => $errorMsg], 422);
        }

        // 2. Keep item level tracking in sync
        if (! $item->started_at) {
            $item->update(['started_at' => now()]);
        }
        $item->update([
            'paused_at' => null,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);

        if (in_array($workOrder->status, [WorkOrder::STATUS_OPEN, WorkOrder::STATUS_DRAFT])) {
            $workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        }

        $freshItem = $item->fresh([
            'parts',
            'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
            'photos',
            'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
            'laborEntries' => fn ($lq) => $lq->where('user_id', $user->id)->latest(),
        ]);

        return response()->json([
            'success' => __('messages.timer_started'),
            'item' => $freshItem,
            'entry' => $entry,
        ]);
    }

    /**
     * Pause timer for an item
     */
    public function pauseTimer(Request $request, WorkOrder $workOrder, WorkOrderItem $item, WorkOrderLaborService $laborService)
    {
        $this->authorize('update', $item);
        $user = $request->user();

        $entry = WorkOrderLaborEntry::query()
            ->where('work_order_item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('status', WorkOrderLaborEntry::STATUS_RUNNING)
            ->latest()
            ->first();

        if (! $entry) {
            return response()->json(['error' => __('messages.entry_not_running')], 422);
        }

        try {
            $entry = $laborService->pause($entry);
        } catch (RuntimeException $e) {
            return response()->json(['error' => __("messages.{$e->getMessage()}")], 422);
        }

        $item->update(['paused_at' => now()]);

        $freshItem = $item->fresh([
            'parts',
            'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
            'photos',
            'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
            'laborEntries' => fn ($lq) => $lq->where('user_id', $user->id)->latest(),
        ]);

        return response()->json([
            'success' => __('messages.timer_paused'),
            'item' => $freshItem,
            'entry' => $entry,
        ]);
    }

    /**
     * Resume timer for an item
     */
    public function resumeTimer(Request $request, WorkOrder $workOrder, WorkOrderItem $item, WorkOrderLaborService $laborService)
    {
        $this->authorize('update', $item);
        $user = $request->user();

        $entry = WorkOrderLaborEntry::query()
            ->where('work_order_item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('status', WorkOrderLaborEntry::STATUS_PAUSED)
            ->latest()
            ->first();

        if (! $entry) {
            return response()->json(['error' => __('messages.entry_not_paused')], 422);
        }

        try {
            $entry = $laborService->resume($entry);
        } catch (RuntimeException $e) {
            return response()->json(['error' => __("messages.{$e->getMessage()}")], 422);
        }

        $item->update(['paused_at' => null]);

        $freshItem = $item->fresh([
            'parts',
            'technicians' => fn ($tq) => $tq->withPivot(['assigned_at', 'completed_at', 'notes', 'share']),
            'photos',
            'itemNotes' => fn ($nq) => $nq->with('user:id,name')->latest(),
            'laborEntries' => fn ($lq) => $lq->where('user_id', $user->id)->latest(),
        ]);

        return response()->json([
            'success' => __('messages.timer_resumed'),
            'item' => $freshItem,
            'entry' => $entry,
        ]);
    }

    /**
     * Update priority for an item
     */
    public function updatePriority(Request $request, WorkOrder $workOrder, WorkOrderItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        $item->update(['priority' => $validated['priority']]);

        return response()->json([
            'success' => __('messages.priority_updated'),
            'item' => $item->fresh(['parts', 'technicians:id,name', 'photos']),
        ]);
    }
}
