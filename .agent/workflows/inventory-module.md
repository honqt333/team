# وثيقة وحدة المخزون (Inventory Module)
## نظام Carag - التوثيق الكامل

---

## 📋 نظرة عامة

وحدة المخزون هي نظام متكامل لإدارة قطع الغيار والمواد الاستهلاكية في مراكز صيانة السيارات. تدعم الوحدة:
- إدارة القطع والمواد
- تتبع الأرصدة في مستودعات متعددة
- تسجيل جميع الحركات (استلام، صرف، تسوية)
- التحويل بين الفروع
- الربط بأوامر العمل (Work Orders)
- حساب التكلفة المتوسطة المرجحة (WAC)

---

## 🗄️ قاعدة البيانات (Database Schema)

### الجداول الرئيسية

#### 1. `parts` - القطع والمواد
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| tenant_id | bigint | المنشأة |
| sku | string | رمز القطعة (فريد) |
| name_ar | string | الاسم بالعربي |
| name_en | string | الاسم بالإنجليزي |
| unit_id | bigint | وحدة القياس |
| category_id | bigint | التصنيف |
| description | text | الوصف |
| min_qty | decimal | الحد الأدنى |
| reorder_qty | decimal | كمية إعادة الطلب |
| default_sale_price | decimal | سعر البيع الافتراضي |
| is_active | boolean | الحالة |

#### 2. `inventory_units` - وحدات القياس
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| tenant_id | bigint | المنشأة |
| name_ar | string | الاسم بالعربي |
| name_en | string | الاسم بالإنجليزي |
| symbol | string | الرمز (قطعة، لتر، كجم) |
| is_active | boolean | الحالة |

#### 3. `inventory_categories` - التصنيفات
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| tenant_id | bigint | المنشأة |
| name_ar | string | الاسم بالعربي |
| name_en | string | الاسم بالإنجليزي |
| is_active | boolean | الحالة |

#### 4. `warehouses` - المستودعات
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| center_id | bigint | الفرع |
| name | string | اسم المستودع |
| is_default | boolean | المستودع الافتراضي |
| is_active | boolean | الحالة |

#### 5. `inventory_balances` - الأرصدة
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| warehouse_id | bigint | المستودع |
| part_id | bigint | القطعة |
| qty_on_hand | decimal | الكمية المتوفرة |
| wac_cost | decimal | متوسط التكلفة المرجحة |

#### 6. `inventory_moves` - الحركات
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| warehouse_id | bigint | المستودع |
| part_id | bigint | القطعة |
| type | string | نوع الحركة |
| qty | decimal | الكمية |
| unit_cost | decimal | تكلفة الوحدة |
| balance_after | decimal | الرصيد بعد |
| reference_type | string | نوع المرجع |
| reference_id | bigint | معرف المرجع |
| notes | text | ملاحظات |
| posted_at | timestamp | تاريخ الترحيل |
| posted_by | bigint | من قام بالترحيل |

**أنواع الحركات (Move Types):**
- `receipt` - استلام
- `issue` - صرف لكرت عمل
- `adjustment_in` - تسوية زيادة
- `adjustment_out` - تسوية نقص
- `transfer_in` - تحويل وارد
- `transfer_out` - تحويل صادر
- `reversal` - عكس حركة

#### 7. `inventory_transfers` - التحويلات
| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint | المعرف |
| tenant_id | bigint | المنشأة |
| code | string | رقم التحويل |
| from_warehouse_id | bigint | من مستودع |
| to_warehouse_id | bigint | إلى مستودع |
| status | string | الحالة |
| notes | text | ملاحظات |
| created_by | bigint | أنشئ بواسطة |
| sent_at | timestamp | تاريخ الإرسال |
| sent_by | bigint | أرسل بواسطة |
| received_at | timestamp | تاريخ الاستلام |
| received_by | bigint | استلم بواسطة |

**حالات التحويل (Transfer Statuses):**
- `draft` - مسودة
- `sent` - مرسل
- `received` - مستلم
- `cancelled` - ملغي

---

## 🔗 الروابط (Routes)

### Base URL: `/app/inventory`

