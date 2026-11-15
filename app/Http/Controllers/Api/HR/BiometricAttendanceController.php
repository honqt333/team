<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Employee;
use App\Services\Billing\SubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BiometricAttendanceController extends Controller
{
    /**
     * تسجيل حضور/انصراف من جهاز البصمة
     *
     * POST /api/v1/attendance/clock
     * Headers: Authorization: Bearer {device_api_token}
     *
     * Body:
     * {
     *   "employee_identifier": "EMP-0001" or "1234567890" (national_id) or "bio-001" (biometric_id),
     *   "action": "check_in" or "check_out",
     *   "timestamp": "2024-01-02T08:00:00" (optional, defaults to now),
     *   "device_id": "device-001" (optional)
     * }
     */
    public function clock(Request $request)
    {
        $deviceOrResponse = $this->resolveDevice($request);

        if ($deviceOrResponse instanceof JsonResponse) {
            return $deviceOrResponse;
        }
        $device = $deviceOrResponse;

        // Validate request
        $validated = $request->validate([
            'employee_identifier' => 'required|string',
            'action' => 'required|in:check_in,check_out',
            'timestamp' => 'nullable|date',
            'device_id' => 'nullable|string',
        ]);

        // Find employee by various identifiers
        $employee = Employee::where('tenant_id', $device->tenant_id)
            ->where(function ($query) use ($validated) {
                $query->where('employee_number', $validated['employee_identifier'])
                    ->orWhere('national_id', $validated['employee_identifier'])
                    ->orWhere('biometric_id', $validated['employee_identifier']);
            })
            ->first();

        if (! $employee) {
            Log::warning('Biometric: Employee not found', [
                'identifier' => $validated['employee_identifier'],
                'device_id' => $device->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'employee_not_found',
                'message' => 'Employee not found with the provided identifier',
            ], 404);
        }

        // Center isolation: prevent recording attendance across different centers
        if ($device->center_id && $employee->center_id && $employee->center_id !== $device->center_id) {
            Log::warning('Biometric: Cross-center attendance rejected', [
                'employee_id' => $employee->id,
                'employee_center_id' => $employee->center_id,
                'device_center_id' => $device->center_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'cross_center_unauthorized',
                'message' => 'Employee belongs to a different center',
            ], 403);
        }

        // Parse and validate timestamp window (UTC)
        $timestamp = $this->parseAndValidateTimestamp($validated['timestamp'] ?? null);

        if (! $timestamp) {
            return response()->json([
                'success' => false,
                'error' => 'timestamp_out_of_window',
                'message' => 'Attendance timestamp is outside the allowed synchronization window.',
            ], 422);
        }

        $date = $timestamp->format('Y-m-d');
        $time = $timestamp->format('H:i');

        // Find or create attendance record for today
        $attendance = Attendance::firstOrNew([
            'tenant_id' => $device->tenant_id,
            'center_id' => $device->center_id,
            'employee_id' => $employee->id,
            'date' => $date,
        ]);

        // Prevent duplicate/replay attendance events for the same action & timestamp
        if (
            ($validated['action'] === 'check_in' && $attendance->check_in !== null)
            || ($validated['action'] === 'check_out' && $attendance->check_out === $time)
        ) {
            return response()->json([
                'success' => false,
                'error' => 'duplicate_attendance_event',
                'message' => 'Duplicate or replayed attendance event rejected.',
            ], 409);
        }

        // Update based on action
        if ($validated['action'] === 'check_in') {
            $attendance->check_in = $time;
            $attendance->status = 'present';
        } else {
            // Always update check_out (last one wins when timestamp differs)
            $attendance->check_out = $time;
        }

        $attendance->save();

        // حساب التأخير والانصراف المبكر
        $attendance->calculateTimes();
        $attendance->save();

        // Update device last sync time
        $device->updateLastSync();

        Log::info('Biometric: Attendance recorded', [
            'employee_id' => $employee->id,
            'action' => $validated['action'],
            'time' => $time,
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => [
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->name_ar,
                'action' => $validated['action'],
                'time' => $time,
                'date' => $date,
            ],
        ]);
    }

    /**
     * استقبال دفعة من سجلات الحضور (Batch)
     *
     * POST /api/v1/attendance/batch
     */
    public function batch(Request $request)
    {
        $deviceOrResponse = $this->resolveDevice($request);

        if ($deviceOrResponse instanceof JsonResponse) {
            return $deviceOrResponse;
        }
        $device = $deviceOrResponse;

        $validated = $request->validate([
            'records' => 'required|array|max:50',
            'records.*.employee_identifier' => 'required|string',
            'records.*.action' => 'required|in:check_in,check_out',
            'records.*.timestamp' => 'required|date',
        ]);

        $results = [
            'total' => count($validated['records']),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($validated['records'] as $index => $record) {
            $employee = Employee::where('tenant_id', $device->tenant_id)
                ->where(function ($query) use ($record) {
                    $query->where('employee_number', $record['employee_identifier'])
                        ->orWhere('national_id', $record['employee_identifier'])
                        ->orWhere('biometric_id', $record['employee_identifier']);
                })
                ->first();

            if (! $employee) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'identifier' => $record['employee_identifier'],
                    'error' => 'employee_not_found',
                ];
                continue;
            }

            // Center isolation check
            if ($device->center_id && $employee->center_id && $employee->center_id !== $device->center_id) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'identifier' => $record['employee_identifier'],
                    'error' => 'cross_center_unauthorized',
                    'message' => 'Employee belongs to a different center',
                ];
                continue;
            }

            $timestamp = $this->parseAndValidateTimestamp($record['timestamp']);

            if (! $timestamp) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'identifier' => $record['employee_identifier'],
                    'error' => 'timestamp_out_of_window',
                ];
                continue;
            }

            $date = $timestamp->format('Y-m-d');
            $time = $timestamp->format('H:i');

            $attendance = Attendance::firstOrNew([
                'tenant_id' => $device->tenant_id,
                'center_id' => $device->center_id,
                'employee_id' => $employee->id,
                'date' => $date,
            ]);

            if (
                ($record['action'] === 'check_in' && $attendance->check_in !== null)
                || ($record['action'] === 'check_out' && $attendance->check_out === $time)
            ) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'identifier' => $record['employee_identifier'],
                    'error' => 'duplicate_attendance_event',
                ];
                continue;
            }

            if ($record['action'] === 'check_in') {
                $attendance->check_in = $time;
                $attendance->status = 'present';
            } else {
                $attendance->check_out = $time;
            }

            $attendance->save();

            // حساب التأخير والانصراف المبكر
            $attendance->calculateTimes();
            $attendance->save();

            $results['success']++;
        }

        $device->updateLastSync();

        return response()->json([
            'success' => true,
            'message' => 'Batch processed',
            'data' => $results,
        ]);
    }

    /**
     * التحقق من صلاحية الـ Token
     *
     * GET /api/v1/attendance/ping
     */
    public function ping(Request $request)
    {
        $deviceOrResponse = $this->resolveDevice($request);

        if ($deviceOrResponse instanceof JsonResponse) {
            return $deviceOrResponse;
        }
        $device = $deviceOrResponse;

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'data' => [
                'device_name' => $device->name,
                'center_id' => $device->center_id,
                'last_sync' => $device->last_sync_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Resolve biometric device from bearer token and verify tenant is active.
     */
    private function resolveDevice(Request $request): BiometricDevice|JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => 'missing_token',
                'message' => 'API token is required',
            ], 401);
        }

        $device = BiometricDevice::findByToken($token);

        if (! $device) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Invalid or inactive API token',
            ], 401);
        }

        $tenant = $device->tenant;

        if (! $tenant || $tenant->trashed()) {
            return response()->json([
                'success' => false,
                'error' => 'tenant_inactive',
                'message' => 'Tenant account is inactive or suspended.',
            ], 403);
        }

        $lifecycle = $tenant->getSubscriptionLifecycle();

        if ($lifecycle['state'] === SubscriptionLifecycleService::STATE_SUSPENDED || ! $lifecycle['can_operate']) {
            return response()->json([
                'success' => false,
                'error' => 'tenant_suspended',
                'message' => 'Tenant subscription is suspended or inactive.',
            ], 403);
        }

        return $device;
    }

    /**
     * Parse timestamp and enforce a bounded UTC synchronization window (-7 days .. +15 minutes).
     */
    private function parseAndValidateTimestamp(?string $rawTimestamp): ?Carbon
    {
        $timestamp = $rawTimestamp !== null
            ? Carbon::parse($rawTimestamp)
            : now('UTC');

        $utcTimestamp = $timestamp->copy()->utc();
        $nowUtc = now('UTC');

        if ($utcTimestamp->lt($nowUtc->copy()->subDays(7)) || $utcTimestamp->gt($nowUtc->copy()->addMinutes(15))) {
            return null;
        }

        return $timestamp;
    }
}
