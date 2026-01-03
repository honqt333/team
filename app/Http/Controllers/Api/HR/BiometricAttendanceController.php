<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Employee;
use Carbon\Carbon;
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
        // Get API token from header
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'missing_token',
                'message' => 'API token is required'
            ], 401);
        }

        // Find device by token
        $device = BiometricDevice::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Invalid or inactive API token'
            ], 401);
        }

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

        if (!$employee) {
            Log::warning('Biometric: Employee not found', [
                'identifier' => $validated['employee_identifier'],
                'device_id' => $device->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'employee_not_found',
                'message' => 'Employee not found with the provided identifier'
            ], 404);
        }

        // Parse timestamp
        $timestamp = isset($validated['timestamp']) 
            ? Carbon::parse($validated['timestamp']) 
            : now();

        $date = $timestamp->format('Y-m-d');
        $time = $timestamp->format('H:i');

        // Find or create attendance record for today
        $attendance = Attendance::firstOrNew([
            'tenant_id' => $device->tenant_id,
            'center_id' => $device->center_id,
            'employee_id' => $employee->id,
            'date' => $date,
        ]);

        // Update based on action
        if ($validated['action'] === 'check_in') {
            // Only set check_in if not already set
            if (!$attendance->check_in) {
                $attendance->check_in = $time;
                $attendance->status = 'present';
            }
        } else {
            // Always update check_out (last one wins)
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
            ]
        ]);
    }

    /**
     * استقبال دفعة من سجلات الحضور (Batch)
     * 
     * POST /api/v1/attendance/batch
     */
    public function batch(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'missing_token',
                'message' => 'API token is required'
            ], 401);
        }

        $device = BiometricDevice::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Invalid or inactive API token'
            ], 401);
        }

        $validated = $request->validate([
            'records' => 'required|array|max:100',
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

            if (!$employee) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'identifier' => $record['employee_identifier'],
                    'error' => 'employee_not_found',
                ];
                continue;
            }

            $timestamp = Carbon::parse($record['timestamp']);
            $date = $timestamp->format('Y-m-d');
            $time = $timestamp->format('H:i');

            $attendance = Attendance::firstOrNew([
                'tenant_id' => $device->tenant_id,
                'center_id' => $device->center_id,
                'employee_id' => $employee->id,
                'date' => $date,
            ]);

            if ($record['action'] === 'check_in') {
                if (!$attendance->check_in) {
                    $attendance->check_in = $time;
                    $attendance->status = 'present';
                }
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
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['success' => false, 'error' => 'missing_token'], 401);
        }

        $device = BiometricDevice::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'error' => 'invalid_token'], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'data' => [
                'device_name' => $device->name,
                'center_id' => $device->center_id,
                'last_sync' => $device->last_sync_at?->toIso8601String(),
            ]
        ]);
    }
}
