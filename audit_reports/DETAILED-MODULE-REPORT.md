# تقرير تفصيلي للوحدات الرئيسية: wrshah.com ↔ carag-v2
## التاريخ: 2026-11-08 | الوحدات: كروت العمل | الطباعة | الفواتير | المخزون | الموارد البشرية

---

## أولاُ: وحدة كروت العمل (Work Orders)

### 📊 البنية البرمجية في carag-v2

**النماذج (13 model):**
| النموذج | السطور | الوظيفة |
|---|---|---|
| WorkOrder | 387 | الكرت الرئيسي — 6 حالات + scopes |
| WorkOrderItem | - | البنود الفرعية (خدمة/قطعة/عمالة) |
| WorkOrderItemPart | - | القطع المستخدمة في البند |
| WorkOrderLaborEntry | - | ساعات العمل لكل فني |
| WorkOrderPhoto | - | صور قبل/بعد/أثناء |
| WorkOrderAttachment | - | ملفات مرفقة |
| WorkOrderDamageMark | - | علامات التلف |
| WorkOrderInspection | - | فحص المركبة مع templates |
| WorkOrderItemNote | - | ملاحظات البنود |
| WorkOrderItemQualityCheck | - | فحص الجودة |
| WorkOrderActivity | - | سجل النشاط (audit trail) |

**Controllers (11 controller — إجمالي 2,406 سطر):**
| Controller | السطور | الوظيفة |
|---|---|---|
| WorkOrderController | 509 | CRUD كامل + filtering + scopes |
| WorkOrderItemController | 318 | إدارة البنود الفرعية |
| WorkOrderStatusController | 238 | تحديث الحالات (بدء/توقف/إلغاء/إكمال) |
| WorkOrderLaborEntryController | 201 | تسجيل ساعات العمالة |
| WorkOrderPartsController | 151 | إضافة/خصم قطع الغيار |
| WorkOrderTechnicianController | 166 | تعيين الفنيين |
| WorkOrderMediaController | 114 | رفع الصور |
| WorkOrderNotesController | 117 | الملاحظات |
| WorkOrderPaymentController | 104 | المدفوعات المرتبطة |
| WorkOrderPrintController | 251 | طباعة 4 قوالب |
| WorkOrderWarrantiesController | 86 | الضمانات |

### 🔄 حالات كروت العمل (6 حالات)

```
open → in_progress → on_hold (مع سبب) → ready_for_qc → completed
                                                        ↓
                                              cancelled
```

**العمليات المتاحة:**
- `startWork()` — بدء التنفيذ
- `putOnHold($reason)` — إيقاف مع سبب
- `resume()` — استئناف من التوقف
- `cancel()` — إلغاء مع تأكيد
- `complete()` — إكمال (يتحقق من اكتمال كل البنود)
- `updateCondition()` — تحديث حالة المركبة عبر Inspection

### 🔍 مقارنة مع wrshah.com

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| عدد الحالات | basic (مفتوح/مكتمل/ملغى) | **6 حالات workflow** | carag-v2 |
| إدارة العمالة | ✅ داخل الكرت | ✅ LaborEntry منفصل + technician assignment | carag-v2 |
| قطع الغيار | ✅ في الكرت | ✅ Part model + inventory deduction تلقائي | carag-v2 |
| الصور/الوسائط | ❌ | ✅ MediaController (رفع متعدد) | carag-v2 |
| نموذج الفحص | ❌ | ✅ Inspection + templates | carag-v2 |
| Audit trail | ❌ | ✅ Activity model يسجل كل تغيير | carag-v2 |
| Workshop Board | ❌ | ✅ لوحة مرئية sortable | carag-v2 |
| الربط بالQuotes | ✅ | ✅ quote_id + conversion flow | تعادل |
| حالة المركبة | ✅ | ✅ ConditionCategory + Item models | carag-v2 |

---

