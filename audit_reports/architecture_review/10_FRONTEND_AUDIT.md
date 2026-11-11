# 10 — تدقيق الواجهة الأمامية (Frontend Audit)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. الحكم العام

> **الأساس ممتاز، التبنّي متفاوت.**

المكوّنات المعتمدة في الدستور **موجودة وتُستخدم بكثافة** (`SearchableSelect` 125 · `DataTable` 100 · `AppToggle` 79 · `PageHeader` 115). لكن **1,322 `<button>` خام في 276 ملف** بينما `AppButton` مستخدم في **11 وسم فقط** ⇒ المعيار موجود والتبنّي ضعيف.

**المشكلة الأخطر ليست markup — بل 6 مواقع تحسب الضريبة داخل Vue.**

---

## 2. المكدّس

| التقنية | الإصدار | ملاحظة |
|---|---|---|
| Vue | `^3.4.0` | `<script setup>` + Composition API |
| Inertia | `^2.0.0` | |
| Vite | `^7.0.7` | + `scripts/generate-page-manifest.mjs` |
| Tailwind | `^3.2.1` | + `@tailwindcss/vite ^4` |
| vue-i18n | `^10.0.8` | |
| ApexCharts + vue3-apexcharts | `^5.12.0` | |
| Leaflet | `^1.9.4` | الخرائط |
| dompurify | `^3.4.5` | التنقية |
| vuedraggable | `^4.1.0` | |
| vue-tel-input | `^9.5.1` | إدخال الهاتف |
| jsbarcode | `^3.12.3` | الباركود |

### 🔴 لا توجد مكتبة إدارة حالة

**لا Pinia ولا Vuex.** `package.json` لا يحتوي أياً منهما.

**الأثر — يُفسّر تكرار منطق الجداول:**
- حالة الخادم من Inertia props
- الحالة المحلية `ref`/`reactive` داخل كل مكوّن
- ⇒ كل صفحة تبني `searchQuery` + `sortField` + `sortDirection` + `currentPage` + `perPage` + `filteredData` **بنفسها**

**9 صفحات** تستخدم `useServerTable` المعياري، بينما **15+ صفحة** (خاصة التقارير) تبني نفس المنطق محلياً.

---

## 3. بنية المكوّنات

### 3.1 القشرة المعتمدة

| المكوّن | LOC | الاستيراد | الحالة |
|---|---|---|---|
| `SearchableSelect.vue` | 408 | **125** | ✅ |
| `PageHeader.vue` | 158 | **115** | ✅ |
| `Common/DataTable.vue` | 284 | **100** | ✅ |
| `Common/AppToggle.vue` | 241 | **79** | ✅ |
| `BaseModal.vue` | 160 | 54 | ✅ |
| `BackButton.vue` | 64 | 50 | ✅ |
| `CustomDatePicker.vue` | 315 | 39 | ✅ |
| `Common/AppPrintButton.vue` | 130 | 32 | ✅ |
| `Tooltip.vue` | 117 | 31 | |
| `Modal.vue` | 222 | 29 | طبقة وسطى |
| `Common/AppEmptyState.vue` | 146 | 28 | ✅ |
| `Common/AppViewModeToggle.vue` | 104 | 23 | ✅ |
| `Common/AppPagination.vue` | 181 | 20 | ✅ |
| `Common/AppTabs.vue` | 153 | 20 | ✅ |
| `Vehicles/SaudiPlateDisplay.vue` | 124 | 15 | ✅ |
| `ConfirmModal.vue` | 170 | 12 | ✅ |
| `Common/FormField.vue` | 92 | 11 | ⚠️ ضعيف |
| `Common/SelectableCard.vue` | 145 | 10 | ✅ |
| `Common/AppTableSkeleton.vue` | 102 | 8 | |
| `Common/RadioGroup.vue` | 72 | **2** | ⚠️ مهمل |

### 3.2 🔴 المكوّنات الميتة — 7 مؤكَّدة

> **الطريقة:** `grep -rlF "@/Components/<path>.vue" resources/js app | wc -l` — بحث **بمسار مطلق** (لا substring).

