# 03 — جرد المكوّنات (Component Inventory)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. نطاق الجرد

| المقياس | العدد | الطريقة |
|---|---|---|
| ملفات `.vue` في `resources/js` | **444** | `find` |
| `Components/` — إجمالي الملفات | 222 | `find` |
| `Components/` — مكوّنات `.vue` | **194** | `find` |
| `Components/` — ملفات اختبار | 28 | `find` |
| `Pages/` — صفحات `.vue` | **248** | `find` |
| `Composables/` — تشغيلية | 19 | `find` |
| `Layouts/` | 6 | `find` |
| إجمالي LOC مكوّنات `.vue` | **55,374** | `wc -l` |

---

## 2. منهجية العد — ولماذا تختلف عن بحث نصي ساذج

### الطريقة المعتمدة

لكل مكوّن، **ثلاث عدّادات مستقلة**:

| العدّاد | الأمر | ما يقيسه |
|---|---|---|
| **Import count** | `grep -rlF "@/Components/<path>.vue" resources/js app` | الملفات التي تستورده |
| **Usage count** | `grep -rc "<ComponentName" resources/js` | الملفات التي تستخدم الوسم فعلياً |
| **Dynamic check** | فحص `app.js` للتسجيل العام، `resolveComponent`، `defineAsyncComponent`، `<component :is>` | الاستخدام غير المباشر |

### لماذا البحث النصي وحده يُضلّل

| الحالة | النتيجة |
|---|---|
| `Toggle.vue` | بحث ساذج على `"Toggle"` يُعطي **125 نتيجة** — لأن `AppToggle` تحتوي السلسلة. البحث المطلق `@/Components/Toggle.vue` يعطي **0**. |
| قوالب الطباعة | بحث ساذج يعطي 0 استخدام ⇒ تبدو ميتة. لكنها **مستخدمة ديناميكياً** عبر `<component :is="activeTemplate">` في `PrintEngine.vue:4,54-71`. |
| `AppSelect.vue` | `grep` على `<AppSelect` يعطي 0، لكنها **مسجّلة عامياً** في `app.js:123`. |

> **القاعدة**: لا يُصنَّف أي مكوّن كـ«غير مستخدم» اعتماداً على بحث نصي واحد.

---

## 3. المكوّنات الأساسية (Top-tier) — أرقام مُتحقَّق منها

| المكوّن | Import | نوع الاستخدام | التصنيف |
|---|---|---|---|
| `Components/SearchableSelect.vue` | **125** | مباشر | ✅ المعتمد في الدستور |
| `Components/PageHeader.vue` | **115** | مباشر | ✅ المعتمد |
| `Components/Common/AppToggle.vue` | **79** | مباشر | ✅ المعتمد |
| `Components/Common/DataTable.vue` | **100** | مباشر | ✅ المعتمد |
| `Components/BaseModal.vue` | **54** | مباشر | Primitive |
| `Components/BackButton.vue` | **50** | مباشر | ✅ |
| `Components/CustomDatePicker.vue` | **39** | مباشر | ✅ المعتمد |
| `Components/Common/AppPrintButton.vue` | **32** | مباشر | ✅ المعتمد |
| `Components/Tooltip.vue` | **31** | مباشر | |
| `Components/Modal.vue` | **29** | مباشر | طبقة فوق BaseModal |
| `Components/Common/AppEmptyState.vue` | **28** | مباشر | ✅ المعتمد |
| `Components/Common/AppViewModeToggle.vue` | **23** | مباشر | ✅ المعتمد |
| `Components/Common/AppPagination.vue` | **20** | مباشر | ✅ المعتمد |
| `Components/Common/AppTabs.vue` | **20** | مباشر | ✅ المعتمد |
| `Components/Vehicles/SaudiPlateDisplay.vue` | **15** | مباشر | ✅ المعتمد |
| `Components/ConfirmModal.vue` | **12** | مباشر | ✅ المعتمد |
| `Components/Common/FormField.vue` | **11** | مباشر | ✅ المعتمد |
| `Components/Common/SelectableCard.vue` | **10** | مباشر | ✅ المعتمد |
| `Components/Common/AppTableSkeleton.vue` | **8** | مباشر | |
| `Components/Common/RadioGroup.vue` | **2** | مباشر | ✅ المعتمد |