## ثانياُ: النظام الطباعي (Printing System)

### 📊 البنية البرمجية

**المكونات الأساسية (8 components):**
| المكون | الوصف |
|---|---|
| PrintEngine.vue | المحرك الموحد — يدير اختيار القالب والمعاينة |
| PrintLayout.vue | التخطيط الأساسي للطباعة |
| TemplateDefaultA4.vue | قالب A4 الافتراضي |
| TemplateThermal80.vue | قالب حراري 80mm للإيصالات |
| StandardPrintControls.vue | أزرار الطباعة الموحدة |
| StandardReportHeader.vue | رأس التقرير الموحد |
| StandardReportFooter.vue | ذيل التقرير الموحد |
| StandardReportLayout.vue | تخطيط التقرير |

**قوالب الطباعة المتاحة (11 صفحة + 4 قوالب backend):**

| الفئة | القالب | الصفحة | الاستخدام |
|---|---|---|---|
| كروت العمل | Condition | WorkOrders/Print/Condition.vue | تقرير حالة المركبة |
| | Services | WorkOrders/Print/Services.vue | تفاصيل الخدمات |
| | Proforma | WorkOrders/Print/Proforma.vue | تسعير مقدّم |
| | Payments | WorkOrders/Print/Payments.vue | كشف المدفوعات |
| الفواتير | Invoice | Invoices/Print.vue | فاتورة مباعة |
| | Purchase | Purchasing/Invoices/Print.vue | فاتورة مشتريات |
| | Quote | Quotes/Print.vue | عرض سعر |
| | Customer | Customers/Print.vue | بيانات عميل |
| | Statement | Customers/PrintStatement.vue | بيان حساب |
| HR | Attendance | HR/Attendance/Print.vue | تقرير حضور |
| | Employee | HR/Employees/Print.vue | بطاقة موظف |
| | Payroll | HR/Payroll/Print.vue | كشف راتب |

**إعدادات الطباعة per tenant:**
- `invoice_title`, `quote_title`, `work_order_title` — عناوين مخصصة
- `invoice_terms`, `quote_terms`, `work_order_terms` — شروط مخصصة
- `print_settings` JSON — القالب النشط
- SignaturePad — توقيع إلكتروني محفوظ

### 🔍 مقارنة مع wrshah.com
| wrshah.com | carag-v2 | الفائز |
|---|---|---|
| قالب واحد (推测) | 4+ قوالب متعددة | carag-v2 بكثير |
| لا يوجد نظام موحد | PrintEngine موحد | carag-v2 |
| غير واضح | Thermal 80mm support | carag-v2 |
| لا يوجد | قوالب ZATCA compliant | carag-v2 |

---

## ثالثاُ: وحدة الفواتير والدفع (Invoices & Payments)

### 📊 البنية البرمجية

**الفواتير المباعة:**
| المكون | الملف | السطور |
|---|---|---|
| Invoice | app/Models/Invoice.php | 218 |
| InvoiceLine | app/Models/InvoiceLine.php | - |
| InvoiceTemplate | app/Models/InvoiceTemplate.php | - |
| Controller | App/InvoicesController.php | 528 |
| Page Hub | Pages/Invoices/Hub.vue | - |
| Page Show | Pages/Invoices/Show.vue | 1782 |
| Page Print | Pages/Invoices/Print.vue | - |

**الفواتير المشتريات:**
| المكون | السطور |
|---|---|
| PurchaseInvoice Model | - |
| PurchaseInvoiceLine Model | - |
| Controller | - |
| Page Show | 2912 سطر |

**المدفوعات:**
| المكون | التفاصيل |
|---|---|
| Payment Model | relationships: invoice, purchase_invoice, work_order, received_by |
| Payment Types | cash, card, bank_transfer, online |
| Partial Payments | ✅ مدعوم |
| Refunds | ✅ مدعوم |
| Bank Receipts | BankTransferReceipt model |