| المكوّن | LOC | المراجع | ملاحظة |
|---|---|---|---|
| `DashboardV2/common/PageHeader.vue` | 50 | **0** | 🔴 **نسخة مكرّرة** بـ props مختلفة (`greeting`/`userName`/`date`) |
| `DashboardV2/common/DashboardTabs.vue` | 44 | **0** | |
| `Common/FormSection.vue` | 45 | **0** | 🔴 **مطلوب في الدستور** |
| `Common/AppCardSkeleton.vue` | 27 | **0** | |
| `Toggle.vue` | 39 | **0** | غلاف لـ`AppToggle` |
| `Checkbox.vue` | 34 | **0** | |
| `TextInput.vue` | 26 | **0** | مخلف Breeze |

**+ `App/AppSelect.vue`**: مسجَّل عامياً في `app.js:123` لكن **0 وسم `<AppSelect>`** ⇒ facility غير مستخدمة.

### 3.3 ✅ استخدام ديناميكي مؤكَّد (ليس ميتاً)

قوالب الطباعة الثلاثة — 0 استخدام مباشر، لكن:
```vue
<!-- resources/js/Components/Print/PrintEngine.vue:4 -->
<component :is="activeTemplate" ... />
<!-- :54-71 — map من القالب إلى المكوّن -->
```
`TemplateDefaultA4` · `TemplateThermal80` · `TemplateCustomersReport` ⇒ **ليست ميتة**.

---

## 4. 🔴 التكرار المُقاس

### 4.1 آلية الكشف

`/tmp/audit_dup3.py` — نافذة hash من **12 سطراً مُطبَّعاً**، تتبّع المقاطع عبر الملفات.

| المقياس | القيمة |
|---|---|
| الملفات الممسوحة | **1,408** |
| المقاطع المكرر المتطابقة | **9,394** |

### 4.2 أكبر التكرارات

| # | الأسطر | الملف أ | الملف ب |
|---|---|---|---|
| 1 | **1,444** | `Pages/System/Support/Tabs/HelpCenterTab.vue:3-1446` | `Pages/System/Help/Index.vue:3-1446` |
| 2 | 401 | `Components/Purchasing/PurchaseInvoiceFormModal.vue:159-559` | `Components/Purchasing/PurchaseOrderFormModal.vue:168-567` |
| 3 | 194 | `Pages/System/Settings/CompanySettings.vue:756-949` | `Pages/System/Settings/Tabs/CompanySettingsTab.vue:687-880` |
| 4 | 170 | `Pages/Settings/Centers/Show.vue:827-996` | `Pages/Settings/Company/Index.vue:1930-2099` |
| 5 | 164 | `Pages/System/Support/Tabs/FeedbacksTab.vue:54-217` | `Pages/System/Feedbacks/Index.vue:75-238` |
| 6 | 155 | `Pages/System/ContactMessages/Index.vue:91-245` | `Pages/System/Support/Tabs/ContactMessagesTab.vue:4-158` |
| 7 | 139 | `Components/WorkOrders/WorkOrderIssueMoreModal.vue:279-417` | `Components/WorkOrders/WorkOrderReturnModal.vue:280-418` |
| 8 | 114 | `Pages/System/Credits/WhatsappPackages.vue:185-298` | `Pages/System/Credits/Tabs/WhatsappPackagesTab.vue:93-206` |
| 9 | 103 | `Pages/Reports/StockActivities.vue:286-388` | `Pages/Reports/Suppliers.vue:312-414` |
| 10 | 87 | `Components/NewPackageDefinitionModal.vue:3-89` | `Components/NewServiceDefinitionModal.vue:3-89` |

### 4.3 🔴 الجذر: نمط «صفحة + نسخة Tab»

**14 مجلد `Tabs/`** تحتوي نسخاً من صفحات قائمة كاملة:

| المجلد | الملفات |
|---|---|
| `Pages/System/Support/Tabs/` | `ContactMessagesTab` · `FeedbacksTab` · `HelpCenterTab` |
| `Pages/System/Credits/Tabs/` | 8 ملفات (Sms×4 + Whatsapp×4) |
| `Pages/System/Website/Tabs/` | 8 ملفات |
| `Pages/System/Settings/Tabs/` | 4 ملفات |
| `Pages/System/Analytics/Tabs/` | 2 ملفات |
| `Pages/HR/Payroll/Tabs/` | 1 ملف |
| `Pages/Settings/Company/Tabs/` | — |