### 🔎 المكوّنات المعتمدة في الدستور وغير مُتبنّاة

| المكوّن | الاستخدام | الملاحظة |
|---|---|---|
| `Components/Common/FormSection.vue` | **0** | **مُلزَم به في الدستور، غير مستخدم إطلاقاً** |
| `Components/Common/RadioGroup.vue` | 2 | مُلزَم، شبه مهمل |
| `Components/Common/FormField.vue` | 11 | مُلزَم، adoption ضعيف |
| `Components/Vehicles/SaudiPlateInput.vue` | 0 مباشر | يُستخدم عبر `VehicleFormModal` — تحققت |
| `Components/Common/SignaturePad.vue` | 3 | محدود |

---

## 4. المكوّنات الميتة — 6 مؤكَّدة (بحث بمسار مطلق)

| # | المكوّن | LOC | الدليل | ملاحظة |
|---|---|---|---|---|
| 1 | `Components/DashboardV2/common/PageHeader.vue` | 50 | `grep -rlF "@/Components/DashboardV2/common/PageHeader.vue" resources/js app` → **0** | 🔴 **نسخة مكرّرة من `PageHeader.vue` — props مختلفة (`greeting`/`userName`/`date`) وتشريح بصري مختلف (border-bottom)** |
| 2 | `Components/DashboardV2/common/DashboardTabs.vue` | 44 | → **0** | البديل المعتمد `AppTabs` |
| 3 | `Components/Toggle.vue` | 39 | → **0** | غلاف لـ`AppToggle` |
| 4 | `Components/Checkbox.vue` | 34 | → **0** | البديل `AppCheckbox` |
| 5 | `Components/TextInput.vue` | 26 | → **0** | مخلف Breeze |
| 6 | `Components/Common/AppCardSkeleton.vue` | 27 | → **0** | `AppTableSkeleton` هو المستخدم |
| 7 | `Components/Common/FormSection.vue` | 45 | → **0** | **مُلزَم به في الدستور** |

**المجموع: 7 ملفات ميتة مؤكَّدة** (بما فيها `FormSection` المطلوب في الدستور).

> ⚠️ **تصحيح صريح**: تقرير التحقيق الفرعي ذكر «9 مكونات غير مستخدمة» و«`PageHeader` له نسختان كلتاهما 115 مستخدماً». الفحص المباشر يُظهر أن **كل الاستيرادات الـ115 تشير إلى `@/Components/PageHeader.vue`**، وأن نسخة `DashboardV2` **ميتة تماماً**. الأدلة في `14_EVIDENCE_INDEX.md` §7.

---

## 5. المكوّنات حسب المجال

### `Components/` الجذر — 30 ملف

| المكوّن | LOC | Import | حالة |
|---|---|---|---|
| `SearchableSelect.vue` | 408 | 125 | ✅ |
| `PageHeader.vue` | 158 | 115 | ✅ المعتمد |
| `BaseModal.vue` | 160 | 54 | ✅ |
| `BackButton.vue` | 64 | 50 | ✅ |
| `CustomDatePicker.vue` | 315 | 39 | ✅ |
| `Modal.vue` | 222 | 29 | طبقة وسطى |
| `ConfirmModal.vue` | 170 | 12 | ✅ |
| `Tooltip.vue` | 117 | 31 | |
| `ServiceFormModal.vue` | 913 | 1 | 💡 ضخم |
| `PackageFormModal.vue` | 762 | 1 | 💡 ضخم |
| `WorkOrderServiceModal.vue` | **2,271** | 1 | 🔴 **الأكبر** |
| `WorkOrderFormModal.vue` | 1,257 | 4 | 💡 |
| `ServiceQuickEditModal.vue` | 341 | 1 | |
| `NewPackageDefinitionModal.vue` | 349 | 1 | |
| `NewServiceDefinitionModal.vue` | 391 | 1 | |
| `DepartmentFormModal.vue` | 302 | 1 | |
| `NotificationBell.vue` | 274 | 1 | |
| `ConditionItemFormModal.vue` | 239 | 1 | |
| `ConditionCategoryFormModal.vue` | 210 | 1 | |
| `SystemAnnouncementBanner.vue` | 104 | 1 | |
| `DialogModal.vue` | 44 | 4 | |
| `Toast.vue` | 147 | 3 | |
| `Breadcrumb.vue` | 42 | 3 | |
| `InputError.vue` | 15 | 7 | |
| `InputLabel.vue` | 14 | 1 | |
| `PrimaryButton.vue` | 13 | 5 | legacy |
| `SecondaryButton.vue` | 13 | 6 | legacy |
| `DangerButton.vue` | 13 | 1 | legacy |
| `ColorField.vue` | 46 | 1 | |
| `TextInput.vue` / `Toggle.vue` / `Checkbox.vue` | 26/39/34 | **0** | 🔴 **ميتة** |

