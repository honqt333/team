<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPurchaseAllocation extends Model
{
    use CenterScoped, HasFactory, TenantScoped;

    public const TYPE_EXTERNAL_PART = 'external_part';

    public const TYPE_WAREHOUSE_PART = 'warehouse_part';

    public const TYPE_SUPPLIER_SERVICE = 'supplier_service';

    public const TYPES = [
        self::TYPE_EXTERNAL_PART,
        self::TYPE_WAREHOUSE_PART,
        self::TYPE_SUPPLIER_SERVICE,
    ];

    protected $table = 'work_order_purchase_allocations';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'work_order_id',
        'purchase_invoice_id',
        'purchase_invoice_line_id',
        'work_order_item_part_id',
        'work_order_item_id',
        'type',
        'item_description',
        'allocated_qty',
        'unit_cost',
        'tax_amount',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'allocated_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<PurchaseInvoice, $this>
     */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * @return BelongsTo<PurchaseInvoiceLine, $this>
     */
    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceLine::class);
    }

    /**
     * @return BelongsTo<WorkOrderItemPart, $this>
     */
    public function targetPart(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItemPart::class, 'work_order_item_part_id');
    }

    /**
     * @return BelongsTo<WorkOrderItem, $this>
     */
    public function targetService(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class, 'work_order_item_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Center, $this>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