**ZATCA E-Invoicing:**
| المكون | الملف |
|---|---|
| Integration Manager | Integration/Integration.php |
| Onboarding | ZatcaOnboardingController.php |
| Settings | TenantZatcaSetting model |
| Tax Calculator | TaxCalculator service |
| Tax Periods | TaxPeriod + TaxPeriodTransaction |

### 💳 ميزات الدفع المتقدمة
- دفع جزئي متعدد (`scopePartiallyPaid`)
- تتبع الرصيد المستحق (`scopeWithBalance`, `getBadDebtAttribute`)
- Dunning system (تنبيهات التحصيل)
- Moyasar payment gateway integration
- Promo codes for discounts

### 🔍 مقارنة مع wrshah.com
| wrshah.com | carag-v2 | الفائz |
|---|---|---|
| فواتير مباعة فقط | مباعة + مشتريات | carag-v2 |
| دفـع نقدي أساسي | متعدد الوسائط + جزئي | carag-v2 |
| ZATCA بسيط | ZATCA متكامل + onboarding | carag-v2 |
| بدون استرداد | نظام refunds كامل | carag-v2 |
| بدون dunning | DunningConfig + logs | carag-v2 |

---

## رابعاُ: وحدة المخزون (Inventory)

### 📊 البنية البرمجية

**النماذج (8 models):**
| النموذج | الوظيفة |
|---|---|
| Part | قطعة الغيار (sku, name, category, unit, cost) |
| InventoryBalance | رصيد كل قطعة في كل مستودع |
| InventoryMove | حركة مخزون (وارد/صادر/نقل/تعديل) |
| InventoryCategory | تصنيف القطع |
| InventoryUnit | وحدات القياس |
| Warehouse | المستودعات |
| InventoryTransfer | طلب نقل بين المستودعات |
| InventoryTransferItem | بنود النقل |

**Controllers (4 controllers — 825 سطر):**
| Controller | السطور | الوظيفة |
|---|---|---|
| InventoryBalanceController | 175 | عرض وتحديث الرصيد |
| InventoryMoveController | 194 | تسجيل الحركات |
| InventoryTransfersController | 281 | إدارة النقل |
| InventorySettingsController | - | الإعدادات |

**أنواع حركات المخزون:**
```php
TYPE_TRANSFER_OUT = 'transfer_out'  // خروج
TYPE_TRANSFER_IN  = 'transfer_in'   // دخول
// أيضاً: WORK_ORDER_ISSUE, PURCHASE_RECEIVE, SALE_USE, ADJUSTMENT
```

**الميزات:**
- Multi-warehouse support
- Low stock alerts (`scopeLowStock`)
- Automatic deduction from Work Orders
- Automatic addition from Purchase Invoices
- Stock valuation (`getTotalValueAttribute`)
- Full movement history

### 🔍 مقارنة مع wrshah.com
| wrshah.com | carag-v2 | الفائz |
|---|---|---|
| ❌ لا يوجد | ✅ 8 models + 4 controllers | carag-v2 |
| ❌ | ✅ Multi-warehouse | carag-v2 |
| ✅ وحدات قياس | ✅ InventoryUnit | تعادل |
| ⚠️ كتالوج مركبات | ⚠️ Part موجود لكن بدون كتالوج vehicle-specific | تعادل |

---

## خامساُ: وحدة الموارد البشرية (HR)

### 📊 البنية البرمجية

**النماذج (20 model):**
Employee, Attendance, AttendanceSettings, Shift, EmployeeShift,
EmployeeContract, EmployeeDocument, EmployeeType, HRRegulation,
Leave, Payroll, PayrollItem, PayrollRun, OtherPayment,
BiometricDevice, Nationality, Department, JobTitle, Allowance, Deduction