### `Components/App/` — نظام التصميم الأصلي (5 ملفات)

| المكوّن | LOC | Import | الاستخدام الفعلي |
|---|---|---|---|
| `AppButton.vue` | 487 | 11 | 10 + 9 عبر التسجيل العام `app.js:121` |
| `AppInput.vue` | 240 | 13 | 12 + 10 عام |
| `AppCheckbox.vue` | 208 | 4 | 2 + 2 عام |
| `AppTextarea.vue` | 197 | 3 | 1 + 1 عام |
| `AppSelect.vue` | 204 | 2 | **0** ⚠️ مسجَّل عامياً بلا استخدام |

### `Components/Common/` — 24 ملفاً، 3,695 LOC

المكوّنات المحورية: `DataTable` (284 / 100 مستخدم) · `AppToggle` (241 / 79) · `AppPagination` (181 / 20) · `AppEmptyState` (146 / 28) · `AppViewModeToggle` (104 / 23) · `AppTabs` (153 / 20) · `AppPrintButton` (130 / 32) · `FormField` (92 / 11) · `SelectableCard` (145 / 10) · `RadioGroup` (72 / 2) · `SignaturePad` (151 / 3) · `AppAttachmentManager` (559 / 2) · `PartsDisplay` (746 / 4) · `CommandPalette` (340 / 1) · `StatusBadge` (21 / 9) · `PaymentStatusBadge` (25 / 2) · `AppTableSkeleton` (102 / 8) · `AppCardSkeleton` (27 / **0**) · `FormSection` (45 / **0**) · `ImportFileField` (43 / 2) · `ImportResult` (15 / 2) · `LoadingSpinner` (8 / 1) · `SortIcon` (41 / 1) · `ReportDateRange` (24 / 2)

### عائلات StatusBadge — 5 تطبيقات

| المكوّن | Import | LOC |
|---|---|---|
| `Components/Billing/StatusBadge.vue` | 7 | 60 |
| `Components/DashboardV2/common/StatusBadge.vue` | 7 | 98 |
| `Components/CustomerPortal/StatusBadge.vue` | 7 | 48 |
| `Components/Common/StatusBadge.vue` | 9 | 21 |
| `Components/Common/PaymentStatusBadge.vue` | 2 | 25 |

**إجمالي الملفات التي تستخدم أي StatusBadge: 22.**

**النقطة الجوهرية:** لا يوجد ملف واحد يستورد أكثر من نسخة — لكن **نفس الملفات تُحدِّد خرائط ألوان الحالة بشكل متكرر**. `Resources/js/Pages/Reports/Vehicles.vue` وحده فيه 6 إشارات StatusBadge.

### باقي المجلدات

| المجلد | ملفات | LOC | ملاحظة |
|---|---|---|---|
| `Components/WorkOrders/` | 31 | 13,562 | 🔴 أكبر حزمة — `WorkOrderServiceModal` 2,271 سطر |
| `Components/Purchasing/` | 8 | 4,400 | `PurchaseOrderFormModal` 1,021 · `PurchaseInvoiceFormModal` 976 |
| `Components/Quotes/` | 6 | 3,490 | `QuoteFormModal` 1,011 · `QuotePartModal` 967 |
| `Components/Customers/` | 4 | 2,320 | `CustomerFormModal` 1,358 |
| `Components/HR/Tabs/` | 7+3 | 3,966 | `EmployeeInfoTab` 852 · `EmployeeFinancialTab` 766 |
| `Components/Settings/Company/` | 5 | 1,540 | |
| `Components/Print/` | 6+3 | 3,410 | `TemplateDefaultA4` 1,593 · `TemplateThermal80` 880 |
| `Components/Subscription/` | 9 | 2,841 | `BankTransferReceiptUpload` 670 |
| `Components/Vehicles/` | 9 | 2,450 | `VehicleFormModal` 970 |
| `Components/CustomerPortal/` | 7 | 657 | |
| `Components/Billing/` | 6 | 804 | |
| `Components/DashboardV2/` | 20 | 2,016 | يشمل `PageHeader` الميت |
| `Components/HR/Modals/` | 1 | 264 | |
| `Components/Inventory/` | 3 | 646 | |
| `Components/System/` | 3 | 1,173 | |
| `Components/Common/` | 24 | 3,695 | |