| الطريقة | الرابط | الاسم | الوصف |
|---------|--------|-------|-------|
| GET | `/` | `app.inventory.hub` | لوحة المخزون الرئيسية |
| | | | |
| **الإعدادات** | | | |
| GET | `/settings` | `app.inventory.settings.index` | صفحة الإعدادات |
| POST | `/settings/units` | `app.inventory.settings.units.store` | إضافة وحدة |
| PUT | `/settings/units/{unit}` | `app.inventory.settings.units.update` | تعديل وحدة |
| DELETE | `/settings/units/{unit}` | `app.inventory.settings.units.destroy` | حذف وحدة |
| POST | `/settings/categories` | `app.inventory.settings.categories.store` | إضافة تصنيف |
| PUT | `/settings/categories/{category}` | `app.inventory.settings.categories.update` | تعديل تصنيف |
| DELETE | `/settings/categories/{category}` | `app.inventory.settings.categories.destroy` | حذف تصنيف |
| | | | |
| **القطع** | | | |
| GET | `/parts` | `app.inventory.parts.index` | قائمة القطع |
| GET | `/parts/create` | `app.inventory.parts.create` | صفحة إضافة |
| POST | `/parts` | `app.inventory.parts.store` | حفظ قطعة جديدة |
| GET | `/parts/{part}/edit` | `app.inventory.parts.edit` | صفحة تعديل |
| PUT | `/parts/{part}` | `app.inventory.parts.update` | تحديث قطعة |
| PATCH | `/parts/{part}/toggle` | `app.inventory.parts.toggle` | تفعيل/تعطيل |
| GET | `/api/parts/search` | `app.inventory.parts.search` | API بحث |
| | | | |
| **الأرصدة** | | | |
| GET | `/stock` | `app.inventory.stock.index` | عرض الأرصدة |
| GET | `/api/stock/{part}` | `app.inventory.stock.part` | API رصيد قطعة |
| | | | |
| **الحركات** | | | |
| GET | `/moves` | `app.inventory.moves.index` | سجل الحركات |
| POST | `/moves/receipt` | `app.inventory.moves.receipt` | تسجيل استلام |
| POST | `/moves/adjustment` | `app.inventory.moves.adjustment` | تسجيل تسوية |
| POST | `/moves/{move}/reverse` | `app.inventory.moves.reverse` | عكس حركة |
| | | | |
| **التحويلات** | | | |
| GET | `/transfers` | `app.inventory.transfers.index` | قائمة التحويلات |
| GET | `/transfers/create` | `app.inventory.transfers.create` | إنشاء تحويل |
| POST | `/transfers` | `app.inventory.transfers.store` | حفظ تحويل |
| GET | `/transfers/{transfer}` | `app.inventory.transfers.show` | عرض تحويل |
| POST | `/transfers/{transfer}/items` | `app.inventory.transfers.items.store` | إضافة بند |
| DELETE | `/transfers/{transfer}/items/{item}` | `app.inventory.transfers.items.destroy` | حذف بند |
| POST | `/transfers/{transfer}/send` | `app.inventory.transfers.send` | إرسال التحويل |
| POST | `/transfers/{transfer}/receive` | `app.inventory.transfers.receive` | استلام التحويل |
| POST | `/transfers/{transfer}/cancel` | `app.inventory.transfers.cancel` | إلغاء التحويل |

---

## 📁 هيكل الملفات

### Backend (Laravel)

```
app/
├── Models/
│   ├── Part.php                    # نموذج القطعة
│   ├── InventoryUnit.php           # وحدات القياس
│   ├── InventoryCategory.php       # التصنيفات
│   ├── InventoryBalance.php        # الأرصدة
│   ├── InventoryMove.php           # الحركات
│   ├── InventoryTransfer.php       # التحويلات
│   ├── InventoryTransferItem.php   # بنود التحويل
│   └── Warehouse.php               # المستودعات
│
├── Http/Controllers/App/
│   ├── PartsController.php         # إدارة القطع
│   ├── InventorySettingsController.php  # الإعدادات
│   ├── InventoryBalanceController.php   # الأرصدة
│   ├── InventoryMoveController.php      # الحركات
│   ├── InventoryTransfersController.php # التحويلات
│   └── WorkOrderPartsController.php     # قطع أوامر العمل
│
├── Policies/
│   ├── PartPolicy.php
│   ├── InventoryBalancePolicy.php
│   ├── InventoryMovePolicy.php
│   └── InventoryTransferPolicy.php
│
└── Services/Inventory/
    ├── InventoryService.php        # خدمة المخزون الرئيسية
    └── WorkOrderPartsService.php   # خدمة قطع أوامر العمل
```

### Frontend (Vue)

```
resources/js/Pages/Inventory/
├── Hub.vue                # لوحة المخزون الرئيسية
├── Settings.vue           # إعدادات الوحدات والتصنيفات
│
├── Parts/
│   ├── Index.vue          # قائمة القطع
│   ├── Form.vue           # نموذج إضافة/تعديل
│   └── CreateModal.vue    # Modal إضافة سريعة
│
├── Stock/
│   └── Index.vue          # عرض الأرصدة
│
├── Moves/
│   └── Index.vue          # سجل الحركات
│
└── Transfers/
    ├── Index.vue          # قائمة التحويلات
    ├── Form.vue           # إنشاء تحويل
    └── Show.vue           # تفاصيل التحويل
```

