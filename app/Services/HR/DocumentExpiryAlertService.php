<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\InternalNotification;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class DocumentExpiryAlertService
{
    public const THRESHOLD_DAYS = [60, 30, 7];

    /**
     * Scan active employees and documents for upcoming expiries (60, 30, 7 days)
     * and dispatch deduplicated InternalNotification alerts to HR managers.
     */
    public function dispatchAlerts(?int $tenantId = null, ?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $targetDates = collect(self::THRESHOLD_DAYS)
            ->mapWithKeys(fn (int $days) => [$today->copy()->addDays($days)->toDateString() => $days]);

        $employees = Employee::withoutGlobalScopes()
            ->where('status', 'active')
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['documents' => fn ($q) => $q->whereNotNull('expiry_date')])
            ->get();

        $recipientsByTenant = [];
        $sentCount = 0;

        foreach ($employees as $employee) {
            $alerts = $this->collectEmployeeAlerts($employee, $targetDates);

            if ($alerts->isEmpty()) {
                continue;
            }

            if (! array_key_exists($employee->tenant_id, $recipientsByTenant)) {
                $recipientsByTenant[$employee->tenant_id] = $this->resolveHrRecipients((int) $employee->tenant_id);
            }

            $recipients = $recipientsByTenant[$employee->tenant_id];

            if ($recipients->isEmpty()) {
                continue;
            }

            foreach ($alerts as $alert) {
                foreach ($recipients as $recipient) {
                    $alreadySent = InternalNotification::withoutGlobalScopes()
                        ->where('tenant_id', $employee->tenant_id)
                        ->where('user_id', $recipient->id)
                        ->where('type', 'hr.expiry_alert')
                        ->where('data->alert_key', $alert['alert_key'])
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    InternalNotification::create([
                        'tenant_id' => $employee->tenant_id,
                        'user_id' => $recipient->id,
                        'type' => 'hr.expiry_alert',
                        'title' => $alert['title'],
                        'body' => $alert['body'],
                        'icon' => 'alert-triangle',
                        'action_url' => route('app.hr.employees.show', ['employee' => $employee->id], false),
                        'data' => [
                            'alert_key' => $alert['alert_key'],
                            'employee_id' => $employee->id,
                            'field' => $alert['field'],
                            'days_remaining' => $alert['days'],
                            'expiry_date' => $alert['expiry_date'],
                        ],
                    ]);

                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    private function collectEmployeeAlerts(Employee $employee, Collection $targetDates): Collection
    {
        $alerts = collect();
        $fields = [
            'national_id_expiry' => 'national_id',
            'passport_expiry' => 'passport',
            'insurance_expiry' => 'insurance',
            'contract_end_date' => 'contract',
        ];

        $empName = $employee->name_ar ?: $employee->name_en ?: $employee->employee_number;

        foreach ($fields as $column => $labelKey) {
            $rawDate = $employee->{$column};

            if (! $rawDate) {
                continue;
            }

            $dateStr = $rawDate instanceof Carbon
                ? $rawDate->toDateString()
                : Carbon::parse((string) $rawDate)->toDateString();

            if (! $targetDates->has($dateStr)) {
                continue;
            }

            $days = (int) $targetDates->get($dateStr);
            $docLabel = __('hr.expiry_alerts.types.'.$labelKey);

            $alerts->push([
                'field' => $column,
                'days' => $days,
                'expiry_date' => $dateStr,
                'alert_key' => "emp:{$employee->id}:{$column}:{$days}d:{$dateStr}",
                'title' => __('hr.expiry_alerts.title', ['type' => $docLabel, 'days' => $days]),
                'body' => __('hr.expiry_alerts.body', [
                    'employee' => $empName,
                    'type' => $docLabel,
                    'date' => $dateStr,
                    'days' => $days,
                ]),
            ]);
        }

        /** @var EmployeeDocument $doc */
        foreach ($employee->documents as $doc) {
            if (! $doc->expiry_date) {
                continue;
            }

            $dateStr = $doc->expiry_date instanceof Carbon
                ? $doc->expiry_date->toDateString()
                : Carbon::parse((string) $doc->expiry_date)->toDateString();

            if (! $targetDates->has($dateStr)) {
                continue;
            }

            $days = (int) $targetDates->get($dateStr);
            $docLabel = $doc->title ?: __('hr.expiry_alerts.types.document');

            $alerts->push([
                'field' => "document:{$doc->id}",
                'days' => $days,
                'expiry_date' => $dateStr,
                'alert_key' => "doc:{$doc->id}:{$days}d:{$dateStr}",
                'title' => __('hr.expiry_alerts.title', ['type' => $docLabel, 'days' => $days]),
                'body' => __('hr.expiry_alerts.body', [
                    'employee' => $empName,
                    'type' => $docLabel,
                    'date' => $dateStr,
                    'days' => $days,
                ]),
            ]);
        }

        return $alerts;
    }

    private function resolveHrRecipients(int $tenantId): Collection
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($tenantId);

        try {
            return User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get()
                ->filter(function (User $user) {
                    $user->unsetRelation('roles')->unsetRelation('permissions');

                    return $user->hasRole('super_admin')
                        || $user->can(Permissions::HR_EMPLOYEES_VIEW);
                })
                ->values();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