---

## 6. المكوّنات الضخمة (≥300 سطر) — 58

| المكوّن | LOC |
|---|---|
| `WorkOrders/WorkOrderServiceModal.vue` | **2,271** |
| `Print/PrintTemplates/TemplateDefaultA4.vue` | 1,593 |
| `Customers/CustomerFormModal.vue` | 1,358 |
| `WorkOrders/WorkOrderFormModal.vue` | 1,257 |
| `Purchasing/PurchaseOrderFormModal.vue` | 1,021 |
| `Quotes/QuoteFormModal.vue` | 1,011 |
| `Purchasing/PurchaseInvoiceFormModal.vue` | 976 |
| `Vehicles/VehicleFormModal.vue` | 970 |
| `Quotes/QuotePartModal.vue` | 967 |
| `WorkOrders/WorkOrderServicesTab.vue` | 932 |
| `ServiceFormModal.vue` | 913 |
| `WorkOrders/WorkOrderCreditNoteModal.vue` | 908 |
| `WorkOrders/WorkOrderPartModal.vue` | 1,067 |
| `Print/PrintTemplates/TemplateThermal80.vue` | 880 |
| `HR/Tabs/EmployeeInfoTab.vue` | 852 |
| `WorkOrders/TechniciansSection.vue` | 813 |
| `PackageFormModal.vue` | 762 |
| `Common/PartsDisplay.vue` | 746 |
| `Settings/Company/CompanySubscriptionTab.vue` | 744 |
| `System/PaymentSettingsForm.vue` | 716 |
| `Subscription/BankTransferReceiptUpload.vue` | 670 |
| `WorkOrders/WorkOrderFinancialSummary.vue` | 487 |
| `App/AppButton.vue` | 487 |
| `WorkOrders/WorkOrderReturnModal.vue` | 462 |
| `WorkOrders/WorkOrderIssueMoreModal.vue` | 436 |
| `Settings/Company/CompanyProfileTab.vue` | 437 |
| `WorkOrders/WorkOrderHeader.vue` | 411 |
| `System/Website/PreviewPane.vue` | 336 |
| `Common/CommandPalette.vue` | 340 |
| `ServiceQuickEditModal.vue` | 341 |
| `NewServiceDefinitionModal.vue` | 391 |
| `NewPackageDefinitionModal.vue` | 349 |
| `WorkOrders/WorkOrderItemStatusControls.vue` | 347 |
| `DepartmentFormModal.vue` | 302 |
| `HR/EmployeeFormModal.vue` | 325 |
| `Inventory/AdjustmentModal.vue` | 309 |
| `Quotes/QuoteApprovalModal.vue` | 323 |
| `HR/Tabs/ContractsTab.vue` | 389 |
| `Subscription/SubscriptionHistoryTable.vue` | 428 |
| `Purchasing/PurchaseOrderItemModal.vue` | 653 |
| `Purchasing/SalesInvoiceFormModal.vue` | 571 |
| `Common/AppAttachmentManager.vue` | 559 |
| `Purchasing/SupplierQuickPaymentModal.vue` | 509 |
| `Vehicles/VehicleMergeModal.vue` | 499 |
| `HR/Tabs/PermissionsTab.vue` | 574 |
| `WorkOrders/WorkOrderServiceModal.vue` | 2,271 |
| `Customers/CustomerStatementModal.vue` | 524 |
| `Quotes/QuoteServiceModal.vue` | 823 |
| `WorkOrders/PaymentFormModal.vue` | 453 |
| `WorkOrders/WorkOrderItemTimer.vue` | 297 |
| `WorkOrders/PaymentsListModal.vue` | 347 |
| `WorkOrders/PaymentsSection.vue` | 515 |
| `WorkOrders/WorkOrderSuggestionsPanel.vue` | 415 |
| `HR/Tabs/EmployeeFinancialTab.vue` | 766 |
| `HR/Tabs/DocumentsTab.vue` | 320 |
| `HR/Tabs/LeavesTab.vue` | 284 |
| `Inventory/ReceiptModal.vue` | 239 |