**Controllers (16 controller — 2,618 سطر):**
| Controller | السطور | الوظيفة |
|---|---|---|
| HRController | 80 | نقطة الدخول |
| EmployeeController | 435 | إدارة الموظفين كاملة |
| AttendanceController | 234 | الحضور والانصراف |
| BiometricDeviceController | 103 | أجهزة البصمة |
| EmployeeContractsController | 97 | العقود |
| EmployeeDocumentsController | 168 | الوثائق |
| EmployeeFinancialsController | 112 | البيانات المالية |
| EmployeePermissionsController | 73 | الصلاحيات |
| EmployeeShiftController | 72 | الجداول الوردية |
| HRPayrollSettingsController | 148 | إعدادات الرواتب |
| HRRegulationsController | 68 | اللوائح |
| LeaveController | 93 | الإجازات |
| OtherPaymentsController | 126 | المدفوعات الأخرى |
| PayrollController | 389 | حساب الرواتب |
| SettingsController | 186 | إعدادات HR |
| ShiftController | 73 | إدارة الدورانات |

**دورة الرواتب:**
```
Draft → Approved → Paid
```
| العملية | الوظيفة |
|---|---|
| `generate()` | إنشاء كشف الرواتب |
| `regenerate()` | إعادة الحساب |
| `approve()` | اعتماد الكشف |
| `markPaid()` | تسجيل الدفع |
| `calculateNetSalary()` | صافي الراتب = الأساسي + البدلات - الخصومات - GOSI |

**بوابة الموظف الذاتية (Employee Portal):**
- Dashboard — نظرة عامة
- MyTasks — مهامه
- Attendance — سجل حضوره
- Payslips — كشفراته
- Leaves — طلبات إجازته
- Profile — ملفه الشخصي

### 🔍 مقارنة مع wrshah.com
| wrshah.com | carag-v2 | الفائz |
|---|---|---|
| وجود الوحدة | ✅ قائمة جانبية | ✅ 16 controller + 20 model | carag-v2 بكثير |
| الحضور | basic | ✅ + Biometric device | carag-v2 |
| الرواتب | ❌ | ✅ + GOSI + allowances | carag-v2 |
| الإجازات | ❌ | ✅ LeaveController | carag-v2 |
| البوابة الذاتية | ❌ | ✅ 5 صفحات portal | carag-v2 |
| البدلات/الخصومات | ✅ في الإعدادات | ✅ Allowance + Deduction models | carag-v2 |

---

## سابعاُ: ملخص المقارنة الشاملة

### حجم الكود لكل وحدة (carag-v2 فقط)

| الوحدة | نماذج | Controllers | Vue pages | إجمالي السطور |
|---|---|---|---|---|
| كروت العمل | 11 | 11 | 2+ | ~3,500+ |
| الطباعة | 0 | 1 | 11 | ~600+ |
| الفواتير والدفع | 6 | 3 | 4 | ~2,000+ |
| المخزون | 8 | 4 | 12 | ~1,200+ |
| الموارد البشرية | 20 | 16 | 13 | ~3,500+ |
| **المجموع** | **45** | **35** | **42** | **~10,800+** |

### ما يتفوق فيه carag-v2

- Workflow state machine متقدم (6 حالات vs basic)
- نظام طباعة موحد بـ 4+ قوالب
- ZATCA e-invoicing متكامل
- Dunning system للتحصيل
- Multi-warehouse inventory
- Payroll cycle كامل + GOSI
- Biometric device support
- Self-service employee portal
- Audit trail في كل وحدة

### ما يحتاجه carag-v2 من wrshah.com

| الميزة | الجهد | الأولوية |
|---|---|---|
| Vehicle Parts Catalog | صعب | 🟡 متوسطة |
| Floating SpeedDial | متوسط | 🔴 عالية |
| Subscription Banner تحسين | سهل | 🔴 عالية |
| Welcome Banner | سهل | 🟡 متوسطة |
| Breadcrumbs تفعيل | سهل | 🟡 متوسطة |