### 4.4 🔴 الدليل القاطع على الانحراف (Drift)

الحالة #1 (`HelpCenterTab` ↔ `Help/Index`) — `diff` يعطي **36 سطراً فقط من 4,129 ⇒ ≈99% متطابق**. والفرق **ليس شكلياً**:

```js
// HelpCenterTab.vue:1478-1482  ← النسخة في Tabs/
contact_whatsapp: props.settings?.contact_whatsapp || '+966 555 785 658',
contact_email:    props.settings?.contact_email    || 'support@khidmh.pro',
quick_start_items: JSON.parse(JSON.stringify(props.settings?.quick_start_items || [])),

// Help/Index.vue:1479-1483  ← النسخة المستقلة
contact_whatsapp: props.settings.contact_whatsapp || '+966 555 785 658',
contact_email:    props.settings.contact_email    || 'support@khidmh.pro',
quick_start_items: JSON.parse(JSON.stringify(props.settings.quick_start_items || [])),
```

**🔴 النسخة المستقلة تنهار (`Cannot read properties of null`) إن كان `settings` null — بينما نسخة `Tabs/` آمنة.**

⇒ **نسختان من نفس الميزة، واحدة منها هشّة.** هذه ليست مشكلة تجميلية — هي انحراف سلوكي فعلي مُثبت.

> **السبب الجذري:** لم يُعتمد مصدر واحد. الحل: **النسخة `Tabs/` هي المصدر**، والنسخة المستقلة تستوردها أو تنحى عنها.

---

## 5. 🔴 منطق مالي في الواجهة — 6 مواقع

| # | الملف | الأسطر | ما يحسبه |
|---|---|---|---|
| 1 | `Composables/usePrintTotals.js` | 23-27, 38-39, 80-96, 142-147, 237-241 | gross total · VAT مع fallback **`0.15`** · line totals |
| 2 | `Components/WorkOrders/WorkOrderPartModal.vue` | 802-806, 921-924 | VAT + بناء `tax_amount` + `grand_total` |
| 3 | `Components/WorkOrders/WorkOrderReturnModal.vue` | 395-399 | VAT |
| 4 | `Components/WorkOrders/WorkOrderIssueMoreModal.vue` | 394-398 | VAT |
| 5 | `Pages/Quotes/Show.vue` | 1425-1447, 1482-1485 | tax factor + total |
| 6 | `Pages/Purchasing/Invoices/Show.vue` | 2293-2332 | return line net/tax/gross |
| 7 | `Components/Billing/InvoiceTotalsCard.vue` | 86-88 | 🔴 **اشتقاق** نسبة VAT من الأرقام المعروضة |

### 5.1 🔴 ثلاثة مصادر لنسبة الضريبة

| القيمة | المصدر |
|---|---|
| `0.15` | `usePrintTotals.js:38-39, 142-147` (hardcoded) |
| `15` | استدعاءات أخرى |
| `15%` | `usePrintTotals.js:23-27` |

**ولا واحد منها يأتي من `TaxCalculator` (PHP).**

### 5.2 🔴 اشتقاق الـ VAT من المخرجات

```js
// Components/Billing/InvoiceTotalsCard.vue:86-88
// يشتق نسبة VAT من الأرقام المعروضة
```

**⇒ منطق دائري**: يعرض مبلغاً، ثم يستنتج النسبة من العرض، ثم يعرضها. خطأ التقريب في العرض يُنتج نسبة خاطئة.

### 5.3 🔴 لا يوجد اختبار لـ `usePrintTotals.js`

من 9 ملفات اختبار composable، **`usePrintTotals` غير مُغطّى** — رغم أنه **الأكثر حساسية مالياً في الواجهة كلها**.

**التوصية:**
1. الخادم يُرجع **snapshot محسوب** لكل سطر/فاتورة/مرتجع.
2. الواجهة **تعرض فقط**.
3. أي preview لحظي قبل الحفظ يستدعي **endpoint خادمي** أو DTO من نفس الخدمة المعيارية.

---

## 6. HTML الخام — أرقام مقيسة