**نمط متكرر:** مكوّنات «النموذج + حساب السعر/الضريبة + الإجماليات + اختيار البند» مكرَّرة عبر 6 مجالات:
`WorkOrderFormModal` · `QuoteFormModal` · `PurchaseOrderFormModal` · `PurchaseInvoiceFormModal` · `CustomerFormModal` · `VehicleFormModal`.

---

## 7. أنماط التكرار الوظيفية (لا markup فقط)

| النمط | الأداة القائمة | عدد المستخدمين | التكرار المكتشف |
|---|---|---|---|
| بحث/فرز/ترقيم | `useServerTable.ts` | **18 صفحة** | Reports، Services، Help، TaxPeriods، MyTasks، StockActivities — **محلياً** |
| تنسيق عملة/تاريخ | `useNumberFormat.js` | 85 ملف | 8 دوال محلية (محدّدة أدناه) |
| صلاحيات | `usePermission.js` | 28 ملف | فحص محلي في `Inventory/Parts/Show.vue:826-828` و`WorkOrders/Show.vue:962-964` |
| تأكيد حذف | `useConfirm.js` | 40 ملف | `window.confirm` في `HR/Payroll/Show.vue:620,646` و`EmployeePortal/MyTasks.vue:391,547` |
| إشعارات | `useToast.js` | 67 ملف | — |
| رفع ملفات | **لا يوجد معيار** | — | **21 ملف، 29 حقل** |
| **VAT/إجماليات** | `usePrintTotals.js` | 2 | **6 مواقع** — 🔴 منطق مالي |
| إعدادات الشركة | — | — | 5 ملفات تملك نسخة من نموذج settings |

### دوال التنسيق المحلية (المطابقة لـ `useNumberFormat`)

| الملف | السطر | الدالة |
|---|---|---|
| `Pages/Customers/Print.vue` | 127-133 | `formatMoney` |
| `Pages/Customers/PrintStatement.vue` | 182-188 | `formatMoney` |
| `Pages/Customers/Index.vue` | 824-826 | `formatCurrency` |
| `Pages/Customers/Show.vue` | 2398-2400 | `formatDate` |
| `Pages/Customers/Show.vue` | 2493-2495 | `formatPrice` |
| `Pages/Reports/Customers.vue` | 1278-1281 | `formatMoney` |
| `Pages/WorkOrders/WorkOrderSignatures.vue` | 31-38 | date-time |
| `Pages/Reports/Payments.vue` | 892-893 | YMD padding |

**الملاحظة الأهم:** الـ locale للعملة **غير موحّد** — `en-GB` و`en-US` و`ar-SA` موزّعة على ملفات مختلفة.

---

## 8. حساب الضريبة في الواجهة — 🔴 منطق أعمال خارج مكانه

| الملف | الأسطر | ماذا يحسب |
|---|---|---|
| `Composables/usePrintTotals.js` | 23-27, 38-39, 80-96, 142-147, 237-241 | gross total، VAT مع fallback `0.15`، line totals |
| `Components/WorkOrders/WorkOrderPartModal.vue` | 802-806, 921-924 | VAT + بناء `tax_amount` + `grand_total` |
| `Components/WorkOrders/WorkOrderReturnModal.vue` | 395-399 | VAT |
| `Components/WorkOrders/WorkOrderIssueMoreModal.vue` | 394-398 | VAT |
| `Pages/Quotes/Show.vue` | 1425-1447, 1482-1485 | tax factor + total |
| `Pages/Purchasing/Invoices/Show.vue` | 2293-2332 | return line net/tax/gross |
| `Components/Billing/InvoiceTotalsCard.vue` | 86-88 | **اشتقاق** نسبة VAT من الأرقام المعروضة |

