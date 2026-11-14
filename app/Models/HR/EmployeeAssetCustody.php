<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\CenterScoped;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAssetCustody extends Model
{
    use CenterScoped, SoftDeletes;

    protected $table = 'hr_employee_asset_custodies';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_DAMAGED = 'damaged';

    public const STATUS_LOST = 'lost';

    public const CATEGORIES = [
        'tool_kit',
        'diagnostic_device',
        'vehicle',
        'sim_card',
        'laptop',
        'uniform',
        'other',
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'employee_id',
        'asset_name',
        'category',
        'serial_number',
        'estimated_value',
        'condition_on_issue',
        'condition_on_return',
        'issued_date',
        'returned_date',
        'status',
        'issue_signature',
        'return_signature',
        'notes',
        'created_by',
        'returned_by',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'issued_date' => 'date:Y-m-d',
        'returned_date' => 'date:Y-m-d',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