| العنصر | الوسوم | الملفات | البديل |
|---|---|---|---|
| `<button>` | **1,322** | **276** | `AppButton` (11 وسم فقط) |
| `<input>` | **719** | **196** | `AppInput` |
| `<textarea>` | **122** | **67** | `AppTextarea` |
| `<select>` | **2** | **2** | ✅ ممتاز — لا تجاوز في الصفحات |
| `type="checkbox"` | 21 | 16 | `AppCheckbox` |
| `type="radio"` | 35 | 18 | `RadioGroup` |

**القراءة:** `<select>` الخام **مكادح** (2 فقط) — نجاح حقيقي. لكن الأزرار والحقول خام في الغالبية العظمى.

> **ملاحظة:** `DataTable.vue` نفسها تستخدم `<table>` خام — وهذا **صحيح** (المكوّن هو التنفيذ الأساسي).

---

## 7. 🔴 التكرار الوظيفي خارج markup

| النمط | المعيار القائم | المستخدمون | التكرار |
|---|---|---|---|
| بحث/فرز/ترقيم | `useServerTable.ts` | **18** | 15+ صفحة محلية |
| تنسيق عملة/تاريخ | `useNumberFormat.js` | **85** | 8 دوال محلية |
| تنسيق تاريخ/وقت | `useFormatters.js` | 11 | 4 دوال محلية |
| صلاحيات | `usePermission.js` | **28** | فحص محلي ×2 |
| تأكيد | `useConfirm.js` | **40** | `window.confirm` ×4 |
| إشعارات | `useToast.js` | **67** | — |
| رفع ملفات | ❌ **لا معيار** | — | 🔴 **21 ملف / 29 حقل** |
| **VAT/إجماليات** | `usePrintTotals.js` | 2 | 🔴 **6 مواقع** |
| إعدادات الشركة | ❌ **لا معيار** | — | 🔴 5 ملفات |

### 7.1 الدوال المحلية (المكرِّرة)

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

**🔴 الـ locale للعملة غير موحّد:** `en-GB` · `en-US` · `ar-SA` موزّعة على ملفات مختلفة ⇒ **نفس الرقم يُعرض بمن formats مختلفة**.

### 7.2 الصلاحيات — مساران

```js
// Pages/Inventory/Parts/Show.vue:826-828  ← خارج المعيار
const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;
```
```js
// Pages/WorkOrders/Show.vue:962-964  ← يقرأ من suggestionPage
```

**تقييم دقيق:** الخادم هو مصدر الحقيقة الفعلي ✅ — لا ثغرة. لكن **وجود مسار بديل في الواجهة يُنتج انطباعاً بأن فحص الواجهة هو الحماية**، ويزيد احتمال أن يفهم مطوّر جديد أن إخفاء الزر = حماية.

### 7.3 🔴 رفع الملفات — 6 عائلات

| العائلة | الملفات |
|---|---|
| raw file input + FormData | **21** (29 حقل) |
| `Common/AppAttachmentManager.vue` | 2 |
| `Common/ImportFileField.vue` | 2 |
| `WorkOrders/VehiclePhotoUploader.vue` | 2 |
| `Subscription/BankTransferReceiptUpload.vue` | 1 |
| logo/stamp handlers | Settings Website/Centers/Company + Feedback |

**الاختلافات بين العائلات:** الحد الأقصى للحجم · قائمة MIME · المعاينة · الإلغاء · إعادة المحاولة · شريط التقدّم · ربط الأخطاء.

**أخطر تكرار:** `Pages/System/Settings/CompanySettings.vue:828-894` و`Pages/System/Settings/Tabs/CompanySettingsTab.vue:759-825` — **رفع الشعار والختم متطابق تقريباً** ⇒ نفس المنطق في مكانين ⇒ ينحرفان.

---

## 8. أنماط Props/Emits

| المكوّن | النموذج | الحالة |
|---|---|---|
| `BaseModal` · `Modal` · `ConfirmModal` | `show` + `close` | ✅ متّسق |
| `AppToggle` · `AppInput` · `AppSelect` · `AppTextarea` | `v-model` | ✅ متّسق |
| `DataTable` | `modelValue` + events | ✅ |
| `AppSelect.vue:22` | `change` | ⚠️ بينما `AppInput` تستخدم `blur`/`focus` |
| `FormField` | `label` + `error` slots | يُستخدم في 11 ملف فقط |

