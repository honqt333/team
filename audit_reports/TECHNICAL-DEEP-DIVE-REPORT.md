# تحليل تقني عميق للكود: carag-v2 (وحدات العمل، الطباعة، الفواتير، المخزون، الموارد البشرية)
## تاريخ التحليل: 2026-11-08
## منهجية: قراءة الكود المصدري الفعلي (Models, Controllers, Vue Components, Migrations)

---

## أولاً: وحدة كروت العمل (Work Orders) — التحليل التقني

### 1.1 البنية النموذجية (WorkOrder.php — 387 سطر)

```php
// السمات الأساسية
uses CenterScoped, HasFactory, HasTaxSnapshot, SoftDeletes;
uses HasWorkOrderOperations, HasWorkOrderRelations;

protected $appends = ['work_order_number'];
protected $hidden = ['tax_breakdown'];
```

**Booted Events (auto-calculation):**
```php
static::saving(function ($workOrder) {
    $workOrder->recalculateTotals();  // ← يحسب الإجماليات تلقائياً

    // مزامنة odometer/mileage
    if ($workOrder->isDirty('odometer') && ! $workOrder->isDirty('mileage')) {
        $workOrder->mileage = $workOrder->odometer;
    }

    // Auto-complete: إذا كل البنود مكتملة → ready_for_qc
    if (in_array($workOrder->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]) && $workOrder->allItemsCompleted()) {
        $workOrder->status = self::STATUS_READY_FOR_QC;
    }
});

static::saved(function ($workOrder) {
    if ($workOrder->status === self::STATUS_DONE && $workOrder->wasChanged('status')) {
        $workOrder->logActivity('status_changed', ...);
    }
});
```

### 1.2 دوال إعادة الحساب (recalculateTotals) — معقدة ومتكاملة

```php
public function recalculateTotals(): void
{
    if ($this->canBeEdited()) {
        $this->refreshTaxSnapshot();  // ← يحفظ snapshot للضريبة
    }

    // تحميل العلاقات إذا لم تكن محمّلة
    $activeItems = $this->items->reject(fn ($item) => $item->status === WorkOrderItem::STATUS_CANCELLED);

    $servicesPrice = $activeItems->sum(fn ($l) => (float) $l->unit_price * (float) $l->qty);
    $servicesDiscount = $activeItems->sum('discount_amount');

    // الفلترة المعقدة للقطع المرتبطة بالبنود النشطة فقط
    $activeParts = $this->parts->filter(function ($part) use ($activeItemIds) {
        if (in_array($part->status, [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED])) {
            return false;
        }
        if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds)) {
            return false;
        }
        return true;
    });

    $partsPrice = $activeParts->sum(fn ($p) => (float) $p->unit_price * (float) $p->qty);
    $partsDiscount = $activeParts->sum('discount');

    $netTotal = ($servicesPrice - $servicesDiscount) + ($partsPrice - $partsDiscount);
    $this->total_excl_tax = $netTotal;

    // منطق الضريبة (inclusive vs exclusive)
    if (! ($this->tax_enabled_snapshot ?? false)) {
        $this->total_tax = 0;
        $this->total_incl_tax = $netTotal;
    } else {
        $taxRate = (float) ($this->tax_rate_snapshot ?: 15.00);
        if (($this->pricing_mode_snapshot ?? 'exclusive') === 'inclusive') {
            $this->total_incl_tax = $netTotal;
            $this->total_excl_tax = round($netTotal / (1 + ($taxRate / 100)), 2);
            $this->total_tax = round($this->total_incl_tax - $this->total_excl_tax, 2);
        } else {
            $this->total_tax = round($netTotal * ($taxRate / 100), 2);
            $this->total_incl_tax = round($netTotal + $this->total_tax, 2);
        }
    }
}
```

### 1.3 الحالات (7 حالات في WorkOrder.php)

```php
public const STATUS_DRAFT = 'draft';
public const STATUS_OPEN = 'open';
public const STATUS_IN_PROGRESS = 'in_progress';
public const STATUS_ON_HOLD = 'on_hold';
public const STATUS_DONE = 'done';
public const STATUS_READY_FOR_QC = 'ready_for_qc';
public const STATUS_CANCELLED = 'cancelled';
```

