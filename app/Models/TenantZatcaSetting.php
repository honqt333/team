<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantZatcaSetting extends Model
{
    use TenantScoped;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_CSR_READY = 'csr_ready';

    public const STATUS_COMPLIANCE_PASSED = 'compliance_passed';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const ENV_SANDBOX = 'sandbox';

    public const ENV_SIMULATION = 'simulation';

    public const ENV_PRODUCTION = 'production';

    protected $fillable = [
        'tenant_id',
        'qr_enabled',
        'environment',
        'onboarding_status',
        'egs_serial_number',
        'csr_content',
        'private_key',
        'compliance_csid',
        'compliance_secret',
        'production_csid',
        'production_secret',
        'production_csid_expires_at',
        'last_onboarding_error',
        'onboarded_at',
    ];

    protected $casts = [
        'qr_enabled' => 'boolean',
        'production_csid_expires_at' => 'datetime',
        'onboarded_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPhase2Active(): bool
    {
        return $this->onboarding_status === self::STATUS_ACTIVE && ! empty($this->production_csid);
    }
}
