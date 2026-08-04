<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 11 — per-appointment audit log.
 *
 * Mirrors the work-order-activities pattern.
 * One row per "thing the staff did to this
 * appointment's communications". The
 * communications dashboard uses this to show
 * operators a "user X retried message Y at
 * time T" history without joining an external
 * audit package.
 *
 * The controller that writes here is
 * responsible for the privacy contract
 * documented in the migration: the description
 * and changes columns must NEVER carry the
 * customer's phone, the SMS body, or any
 * provider error message.
 */
class AppointmentActivity extends Model
{
    use HasFactory;
    // Note: TenantScoped is intentionally NOT
    // used here. Audit logs are written from
    // controllers that already filter on
    // tenant_id at the call site, and the
    // cross-tenant audit scanner is
    // straightforward to add later by passing
    // the tenant id explicitly. We do not want
    // a global scope that would surprise the
    // staff UI when reading this log.

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'user_id',
        'action',
        'description',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