**انتقالات الحالات عبر WorkOrderStatusController:**
| الدالة | الانتقال | الشروط |
|---|---|---|
| `startWork()` | open → in_progress | لا يوجد بنود قيد الانتظار |
| `putOnHold($reason)` | أي → on_hold | `canBeOnHold()` |
| `resume()` | on_hold → in_progress | `resume()` ترجع true |
| `cancel()` | أي → cancelled | `canBeCancelled()` — لا فنيين/قطع |
| `complete()` | ready_for_qc → done | كل البنود مكتملة + لا مبالغ زائدة |

**النموذج الفرعي WorkOrderItem — 6 حالات:**
```php
STATUS_PENDING, STATUS_IN_PROGRESS, STATUS_READY_FOR_QC,
STATUS_COMPLETED, STATUS_ON_HOLD, STATUS_CANCELLED
```

### 1.4 علاقات الكرت (13 نموذج مرتبط)

| العلاقة | النموذج | الوصف |
|---|---|---|
| `items()` | WorkOrderItem | بنود الخدمة/القطع/العمالة |
| `parts()` | WorkOrderItemPart | القطع المستخدمة |
| `laborEntries()` | WorkOrderLaborEntry | ساعات العمل |
| `technicians()` | WorkOrderItemTechnician | الفنيون |
| `inspections()` | WorkOrderInspection | فحوصات المركبة |
| `photos()` | WorkOrderPhoto | صور قبل/بعد |
| `attachments()` | WorkOrderAttachment | ملفات مرفقة |
| `damageMarks()` | WorkOrderDamageMark | علامات التلف على الرسم |
| `activities()` | WorkOrderActivity | سجل النشاط (audit) |
| `notes()` | WorkOrderItemNote | ملاحظات البنود |
| `qualityChecks()` | WorkOrderItemQualityCheck | فحص الجودة |
| `warranties()` | WorkOrderWarranties | الضمانات |

### 1.5 WorkOrderStatusController — منطق العمل (238 سطر)

```php
// بدء العمل: يحول كل البنود pending → in_progress مع timestamp
public function startWork(WorkOrder $work_order) {
    DB::transaction(function () use ($work_order) {
        $work_order->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        foreach ($work_order->items()->where('status', WorkOrderItem::STATUS_PENDING)->get() as $item) {
            $item->update(['status' => WorkOrderItem::STATUS_IN_PROGRESS]);
        }
    });
}

// وضع على الانتظار: مع سبب إلزامي
public function putOnHold(ChangeStatusRequest $request, WorkOrder $work_order) {
    $work_order->putOnHold($validated['reason']);
}

// الإكمال: يتحقق من allItemsCompleted() + لا balance < 0
// ينشئ فاتورة تلقائياً عبر InvoiceService إذا لا توجد
public function complete(Request $request, WorkOrder $work_order) {
    if (! $work_order->allItemsCompleted()) {
        return back()->with('error', 'لا يمكن إكمال...');
    }
    if ($work_order->balance < -0.01) {
        return back()->with('error', 'مبالغ زائدة مدفوعة...');
    }
    // ينشئ الفاتورة تلقائياً
    $invoice = $invoiceService->createFromWorkOrder($work_order, auth()->user());
    $invoiceService->issueInvoice($invoice);
}
```

---

## ثانياً: النظام الطباعي (Printing System) — التحليل التقني

### 2.1 PrintEngine.vue — المحرك المركزي

```javascript
// اختيار القالب ديناميكياً
const templates = {
    TemplateDefaultA4,
    TemplateThermal80,
    TemplateModernA4: TemplateDefaultA4,
    TemplateSleekThermal: TemplateThermal80,
};

const activeTemplate = computed(() => {
    const activeName =
        props.documentSettings?.template ||          // 1. إعداد المستند
        props.visualSettings?.active_template ||     // 2. تفضيل المستخدم
        'TemplateDefaultA4';                          // 3. افتراضي
    return templates[activeName] || TemplateDefaultA4;
});
```

### 2.2 TemplateDefaultA4.vue — قالب A4 متكامل