**ثلاثة مصادر للـ VAT rate:** `0.15` (hardcoded)، `15`، و`15%` — ولا واحد منها يأتي من `TaxCalculator` (PHP).

**الإ Hurt:** اختلاف التقريب + inclusiveness + الـ default ⇒ **الفاتورة المعروضة قد لا تطابق المحفوظة**.

---

## 9. جرد Composables (19 تشغيلية)

| Composable | LOC | مستوردون | الحكم |
|---|---|---|---|
| `useNumberFormat.js` | 144 | **85** | ✅ المعيار — لكن 8 دوال محلية تتجاوزه |
| `useToast.js` | 119 | **67** | ✅ المعيار |
| `useConfirm.js` | 45 | **40** | ✅ المعيار — لكن `window.confirm` ما زال مستخدماً |
| `usePrint.js` | 407 | **41** | ✅ مركزي |
| `useLocalized.js` | 37 | 27 | ✅ |
| `usePermission.js` | 73 | **28** | ✅ — مع استثناء واحد |
| `useServerTable.ts` | 197 | **18** | ✅ — **أفضل مرشح للتعميم** |
| `useFormScrollToError.js` | 69 | 14 | ⚠️ 14 من عشرات النماذج |
| `useFormatters.js` | 142 | 11 | ⚠️ مع locals |
| `useTheme.js` | 85 | 4 | ✅ |
| `useDebounce.ts` | 103 | 3 | ✅ |
| `usePrintTotals.js` | 303 | 2 | 🔴 **منطق مالي — بلا اختبار** |
| `useVehicleSearch.js` | 44 | 2 | نطاق محدود |
| `useLocaleSync.js` | 36 | 1 | قليل الاستخدام |
| `useScreenRecorder.js` | 194 | 1 | ميزة محددة |
| `useWorkOrderItems.ts` | 198 | 1 | نطاق محدود |
| `useWorkOrderNotes.ts` | 333 | 1 | نطاق محدود |
| `useWorkOrderStatus.ts` | 256 | 1 | نطاق محدود |
| `useWorkOrderSuggestions.js` | 102 | 1 | نطاق محدود |

**فجوة التغطية:** `usePrintTotals.js` — الأكثر حساسية مالياً — **لا يملك ملف اختبار** (من 9 ملفات اختبار composable).

---

## 10. خصائص Props/Emits المتعارضة

| المكوّن | النموذج | ملاحظة |
|---|---|---|
| `BaseModal.vue` / `Modal.vue` / `ConfirmModal.vue` | `show` + `close` event | ✅ متّسق |
| `AppToggle` / `AppInput` / `AppSelect` / `AppTextarea` | `v-model` (`update:modelValue`) | ✅ متّسق |
| `DataTable.vue` | `modelValue` + events | ✅ |
| `AppSelect.vue:22` | `change` | ⚠️ بينما `AppInput` تستخدم `blur`/`focus` |
| `FormField.vue` | `label` + `error` slots | يُستخدم في 11 ملف فقط |

**لا يوجد `DropdownMenu` / `ActionMenu` / `Popover` مشترك** — القوائم مبنية inline داخل الصفحات ⇒ تختلف في الـ styling والـ keyboard behavior والـ close-on-outside-click.

---

## 11. الخلاصة

| الاستنتاج | الدليل |
|---|---|
| **الأساس موجود وقوي** | `DataTable` 100 · `SearchableSelect` 125 · `PageHeader` 115 · `AppToggle` 79 — تبنٍّ ممتاز للمكوّنات المعتمدة |
| **التكرار مُركَّب لا عشوائي** | 9,394 مقطع مكرر، أكبرها 1,444 سطر — نمط «صفحة + نسخة Tab» |
| **التكرار الأخطر ليس في المكوّنات** | 6 مواقع تحسب VAT في الواجهة — منطق أعمال لا عرض |
| **7 مكونات ميتة** | مؤكَّدة بمسار مطلق، منها `FormSection` المطلوب في الدستور |
| **لا مكتبة حالة** | يفسّر تكرار منطق الجداول في 15+ صفحة |
