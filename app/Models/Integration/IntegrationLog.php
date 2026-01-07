<?php

namespace App\Models\Integration;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'integration_id',
        'action',
        'request',
        'response',
        'status',
        'error_message',
        'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Log an integration call.
     */
    public static function log(
        int $integrationId,
        string $action,
        array $request = [],
        array $response = [],
        string $status = 'success',
        ?string $error = null,
        ?int $responseTime = null,
        ?int $tenantId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'integration_id' => $integrationId,
            'action' => $action,
            'request' => $request,
            'response' => $response,
            'status' => $status,
            'error_message' => $error,
            'response_time_ms' => $responseTime,
        ]);
    }
}