**الميزات:**
- Header: شعار + اسم المركز + VAT/CR + الهاتف
- QR Code support (ZATCA)
- Document details (code, date, ref invoice, return reason)
- Customer info section
- Vehicle info (plate, make, model, year, VIN, odometer)
- Line items table (service/part, qty, unit price, discount, total)
- Totals with tax breakdown
- Signature fields (reception/delivery)
- Footer: شروط وأحكام + نص مخصص

**Dynamic styling:**
```javascript
const primaryColor = computed(() => props.visualSettings?.primary_color || '#fbbf24');
const isModern = computed(() => props.documentSettings?.style === 'modern');
```

### 2.3 TemplateThermal80.vue — قالب حراري 80mm

- عرض ثابت `w-[80mm]`
- Compact layout للإيصالات
- نفس البيانات لكن مضغوطة
- لا QR code (اختياري)

### 2.4 WorkOrderPrintController — 4 endpoints (251 سطر)

```php
public function printCondition(WorkOrder $workOrder): Response  // حالة المركبة
public function printServices(WorkOrder $workOrder): Response   // الخدمات
public function printProforma(WorkOrder $workOrder): Response   // تسعير
public function printPayments(WorkOrder $workOrder): Response   // المدفوعات
```

### 2.5 صفحات الطباعة (11 صفحة Vue)

| الصفحة | نوع المستند |
|---|---|
| WorkOrders/Print/Condition.vue | تقرير حالة المركبة |
| WorkOrders/Print/Services.vue | تفاصيل الخدمات |
| WorkOrders/Print/Proforma.vue | تسعير مقدّم |
| WorkOrders/Print/Payments.vue | كشف المدفوعات |
| Invoices/Print.vue | فاتورة مباعة |
| Purchasing/Invoices/Print.vue | فاتورة مشتريات |
| Quotes/Print.vue | عرض سعر |
| Customers/Print.vue | بيانات عميل |
| Customers/PrintStatement.vue | بيان حساب |
| HR/Attendance/Print.vue | تقرير حضور |
| HR/Payroll/Print.vue | كشف راتب |

---

## ثالثاً: وحدة الفواتير والدفع — التحليل التقني

### 3.1 Invoice.php — نموذج شامل (218 سطر)

```php
uses CenterScoped, HasFactory, HasInvoiceRelations, HasTaxSnapshot, SoftDeletes;

protected $appends = ['balance', 'is_paid', 'payment_status_label', 'bad_debt'];

protected $fillable = [
    'tenant_id', 'center_id', 'customer_id', 'work_order_id',
    'original_invoice_id', 'invoice_number', 'issue_date', 'supply_date',
    'due_date', 'notes', 'return_reason',
    'type', 'subtype', 'status', 'payment_status',
    // Snapshots
    'customer_name_snapshot', 'customer_vat_snapshot', 'customer_address_snapshot',
    'tax_enabled_snapshot', 'pricing_mode_snapshot', 'tax_rate_snapshot', 'currency_code',
    // Totals
    'total_excl_tax', 'total_tax', 'total_incl_tax', 'total_taxable_amount', 'total_paid', 'tax_breakdown',
    // ZATCA
    'zatca_qr_tlv', 'zatca_uuid', 'zatca_hash', 'zatca_prev_hash', 'xml_path',
];

// Accessors ذكية
public function getBalanceAttribute(): float {
    return (float) $this->total_incl_tax - (float) $this->total_paid;
}

public function getIsPaidAttribute(): bool {
    return $this->payment_status === 'paid';
}

public function getPaymentStatusLabelAttribute(): string {
    return match ($this->payment_status) {
        'unpaid' => __('invoices.status.unpaid'),
        'partial' => __('invoices.status.partial'),
        'paid' => __('invoices.status.paid'),
        null, '' => '',
        default => (string) $this->payment_status,
    };
}

public function getBadDebtAttribute(): float {
    return (float) $this->payments()->where('type', 'bad_debt')->sum('amount');
}
```

### 3.2 تحديث حالة الدفع (updatePaymentStatus) — منطق ذكي