---

## 🔧 الخدمات (Services)

### InventoryService

```php
// استلام (إضافة مخزون)
$inventoryService->receipt(
    warehouseId: 1,
    partId: 5,
    qty: 100,
    unitCost: 25.50,
    userId: auth()->id(),
    notes: 'استلام مورد ABC'
);

// صرف (خصم من المخزون)
$inventoryService->issue(
    warehouseId: 1,
    partId: 5,
    qty: 10,
    userId: auth()->id(),
    referenceType: 'work_order',
    referenceId: 123,
    allowNegative: false
);

// تسوية (زيادة أو نقص)
$inventoryService->adjust(
    warehouseId: 1,
    partId: 5,
    qty: -5, // سالب = نقص، موجب = زيادة
    userId: auth()->id(),
    notes: 'تسوية جرد'
);

// عكس حركة
$inventoryService->reverseMove($move, auth()->id());

// إرسال تحويل
$inventoryService->sendTransfer($transfer, auth()->id());

// استلام تحويل
$inventoryService->receiveTransfer($transfer, $receivedQtys, auth()->id());

// إلغاء تحويل
$inventoryService->cancelTransfer($transfer, auth()->id(), 'سبب الإلغاء');
```

---

## 🔐 الصلاحيات (Permissions)

| الصلاحية | الوصف |
|----------|-------|
| `inventory.parts.viewAny` | عرض قائمة القطع |
| `inventory.parts.create` | إضافة قطعة |
| `inventory.parts.update` | تعديل قطعة |
| `inventory.parts.delete` | حذف قطعة |
| `inventory.stock.view` | عرض الأرصدة |
| `inventory.moves.viewAny` | عرض الحركات |
| `inventory.moves.create` | تسجيل حركة (استلام/تسوية) |
| `inventory.moves.reverse` | عكس حركة |
| `inventory.transfers.viewAny` | عرض التحويلات |
| `inventory.transfers.create` | إنشاء تحويل |
| `inventory.transfers.send` | إرسال تحويل |
| `inventory.transfers.receive` | استلام تحويل |
| `inventory.transfers.cancel` | إلغاء تحويل |
| `inventory.settings.manage` | إدارة الإعدادات |
| `inventory.override_negative_stock` | السماح بالرصيد السالب |

---

## 🔄 سير العمل (Workflows)

### 1. دورة حياة القطعة
```
إنشاء القطعة → تفعيل → استخدام في أوامر العمل → (اختياري) تعطيل
```

### 2. دورة الاستلام
```
تسجيل استلام → تحديث الرصيد → تحديث WAC → ترحيل الحركة
```

### 3. دورة الصرف لأمر عمل
```
اختيار قطعة → إدخال الكمية → التحقق من الرصيد → الصرف التلقائي → تحديث الرصيد
```

### 4. دورة التحويل
```
إنشاء تحويل (مسودة) → إضافة البنود → إرسال → 
استلام في الفرع المستهدف → تحديث الأرصدة
```

---

## 📊 حساب التكلفة المتوسطة المرجحة (WAC)

```
WAC الجديدة = (الكمية القديمة × WAC القديمة + الكمية الجديدة × التكلفة الجديدة) 
              ÷ (الكمية القديمة + الكمية الجديدة)

مثال:
- الرصيد الحالي: 100 قطعة × 20 ر.س = 2000 ر.س
- استلام: 50 قطعة × 25 ر.س = 1250 ر.س
- WAC الجديدة = (2000 + 1250) ÷ 150 = 21.67 ر.س
```

---

## 🚨 ملاحظات مهمة

1. **المستودع الافتراضي**: كل فرع لديه مستودع افتراضي يُنشأ تلقائياً
2. **منع الرصيد السالب**: افتراضياً ممنوع، يحتاج صلاحية خاصة
3. **عكس الحركات**: يمكن عكس الحركات اليدوية فقط
4. **التحويلات**: لا يمكن تعديل تحويل بعد إرساله
5. **الحذف الناعم**: القطع تُعطَّل ولا تُحذف للحفاظ على التاريخ

---

## ✅ الحالة الحالية

| المكون | الحالة |
|--------|--------|
| Models | ✅ مكتمل |
| Controllers | ✅ مكتمل |
| Policies | ✅ مكتمل |
| Routes | ✅ مكتمل |
| Vue Pages | ✅ مكتمل |
| InventoryService | ✅ مكتمل |
| الترجمات | ✅ مكتمل |
| ربط أوامر العمل | ✅ مكتمل |

---

*آخر تحديث: 2026-01-11*