**⛔ لا يوجد `DropdownMenu` / `ActionMenu` / `Popover` مشترك** ⇒ القوائم inline داخل الصفحات، بأشكال مختلفة في:
trigger styling · placement · keyboard handling · close-on-outside-click · mobile behavior.

---

## 9. المكوّنات الضخمة — 58 (>300 سطر)

| المكوّن | LOC | المشكلة |
|---|---|---|
| `WorkOrders/WorkOrderServiceModal.vue` | **2,271** | 🔴 **الأكبر** — form + pricing + tax + items + selection |
| `Print/PrintTemplates/TemplateDefaultA4.vue` | 1,593 | منطق تخطيط مختلط |
| `Customers/CustomerFormModal.vue` | 1,358 | form + validation + contacts + vehicles |
| `WorkOrders/WorkOrderFormModal.vue` | 1,257 | |
| `Purchasing/PurchaseOrderFormModal.vue` | 1,021 | |
| `Quotes/QuoteFormModal.vue` | 1,011 | 🔴 **نفس النمط** |
| `Purchasing/PurchaseInvoiceFormModal.vue` | 976 | |
| `Vehicles/VehicleFormModal.vue` | 970 | |
| `Quotes/QuotePartModal.vue` | 967 | |
| `WorkOrders/WorkOrderPartModal.vue` | 1,067 | |
| `WorkOrders/WorkOrderServicesTab.vue` | 932 | |
| `ServiceFormModal.vue` | 913 | |
| `WorkOrders/WorkOrderCreditNoteModal.vue` | 908 | |
| `Print/PrintTemplates/TemplateThermal80.vue` | 880 | |
| `HR/Tabs/EmployeeInfoTab.vue` | 852 | |
| `WorkOrders/TechniciansSection.vue` | 813 | |
| `PackageFormModal.vue` | 762 | |
| `Common/PartsDisplay.vue` | 746 | |
| `Settings/Company/CompanySubscriptionTab.vue` | 744 | |
| `System/PaymentSettingsForm.vue` | 716 | |
| `Subscription/BankTransferReceiptUpload.vue` | 670 | |

### 🔴 النمط المتكرر (الأهم)

**6 مكونات «نموذج + حساب السعر/الضريبة + الإجماليات + اختيار البند»** في مجالات مختلفة:
`WorkOrderFormModal` · `QuoteFormModal` · `PurchaseOrderFormModal` · `PurchaseInvoiceFormModal` · `CustomerFormModal` · `VehicleFormModal`

**⇒ قالب مشترك (`LineItemEditor` + `TotalsPanel`) يزيل أكبر مصدر للتكرار في الواجهة.**

###-component صغير بلا فائدة

`Common/LoadingSpinner.vue` (8) · `Common/ImportResult.vue` (15) · `Common/StatusBadge.vue` (21) · `Common/PaymentStatusBadge.vue` (25) · `Common/ReportDateRange.vue` (24) · `Common/AppCardSkeleton.vue` (27) · `PrimaryButton.vue` (13) · `SecondaryButton.vue` (13) · `DangerButton.vue` (13) · `InputError.vue` (15) · `InputLabel.vue` (14) · `TextInput.vue` (26)

**التقييم:** primitive صغير **نمط صحيح**. لكن `LoadingSpinner` و`InputError` يمكن أن يكونا slot داخل primitive أكبر، و`PrimaryButton`/`SecondaryButton`/`DangerButton` **أغلفة legacy** يمكن حذفها بـ`AppButton variant`.

---

## 10. جرد Composables (19 تشغيلية)