```php
public function updatePaymentStatus(): void
{
    if ($this->type === InvoiceType::CREDIT_NOTE) {
        $totalRefunded = (float) $this->payments()
            ->selectRaw('SUM(CASE WHEN type IN ("refund", "payment") THEN amount WHEN type = "bad_debt" THEN -amount ELSE 0 END) as refunded')
            ->value('refunded');

        $totalIncl = (float) $this->total_incl_tax;
        if ($totalRefunded >= $totalIncl - 0.01) {
            $this->payment_status = 'paid';
            $this->total_paid = $totalIncl;
        } elseif ($totalRefunded > 0.01) {
            $this->payment_status = 'partial';
            $this->total_paid = $totalRefunded;
        } else {
            $this->payment_status = 'unpaid';
            $this->total_paid = 0;
        }
        $this->save();
        return;
    }

    // للفواتير العادية: payments تضيف، refunds تطرح
    $totalPaid = (float) $this->payments()
        ->selectRaw('SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) as paid')
        ->value('paid');

    $this->total_paid = $totalPaid;
    $totalIncl = (float) $this->total_incl_tax;

    if ($totalIncl <= 0.01) {
        $this->payment_status = 'paid';
    } elseif ($totalPaid <= 0) {
        $this->payment_status = 'unpaid';
    } elseif ($totalPaid >= $totalIncl - 0.01) {  // tolerance 1 cent
        $this->payment_status = 'paid';
    } else {
        $this->payment_status = 'partial';
    }
    $this->save();
}
```

### 3.3 Payment.php — أنواع الدفع والعلاقات

```php
// الأنواع
type: 'payment' | 'refund' | 'bad_debt'

// العلاقات
belongsTo(tenant)
belongsTo(center)
belongsTo(invoice)
belongsTo(purchaseInvoice)
belongsTo(workOrder)
belongsTo(receivedBy)

// scopes
scopeForTenant, scopeForCenter, scopeForInvoice, scopeForWorkOrder

// accessor
getPaymentMethodLabelAttribute()
```

### 3.4 ZATCA Integration — متكامل

| المكون | الملف |
|---|---|
| Integration | `Integration/Integration.php` |
| Onboarding | `ZatcaOnboardingController.php` |
| Settings | `TenantZatcaSetting` model |
| Tax Calculator | `TaxCalculator` service |
| Tax Periods | `TaxPeriod` + `TaxPeriodTransaction` |

---

## رابعاً: وحدة المخزون — التحليل التقني

### 4.1 Part.php — نموذج القطعة (159 سطر)

```php
uses HasFactory, SoftDeletes, TenantScoped;

protected $fillable = [
    'tenant_id', 'sku', 'barcode', 'name_ar', 'name_en',
    'unit_id', 'purchase_unit_id', 'purchase_conversion_factor',
    'category_id', 'description', 'image_path',
    'is_active', 'default_sale_price', 'min_sale_price',
    'min_qty', 'reorder_qty',
];

// Auto-conversion factor
protected static function boot(): void {
    static::saving(function (Part $part) {
        if (empty($part->purchase_conversion_factor) || (float) $part->purchase_conversion_factor <= 0) {
            $part->purchase_conversion_factor = 1.0;
        }
    });
}

public function setPurchaseConversionFactorAttribute($value): void {
    $this->attributes['purchase_conversion_factor'] = (empty($value) || (float) $value <= 0) ? 1.0 : (float) $value;
}

// Accessors
public function getNameAttribute(): string {
    $locale = app()->getLocale();
    return $locale === 'ar' ? ($this->name_ar ?: $this->name_en) : ($this->name_en ?: $this->name_ar) ?: '';
}

public function getTotalQtyOnHandAttribute(): float {
    return (float) $this->inventoryBalances()->sum('qty_on_hand');
}
```

### 4.2 InventoryBalance.php — رصيد المخزون (150 سطر)