| Composable | LOC | مستوردون | الحكم |
|---|---|---|---|
| `useNumberFormat.js` | 144 | **85** | ✅ المعيار — 8 locals تتجاوزه |
| `useToast.js` | 119 | **67** | ✅ المعيار |
| `useConfirm.js` | 45 | **40** | ✅ المعيار — `window.confirm` باقٍ |
| `usePrint.js` | 407 | **41** | ✅ مركزي — لكن فيه 72 نصاً عربياً مثبَّتاً |
| `usePermission.js` | 73 | **28** | ✅ |
| `useServerTable.ts` | 197 | **18** | ✅ **أفضل مرشح للتعميم** |
| `useFormScrollToError.js` | 69 | 14 | ⚠️ 14 من عشرات النماذج |
| `useFormatters.js` | 142 | 11 | ⚠️ مع locals |
| `useTheme.js` | 85 | 4 | ✅ |
| `useDebounce.ts` | 103 | 3 | ✅ |
| **`usePrintTotals.js`** | 303 | 2 | 🔴 **منطق مالي — بلا اختبار** |
| `useVehicleSearch.js` | 44 | 2 | نطاق محدود |
| `useLocaleSync.js` | 36 | 1 | قليل |
| `useScreenRecorder.js` | 194 | 1 | ميزة محددة |
| `useWorkOrderItems.ts` | 198 | 1 | نطاق محدود |
| `useWorkOrderNotes.ts` | 333 | 1 | نطاق محدود |
| `useWorkOrderStatus.ts` | 256 | 1 | نطاق محدود |
| `useWorkOrderSuggestions.js` | 102 | 1 | نطاق محدود |

### ما يجب أن composable لكنه مكرَّر

| Concern | Composable مطلوب |
|---|---|
| رفع الملفات | `useFileUpload` — 21 ملف |
| إعدادات الشركة | `useCompanySettings` — 5 ملفات |
| البحث في التقارير | توسيع `useServerTable` |

---

## 11. الأداء

### 11.1 البناء ✅

```
✓ built in 32.09s
397 ملف asset · JS 6.8M · CSS 696K · public/build 7.8M
```

**أكبر chunks:**
| Chunk | الحجم | gzip |
|---|---|---|
| `theme-ClkoP7N7.js` | 725.95 kB | 195.71 kB |
| `vendor-misc-CLPz_cIN.js` | 456.27 kB | 128.82 kB |
| `vendor-vue-D4XvFTRt.js` | 139.04 kB | 53.24 kB |
| `AppLayout-Cou4RzfH.js` | 123.91 kB | 19.15 kB |
| `Show-ueRopwdT.js` | 270.86 kB | 59.87 kB |

> `theme-*.js` بحجم 726 kB هو **الأكبر** — يستحق تدقيقاً: هل هو chunk مستقل لمكتبة رسوم (ApexCharts)؟

### 11.2 ✅ تحسين سابق فعّال ومُثبت

`scripts/generate-page-manifest.mjs` يستبدل `import.meta.glob` — وقد نقل الأداء من **49% إلى 91-98%** و**362 طلباً إلى 57** (مُوثَّق في `.system-audit-report.md`).

**⇒ لا يوجد تراجع في الأداء منذ ذلك التحسين.**

### 11.3 🔴 `AppLayout.vue` — 3,600+ سطر

**أكبر ملف في الواجهة.** يحوي:
- shell كامل (root, sidebar, topbar)
- `isRtl` + تبديل الاتجاه
- `subscription_lifecycle` + شريط الاشتراك
- التنقّل + القوائم المنسدلة

**⇒ لا يمكن تقسيمه دون فك إشارات مرتفعة.** الأفضل: استخراج `AppSidebar`/`AppTopbar` (المهمة V-2 في `05_DESIGN_SYSTEM_AUDIT.md`).

### 11.4 ⚠️ إعادة التصيير

**لم يُتحقَّق** من `v-for` بلا `:key` صحيح أو watchers مكلفة. يحتاج Profiling فعلي.

---

## 12. إمكانية الوصول

| الميزة | الحالة | الدليل |
|---|---|---|
| focus-visible عام | ✅ | `reset.css:118-127` |
| reduced-motion | ✅ | `reset.css:129-138` |
| `<dialog>` + focus trap | ✅ | `Modal.vue:50-79,107-139` |
| عنوان المودال مرتبط | ✅ | `Modal.vue:66-71` |
| أرقام LTR في RTL | ✅ | `typography.css:140-149` |
| **أزرار أيقونة بلا اسم** | 🔴 | ~490 `<button>`؛ أمثلة: `SystemLayout.vue:141-153, 279-313, 315-329` |
| **`DataTable` غير قابل للكيبورد** | 🔴 | `:157-188` (`<th>` بـ`@click` فقط) · `:193-204` (`<tr>` بـ`@click` فقط) |
| `focus:outline-hidden` بلا بديل | 🔴 | `EmployeePortalLayout.vue:97, 174` |
| `FormField` بـ11 ملف | ⚠️ | حقول بلا `<label>` مرتبط |