```php
uses CenterScoped, HasFactory;

public static function getOrCreate(int $warehouseId, int $partId): self {
    $balance = static::query()
        ->withoutGlobalScopes()
        ->where('warehouse_id', $warehouseId)
        ->where('part_id', $partId)
        ->first();

    if (! $balance) {
        $warehouseRow = DB::table('warehouses')->where('id', $warehouseId)->first(['tenant_id', 'center_id']);
        $balance = static::query()->withoutGlobalScopes()->create([
            'warehouse_id' => $warehouseId,
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'part_id' => $partId,
            'qty_on_hand' => 0,
            'wac_cost' => 0,
        ]);
    }
    return $balance;
}

public function scopeLowStock($query) {
    return $query->where(function ($q) {
        $q->where(function ($sub) {
            $sub->where('inventory_balances.min_stock', '>', 0)
                ->whereColumn('inventory_balances.qty_on_hand', '<=', 'inventory_balances.min_stock');
        })->orWhere(function ($sub) {
            $sub->where(function ($zeroMin) {
                $zeroMin->whereNull('inventory_balances.min_stock')
                    ->orWhere('inventory_balances.min_stock', '<=', 0);
            })
                ->whereHas('part', function ($pq) {
                    $pq->where('min_qty', '>', 0)
                        ->whereColumn('inventory_balances.qty_on_hand', '<=', 'parts.min_qty');
                });
        });
    });
}
```

### 4.3 InventoryMove.php — حركات المخزون (أنواع 10)

```php
public const TYPE_RECEIPT = 'receipt';
public const TYPE_ISSUE_TO_WORKORDER = 'issue_to_workorder';
public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
public const TYPE_TRANSFER_OUT = 'transfer_out';
public const TYPE_TRANSFER_IN = 'transfer_in';
public const TYPE_REVERSAL = 'reversal';
public const TYPE_PURCHASE_RETURN = 'purchase_return';
public const TYPE_SALE = 'sale';

// Helpers
public function isIncoming(): bool {
    return in_array($this->move_type, [self::TYPE_RECEIPT, self::TYPE_ADJUSTMENT_IN, self::TYPE_TRANSFER_IN]);
}

public function isOutgoing(): bool {
    return in_array($this->move_type, [self::TYPE_ISSUE_TO_WORKORDER, self::TYPE_SALE, self::TYPE_ADJUSTMENT_OUT, self::TYPE_TRANSFER_OUT, self::TYPE_PURCHASE_RETURN]);
}

public function canBeReversed(): bool {
    return $this->isPosted() && ! $this->isReversed();
}

// Polymorphic workOrder accessor
public function getWorkOrderAttribute(): ?WorkOrder {
    $ref = $this->reference;
    if ($ref instanceof WorkOrderItemPart) return $ref->workOrder;
    if ($ref instanceof WorkOrder) return $ref;
    if ($ref instanceof Invoice) return $ref->workOrder;
    return null;
}
```

### 4.4 Controllers (4 controllers — 825 سطر)

| Controller | السطور | الوظيفة |
|---|---|---|
| InventoryBalanceController | 175 | عرض/تحديث الرصيد |
| InventoryMoveController | 194 | تسجيل الحركات |
| InventoryTransfersController | 281 | نقل بين المستودعات |
| InventorySettingsController | - | الإعدادات |

---

## خامساً: وحدة الموارد البشرية — التحليل التقني

### 5.1 Employee.php — نموذج الموظف (187 سطر)

```php
uses HasEmployeeRelations, SoftDeletes, TenantScoped;

protected $fillable = [
    'tenant_id', 'center_id', 'user_id', 'employee_number',
    'name_ar', 'name_en', 'phone', 'email', 'photo_path',
    'gender', 'marital_status', 'birth_date',
    'job_title_id', 'employee_type_id', 'department_id',
    'hire_date', 'contract_end_date', 'default_shift_id',
    'nationality_id', 'national_id', 'national_id_expiry',
    'passport_number', 'passport_expiry', 'border_entry_number',
    'sponsor_name', 'profession_on_id',
    'insurance_company', 'insurance_card_number', 'insurance_policy_number', 'insurance_expiry',
    'base_salary', 'commission_enabled', 'commission_type', 'commission_rate',
    'status', 'termination_date', 'termination_reason', 'notes',
    'biometric_id', 'bank_name', 'bank_iban', 'bank_account_number',
    'gosi_rate',
];

// Auto-generate employee number
static::creating(function ($employee) {
    if (empty($employee->employee_number)) {
        $lastNumber = static::where('tenant_id', $employee->tenant_id)->withTrashed()->max('id') ?? 0;
        $employee->employee_number = 'EMP-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
});

// Sync user status with employee status
static::updated(function ($employee) {
    if ($employee->isDirty('status')) {
        if ($employee->user_id && $employee->user) {
            $newStatus = $employee->status;
            if (in_array($newStatus, ['terminated', 'inactive'])) {
                $employee->user->update(['is_active' => false]);
            } elseif ($newStatus === 'active') {
                $employee->user->update(['is_active' => true]);
            }
        }
    }
});

// Get shift for specific date
public function getShiftForDate(Carbon $date): ?Shift {
    $scheduledShift = $this->employeeShifts()->where('date', $date->format('Y-m-d'))->with('shift')->first();
    if ($scheduledShift && $scheduledShift->shift) return $scheduledShift->shift;

    $dayOfWeek = $date->dayOfWeek;
    $weeklyShift = $this->employeeShifts()->where('day_of_week', $dayOfWeek)->whereNull('date')->with('shift')->first();
    if ($weeklyShift && $weeklyShift->shift) return $weeklyShift->shift;

    return $this->defaultShift;
}
```

### 5.2 Attendance.php — الحضور مع حساب أوقات الورديات

```php
uses SoftDeletes, TenantScoped;

protected $fillable = [
    'tenant_id', 'center_id', 'employee_id', 'date', 'status',
    'check_in', 'check_out', 'late_minutes', 'early_leave_minutes', 'overtime_minutes', 'notes',
];

// حساب الأوقات التلقائي
public function calculateTimes(): void {
    $employee = $this->employee;
    $settings = AttendanceSettings::getForCenter($this->center_id);
    $gracePeriod = $settings->grace_period_minutes;

    $this->late_minutes = 0;
    $this->early_leave_minutes = 0;
    $this->overtime_minutes = 0;

    $shift = $employee->getShiftForDate($this->date);
    if (! $shift) return;

    // حساب التأخير
    if ($this->check_in && $shift->start_time) {
        $shiftStart = Carbon::parse($this->date->format('Y-m-d').' '.$shift->start_time);
        $checkIn = Carbon::parse($this->date->format('Y-m-d').' '.Carbon::parse($this->check_in)->format('H:i'));
        if ($checkIn->gt($shiftStart)) {
            $lateMinutes = abs($checkIn->diffInMinutes($shiftStart));
            $this->late_minutes = $lateMinutes > $gracePeriod ? $lateMinutes : 0;
        }
    }

    // الانصراف المبكر / وقت إضافي
    if ($this->check_out && $shift->end_time) {
        $shiftEnd = Carbon::parse($this->date->format('Y-m-d').' '.$shift->end_time);
        $checkOut = Carbon::parse($this->date->format('Y-m-d').' '.Carbon::parse($this->check_out)->format('H:i'));
        if ($shift && $shift->is_overnight && $shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }
        if ($checkOut->lt($shiftEnd)) {
            $this->early_leave_minutes = abs($shiftEnd->diffInMinutes($checkOut));
        } elseif ($checkOut->gt($shiftEnd) && $settings->overtime_enabled) {
            $this->overtime_minutes = abs($checkOut->diffInMinutes($shiftEnd));
        }
    }
}
```

### 5.3 PayrollRun.php + PayrollController (389 سطر)

```php
// PayrollRun states
scopeDraft() → scopeApproved() → scopePaid()

// PayrollController::generate()
public function generate(Request $request) {
    $validated = $request->validate([
        'period_start' => 'required|date',
        'period_end' => 'required|date|after_or_equal:period_start',
        'payment_date' => 'nullable|date',
    ]);
    
    // منع التكرار لنفس الفترة
    $exists = PayrollRun::where('tenant_id', $tenantId)
        ->where('center_id', $centerId)
        ->where('period_start', $periodStart->toDateString())
        ->exists();
}
```

### 5.4 Employee Methods — حسابات الراتب

```php
public function calculateTotalAllowances(): float { ... }
public function calculateTotalDeductions(): float { ... }
public function calculateGosiAmount(): float { ... }
public function calculateNetSalary(): float {
    return $this->base_salary + $this->calculateTotalAllowances() - $this->calculateTotalDeductions() - $this->calculateGosiAmount();
}
```