---

## 13. RTL / Dark Mode

| المحور | الحالة |
|---|---|
| RTL عام | ✅ `i18n/index.js:13-18` يضبط `dir` |
| 🔴 `CustomPage.vue:16-18` | **يفرض RTL دائماً** (`\|\| true`) |
| RTL في المكوّنات | ⚠️ 130 موضع فيزيائي في Pages · 106 في Components · 29 في Layouts |
| ✅ `DataTable` | يستخدم `text-start`/`text-end` بشكل صحيح |
| Dark mode | ✅ أساس سليم |
| 🔴 feedback soft | لا تُستبدل في الوضع الداكن ⇒ تباين ضعيف |
| 🔴 البوابة | **تحذف** صنف `dark` عند التركيب |

---

## 14. الجولة 1 — إصلاحات الواجهة

| # | الملف | التغيير | المخاطرة | الجهد |
|---|---|---|---|---|
| 1 | `resources/js/i18n/lang/en.json` | `common.app_name` ⇒ `'Khidmh Pro'` | **صفر** | دقيقة |
| 2 | `resources/js/app.js:31` | إزالة السقوط `'Laravel'` | **صفر** | 5 دقائق |
| 3 | `resources/js/Pages/Public/CustomPage.vue:16-18` | إزالة `\|\| true` | **صفر** | دقيقة |
| 4 | `resources/js/Pages/System/Support/Tabs/HelpCenterTab.vue` | 🟢 **مصدر واحد** — `Help/Index.vue` يستوردها | متوسط | 3-4 ساعات |
| 5 | `resources/js/Components/DashboardV2/common/PageHeader.vue` | 🔴 **حذف** (0 مرجع) | **صفر** | دقيقة |
| 6 | `resources/js/Components/{TextInput,Toggle,Checkbox}.vue` + `Common/AppCardSkeleton.vue` | 🔴 **حذف** (0 مرجع) | **صفر** | 5 دقائق |
| 7 | `resources/js/Components/Common/FormSection.vue` | ⚠️ **إما يُستخدم (11 ملف) أو يُحذف** | **صفر** | يوم |
| 8 | `resources/js/Components/Common/DataTable.vue` | `tabindex` + `keydown` على `<th>` و`<tr>` | منخفض | 3 ساعات |
| 9 | `resources/js/Composables/usePrintTotals.js` | نقل الحسابات للخادم | متوسط | 3-5 أيام |

---

## 15. ملخص

| المحور | التقييم | الجملة القابلة للتنفيذ |
|---|---|---|
| المكوّنات المعتمدة | ✅ **قوية** | `SearchableSelect` 125 · `DataTable` 100 · `PageHeader` 115 |
| HTML الخام | 🔴 | 1,322 زر خام في 276 ملف — `AppButton` بـ11 فقط |
| التكرار | 🔴 | 9,394 مقطع — جذره نمط «صفحة + نسخة Tab» |
| الانحراف في النسخ | 🔴 | `Help/Index.vue` ينهار على `settings` null |
| المنطق المالي | 🔴 | 6 مواقع تحسب VAT في Vue |
| إدارة الحالة | ⚠️ | **لا Pinia** — 15+ صفحة تبني منطق الجداول محلياً |
| مكوّنات ميتة | ⚠️ | 7 مؤكَّدة (منها نسخة `PageHeader` مكرَّرة) |
| Composables | ✅ | 19 established — `useServerTable` هو المرشح للتعميم |
| الأداء | ✅ | بناء 32s · تحسين `page-manifest` فعّال |
| RTL | ⚠️ | 265 موضعاً فيزيائياً + عيب `\|\| true` |
| Dark Mode | ⚠️ | ألوان soft + البوابة تُلغي dark |
| a11y | ⚠️ | `DataTable` غير قابل للكيبورد |
| الاختبارات | ✅ | 44 ملف / 147 اختبار — كلها ناجحة · **لكن `usePrintTotals` بلا اختبار** |