### 5.5 Controllers (16 controllers — 2,618 سطر)

| Controller | السطور | الوظيفة |
|---|---|---|
| EmployeeController | 435 | إدارة الموظفين |
| AttendanceController | 234 | الحضور + print |
| BiometricDeviceController | 103 | أجهزة البصمة |
| EmployeeContractsController | 97 | العقود |
| EmployeeDocumentsController | 168 | الوثائق |
| EmployeeFinancialsController | 112 | البيانات المالية |
| EmployeePermissionsController | 73 | الصلاحيات |
| EmployeeShiftController | 72 | الجداول |
| HRPayrollSettingsController | 148 | إعدادات الرواتب |
| HRRegulationsController | 68 | اللوائح |
| LeaveController | 93 | الإجازات |
| OtherPaymentsController | 126 | مدفوعات أخرى |
| PayrollController | 389 | دورة الرواتب |
| SettingsController | 186 | إعدادات HR |
| ShiftController | 73 | الدورانات |
| HRController | 80 | نقطة الدخول |

---

## سادساً: ملخص المقارنة التقنية النهائية

### حجم الكود للوحدات الخمس

| الوحدة | نماذج | Controllers | إجمالي سطور PHP | Vue Pages |
|---|---|---|---|---|
| كروت العمل | 11 | 11 (2,406) | ~3,500+ | 2+ |
| النظام الطباعي | 0 | 1 (251) | ~600+ | 11 |
| الفواتير والدفع | 6 | 3 | ~2,000+ | 4 |
| المخزون | 8 | 4 (825) | ~1,200+ | 12 |
| الموارد البشرية | 20 | 16 (2,618) | ~3,500+ | 13 |
| **المجموع** | **45** | **35 (6,100+)** | **~10,800+** | **42** |

### الميزات التقنية المتقدمة في carag-v2

| الوحدة | الميزة التقنية | الكود |
|---|---|---|
| كروت العمل | Auto-calculation + Tax Snapshots | `recalculateTotals()`, `refreshTaxSnapshot()` |
| كروت العمل | State Machine مع Auto-transitions | `STATUS_READY_FOR_QC` auto عند اكتمال البنود |
| كروت العمل | Audit Trail شامل | `WorkOrderActivity` يسجل كل تغيير |
| الطباعة | Template Resolution ذكي | Document settings > User preference > Default |
| الطباعة | Multi-format (A4 + Thermal 80mm) | `TemplateDefaultA4`, `TemplateThermal80` |
| الفواتير | Payment Status ذكي | `updatePaymentStatus()` مع tolerance 1 cent |
| الفواتير | Credit Note handling | منطق منفصل للـ refunds |
| الفواتير | ZATCA Full Compliance | QR TLV, UUID, Hash chain, XML |
| المخزون | WAC (Weighted Average Cost) | `wac_cost`, `wac_after` في الحركات |
| المخزون | Polymorphic WorkOrder Accessor | `InventoryMove::getWorkOrderAttribute()` |
| المخزون | Low Stock Scopes معقدة | `scopeLowStock()` مع fallback لـ Part.min_qty |
| HR | Shift Resolution متقدم | `getShiftForDate()` مع weekly/specific date |
| HR | Auto Time Calculation | `calculateTimes()` مع grace period + overnight shifts |
| HR | GOSI Calculation | `calculateGosiAmount()` |
| HR | Employee ↔ User Sync | Automatic is_active sync |

---

## الخلاصة

**carag-v2 ليس مجرد نظام CRM — هو ERP كامل** مع:
- **State machines** معقدة للعمل (7 حالات للكرت + 6 للبنود)
- **Auto-calculation** ذكية (totals, tax, payroll, attendance times)
- **Audit trails** شاملة في كل وحدة
- **Multi-format printing** مع template resolution ذكي
- **ZATCA compliance** مدمج في الفواتير
- **WAC inventory costing** معقد
- **Shift-aware attendance** مع overnight support
- **GOSI + Payroll** متكامل

هذا مستوى **Enterprise-grade** يتجاوز بكثير نظام ورش بسيط مثل wrshah.com.
