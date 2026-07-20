# 🚀 Carag V2 — Master Recovery Plan
## من 62.5% إلى 95%+ — خطة شاملة 6 أشهر

**التاريخ:** 2026-07-20
**المُعد:** Mavis
**المشروع:** Carag V2 (Khidmh Pro) — ورشة سيارات SaaS
**الـ Branch:** `integration/p0-print-settings`
**الهدف النهائي:** جاهز للإنتاج على مستوى SaaS عالمي (Stripe, Shopify, Odoo level)

---

# 🎯 الرؤية النهائية (Target State)

| المعيار | الحالي | الهدف |
|---|---|---|
| **Project Completion** | 62.5% | **95%+** |
| **Production Ready** | ❌ | ✅ |
| **Multi-region** | ❌ | ✅ |
| **Mobile App** | ❌ | ✅ (PWA + Native API) |
| **Payment Gateways** | 0/4 | 4/4 ✅ |
| **ZATCA Compliance** | Partial | Full ✅ |
| **API Coverage** | 25% | 95% |
| **Frontend Tests** | 0% | 70%+ |
| **Backend Tests** | 50% | 85%+ |
| **CI/CD** | CI only | Full pipeline |
| **Documentation** | 10% | 90% |
| **Mobile App** | None | PWA + Native-ready API |
| **Disaster Recovery** | None | RTO < 1h, RPO < 15m |
| **Multi-currency** | SAR only | 8+ currencies |
| **Multi-language** | AR/EN | AR/EN/FR/UR/HI |
| **Performance (P95)** | Unknown | < 200ms |
| **Uptime SLA** | None | 99.95% |

---

# 📅 الـ Timeline العام

```
2026
├── Q3 (Jul-Sep): Foundation + Critical Fixes
│   ├── Phase 0: Critical Blockers (2 أسابيع)
│   ├── Phase 1: Foundation (شهر 1)
│   └── Phase 2: Payment + ZATCA (شهر 2)
└── Q4 (Oct-Dec): Scale + Mobile + Reports
    ├── Phase 3: Mobile + Multi-tenant (شهر 3)
    ├── Phase 4: Reports + Analytics (شهر 4)
    └── Phase 5: DevOps + Production (شهر 5)
2027
└── Q1 (Jan-Mar): Polish + World-Class
    └── Phase 6: UX + Performance (شهر 6)
```

---

# 🚨 PHASE 0 — Critical Blockers (الأسبوع 1-2)
**"إيقاف النزيف" — الـ 7 ثغرات اللي تمنع الـ production**

### الأسبوع 1: Security & Data Integrity

#### Day 1-2: Fix XSS vulnerabilities (Stage 7 #57)
```bash
# Critical XSS fixes
- [ ] resources/js/Components/SystemAnnouncementBanner.vue:18
  - [ ] v-html → v-safe-html
- [ ] resources/js/Components/WorkOrders/WorkOrderItemModal.vue:110
  - [ ] v-html="status.icon" → use component
- [ ] resources/js/Components/SignatureModal.vue:47
  - [ ] v-html="tab.icon" → use component
- [ ] resources/js/Components/Dashboard/QuickActions.vue:92
  - [ ] v-html → use component
- [ ] resources/js/Components/Dashboard/StatsCard.vue:168
  - [ ] v-html → use component
- [ ] resources/js/Components/Dashboard/AlertsWidget.vue:102
  - [ ] v-html → use component
- [ ] resources/js/Components/Dashboard/DashboardCustomizer.vue:145
  - [ ] v-html → use component
- [ ] resources/js/Pages/Invoices/Sales/Index.vue:141
  - [ ] v-html → use component
- [ ] resources/js/Pages/Purchasing/Sales/Index.vue:127
  - [ ] v-html → use component
- [ ] resources/js/Pages/System/Security/TwoFactorSetup.vue:28
  - [ ] v-html="qrCode" → sanitize explicitly
- [ ] resources/js/Components/NotificationController.php (no v-html)
- [ ] ESLint rule: forbid v-html without safeHtml escape
```

**Verification:**
```bash
npm run build
grep -r "v-html" resources/js --include="*.vue" | grep -v "v-safe-html"
# يجب يكون 0 نتائج
```

#### Day 2-3: Mass Assignment Hardening (Stage 3 #25)
```php
// app/Models/User.php
- [ ] إزالة two_factor_secret من $fillable
- [ ] إزالة two_factor_recovery_codes من $fillable
- [ ] إضافة enableTwoFactor() method dedicated
- [ ] إضافة regenerateRecoveryCodes() method
- [ ] Review كل Models للـ $fillable
- [ ] Convert $guarded = [] إلى explicit $fillable
- [ ] ESLint-style check via grep:
  - [ ] grep -rn "guarded = \[\]" app/Models/
  - [ ] يجب يكون 0 نتائج (use $fillable only)
```

#### Day 3-4: Payment Type Normalization (Stage 2 #18)
```bash
# 1. Migration
- [ ] database/migrations/2026_07_25_normalize_payment_types.php
  - DB::statement("UPDATE payments SET type = LOWER(type) WHERE type IN ('Payment', 'Refund', 'Bad_debt')")
  - DB::statement("UPDATE invoices SET type = LOWER(type) WHERE type IN ('Invoice', 'Credit', 'Debit')")

# 2. Enum class
- [ ] app/Enums/PaymentType.php
  - case PAYMENT = 'payment';
  - case REFUND = 'refund';
  - case BAD_DEBT = 'bad_debt';

# 3. Model update
- [ ] app/Models/Payment.php
  - protected $casts = ['type' => PaymentType::class];
  
# 4. Remove mixed case from all queries
- [ ] grep -rn "Payment\|Refund\|Bad_debt" app/ --include="*.php"
- [ ] شيل كل mixed case
```

#### Day 4-5: Rate Limiting on Web Routes (Stage 2 #7)
```php
// app/Providers/RouteServiceProvider.php (new)
- [ ] RateLimiter::for('login', 5 per minute per IP)
- [ ] RateLimiter::for('register', 3 per minute per IP)
- [ ] RateLimiter::for('password-reset', 3 per minute per IP)
- [ ] RateLimiter::for('2fa-verify', 5 per minute per IP)
- [ ] RateLimiter::for('phone-otp', 3 per minute per phone)
- [ ] RateLimiter::for('api-public', 30 per minute per IP)
- [ ] RateLimiter::for('uploads', 20 per hour per user)
- [ ] RateLimiter::for('exports', 10 per hour per user)

// routes/web.php
- [ ] Apply throttle to all auth routes
- [ ] Apply throttle to public landing form
- [ ] Apply throttle to quote public link
```

### الأسبوع 2: Multi-tenant Hardening

#### Day 6-8: Tenant Scope Audit (Stage 1 #1)
```bash
# 1. CI lint rule
- [ ] .github/workflows/security.yml
  - run: |
      php scripts/check-tenant-scope.php
  # Fails build if model has tenant_id but no TenantScoped/CenterScoped trait

# 2. Fix 55 models (one per day)
- [ ] Customer, Supplier, Part, Service, User (has 2fa fields)
- [ ] WorkOrder, WorkOrderItem, WorkOrderItemPart
- [ ] WorkOrderActivity, WorkOrderPhoto, WorkOrderAttachment
- [ ] WorkOrderDamageMark, WorkOrderInspection, WorkOrderItemNote
- [ ] Quote, QuoteLine, QuotePart
- [ ] Invoice, InvoiceLine, InvoiceTemplate
- [ ] Payment (TenantScoped already)
- [ ] PurchaseOrder, PurchaseOrderItem
- [ ] PurchaseInvoice, PurchaseInvoiceLine
- [ ] PurchaseReturnInvoice, PurchaseReturnInvoiceLine
- [ ] GoodsReceivedNote, GrnItem
- [ ] InventoryBalance, InventoryMove
- [ ] InventoryTransfer, InventoryTransferItem
- [ ] Vehicle, VehicleColor, VehicleMake, VehicleModel
- [ ] VehicleMileageLog, VehicleConditionCategory, VehicleConditionItem
- [ ] Employee, EmployeeContract, EmployeeDocument
- [ ] Attendance, Leave, Payroll, PayrollItem, PayrollRun
- [ ] Allowance, Deduction, Shift, JobTitle, EmployeeType
- [ ] OtherPayment, EmployeeShift, HRRegulation, BiometricDevice
- [ ] CommunicationTemplate, ContactMessage
- [ ] NotificationSendLog, InternalNotification
- [ ] Setting, PaymentSettings, TenantTaxSetting, TenantZatcaSetting
- [ ] Integration, IntegrationLog, TenantIntegration
- [ ] AiMemory, AuditSnapshot, AuditViolation, SlowQueryLog
- [ ] Plan, Subscription, SubscriptionInvoice, SubscriptionPayment
- [ ] PromoCode, PromoCodeUsage, Installment
- [ ] SmsPackage, SmsPurchase, SmsUsageLog
- [ ] WhatsappPackage, WhatsappPurchase, WhatsappUsageLog
- [ ] TenantSmsBalance, TenantWhatsappBalance
- [ ] TenantAnnouncementRead, CenterSequence, CenterAddress, CenterWorkingHour
- [ ] IncomeCategory, Nationality, AdminActivityLog

# 3. Refactor withoutGlobalScopes
- [ ] WorkOrderController (12 uses)
- [ ] CustomerController (5 uses)
- [ ] VehicleController (3 uses)
- [ ] CompanyProfileController (1 use)
- [ ] CenterSettingsController (3 uses)
- [ ] ServiceController (1 use)
- [ ] CompanyTransactionController (1 use)
- [ ] WorkOrderWarrantiesController (2 uses)
- [ ] SuppliersController (1 use)
- [ ] PublicQuoteController (1 use)
- [ ] System/TenantsController (1 use)
```

**Pattern (refactor):**
```php
// BEFORE
$workOrders = $customer->workOrders()
    ->withoutGlobalScope('center_scoped')
    ->with([...])
    ->get();

// AFTER
$workOrders = $customer->workOrders()  // already scoped
    ->with([...])
    ->get();

// OR for cross-center legitimate access
$workOrders = $customer->workOrdersAcrossCenters()
    ->with([...])
    ->get();
```

#### Day 9-10: Policy Coverage for Critical Resources
```php
// app/Policies/PaymentPolicy.php (new)
- [ ] viewAny, view, create, update, delete, refund
- [ ] Super admin override
- [ ] Tenant + center scope check

// app/Policies/LeavePolicy.php (new)
- [ ] view, create, approve, reject, cancel
- [ ] Self-approve prevention
- [ ] Balance check

// app/Policies/PayrollPolicy.php (new)
- [ ] view, process, approve, disburse
- [ ] HR-only access

// app/Policies/WorkOrderItemPolicy.php (new)
- [ ] view, create, update, delete
- [ ] WO context check
- [ ] Read-only after WO done
```

**Phase 0 Deliverables:**
- ✅ 0 XSS vulnerabilities
- ✅ 0 mass assignment vulnerabilities
- ✅ Payment types normalized (enum)
- ✅ All web routes rate-limited
- ✅ 55 models with TenantScoped/CenterScoped
- ✅ 4 critical policies
- ✅ CI lint check

**Phase 0 Outcome: 65% completion (from 62.5%)**

---

# 🔧 PHASE 1 — Foundation Reinforcement (الشهر 1)
**"بناء الأساس المتين" — Architecture, Quality, Testing**

### الأسبوع 3-4: Architecture Refactor

#### Week 3: Extract FormRequests
```bash
# 193 inline validations → 80+ FormRequests
- [ ] audit-all-validate-calls.sh
- [ ] Create per-controller requests:

# Customers
- [ ] CustomerStoreRequest (exists, refactor)
- [ ] CustomerUpdateRequest (exists, refactor)
- [ ] CustomerMergeRequest (new)
- [ ] CustomerImportRequest (new)

# Vehicles
- [ ] VehicleStoreRequest (exists)
- [ ] VehicleUpdateRequest (exists)
- [ ] VehicleMileageLogRequest (new)

# Work Orders
- [ ] WorkOrderStoreRequest (exists, refactor)
- [ ] WorkOrderUpdateRequest (exists, refactor)
- [ ] WorkOrderItemRequest (new)
- [ ] WorkOrderInspectionRequest (new)
- [ ] WorkOrderDamageMarkRequest (new)
- [ ] WorkOrderSignatureRequest (new)
- [ ] WorkOrderTechnicianRequest (new)
- [ ] WorkOrderPartRequest (new)
- [ ] WorkOrderNoteRequest (new)
- [ ] WorkOrderStatusChangeRequest (new)
- [ ] WorkOrderConditionUpdateRequest (new)

# Quotes
- [ ] QuoteRequest (exists, refactor)
- [ ] QuoteApproveRequest (new)
- [ ] QuoteRejectRequest (new)
- [ ] QuoteLineRequest (new)
- [ ] QuotePartRequest (new)

# Invoices
- [ ] InvoiceCreateRequest (new)
- [ ] InvoiceIssueRequest (new)
- [ ] InvoiceCancelRequest (new)
- [ ] InvoiceCreditNoteRequest (new)

# Payments
- [ ] PaymentStoreRequest (new)
- [ ] PaymentUpdateRequest (new)
- [ ] PaymentRefundRequest (new)
- [ ] PaymentAllocationRequest (new)

# Inventory
- [ ] PartStoreRequest (new)
- [ ] PartUpdateRequest (new)
- [ ] PartCategoryRequest (new)
- [ ] WarehouseRequest (new)
- [ ] InventoryTransferRequest (new)
- [ ] StockAdjustmentRequest (new)
- [ ] StockTakeRequest (new)

# Purchasing
- [ ] SupplierStoreRequest (exists, refactor)
- [ ] SupplierUpdateRequest (new)
- [ ] PurchaseOrderStoreRequest (new)
- [ ] PurchaseOrderUpdateRequest (new)
- [ ] PurchaseOrderLineRequest (new)
- [ ] PurchaseInvoiceRequest (new)
- [ ] GoodsReceivedNoteRequest (new)
- [ ] PurchaseReturnRequest (new)

# HR
- [ ] EmployeeStoreRequest (new)
- [ ] EmployeeUpdateRequest (new)
- [ ] EmployeeContractRequest (new)
- [ ] EmployeeDocumentRequest (new)
- [ ] AttendanceRequest (new)
- [ ] LeaveStoreRequest (new)
- [ ] LeaveApproveRequest (new)
- [ ] PayrollProcessRequest (new)
- [ ] PayslipAdjustmentRequest (new)

# Auth
- [ ] LoginRequest (exists, refactor)
- [ ] RegisterRequest (new)
- [ ] PasswordResetRequest (new)
- [ ] TwoFactorVerifyRequest (new)
- [ ] PhoneOtpRequest (new)
- [ ] SetPasswordRequest (new)

# Settings
- [ ] CompanyProfileRequest (new)
- [ ] TaxSettingsRequest (new)
- [ ] ZatcaSettingsRequest (new)
- [ ] NumberingSettingsRequest (new)
- [ ] PrintSettingsRequest (new)
- [ ] UserStoreRequest (exists)
- [ ] UserUpdateRequest (exists)
- [ ] RoleRequest (new)
- [ ] BranchesRequest (new)

# System
- [ ] TenantStoreRequest (new)
- [ ] TenantUpdateRequest (new)
- [ ] PlanRequest (new)
- [ ] SubscriptionRequest (new)
- [ ] PromoCodeRequest (new)
- [ ] IntegrationRequest (new)
- [ ] AnnouncementRequest (new)
- [ ] CommunicationTemplateRequest (new)

# Reports
- [ ] ReportFilterRequest (new, generic)

# Public
- [ ] ContactFormRequest (new)
- [ ] QuotePublicApproveRequest (new)
- [ ] QuotePublicRejectRequest (new)
```

#### Week 4: Refactor God Controllers
```bash
# Split 14 God Controllers (Step 1: 4 critical)

# WorkOrderController (474 → 6 controllers)
- [ ] WorkOrderController (index, show) - 100 lines
- [ ] WorkOrderCreateController (create, store) - 100 lines
- [ ] WorkOrderUpdateController (edit, update) - 80 lines
- [ ] WorkOrderDeleteController (destroy) - 30 lines
- [ ] WorkOrderExportController (export, print) - 80 lines
- [ ] WorkOrderSearchController (apiIndex, search) - 100 lines

# CustomerController (288 → 4)
- [ ] CustomerController (index, show) - 100 lines
- [ ] CustomerCreateController - 80 lines
- [ ] CustomerUpdateController - 80 lines
- [ ] CustomerSearchController - 60 lines

# QuoteController (493 → 5)
- [ ] QuoteController (index, show) - 120 lines
- [ ] QuoteCreateController - 100 lines
- [ ] QuoteUpdateController - 80 lines
- [ ] QuoteApprovalController (exists) - 80 lines
- [ ] QuoteConversionController - 80 lines

# CompanyProfileController (494 → 4)
- [ ] CompanyProfileController (index) - 100 lines
- [ ] CompanyProfileUpdateController - 150 lines
- [ ] CompanySettingsController - 150 lines
- [ ] CompanyTransactionsController (resource) - 100 lines
```

### الأسبوع 5-6: Testing Infrastructure

#### Week 5: Frontend Testing Setup
```bash
# Setup
- [ ] pnpm add -D @vitest/coverage-v8
- [ ] pnpm add -D @vue/test-utils (already installed)
- [ ] vitest.config.ts (already exists, configure)
- [ ] Setup MSW for API mocking

# CI enforcement
- [ ] Remove --passWithNoTests from package.json
- [ ] Add minimum coverage threshold (50% → 70%)
- [ ] Add coverage report to CI

# Critical tests (Week 5)
- [ ] 11 Composables tests (1 per file)
  - [ ] useTheme.test.js
  - [ ] useFormatters.test.js
  - [ ] useLocaleSync.test.js
  - [ ] useLocalized.test.js
  - [ ] useNumberFormat.test.js
  - [ ] usePermission.test.js
  - [ ] useToast.test.js
  - [ ] useConfirm.test.js
  - [ ] useWorkOrderItems.test.js
  - [ ] useWorkOrderNotes.test.js
  - [ ] useWorkOrderStatus.test.js

- [ ] App components tests
  - [ ] AppButton.test.ts
  - [ ] AppInput.test.ts
  - [ ] AppSelect.test.ts
  - [ ] AppTextarea.test.ts
  - [ ] AppCheckbox.test.ts
  - [ ] ConfirmModal.test.ts
  - [ ] BaseModal.test.ts
  - [ ] LoadingSpinner.test.ts

- [ ] Plugins
  - [ ] safeHtml.test.js (XSS prevention!)
  - [ ] arabicNumerals.test.js

- [ ] Utilities
  - [ ] pricing.test.js
  - [ ] phone.test.js
  - [ ] permissions.test.js
```

#### Week 6: Backend Testing Expansion
```bash
# Goal: from 57 tests to 200+ tests
# Add per-module Feature tests:

# Work Orders (30 new tests)
- [ ] WorkOrderCrudTest (exists, expand)
- [ ] WorkOrderStatusFlowTest (new)
- [ ] WorkOrderItemManagementTest (new)
- [ ] WorkOrderPartManagementTest (new)
- [ ] WorkOrderInspectionTest (new)
- [ ] WorkOrderSignatureTest (new)
- [ ] WorkOrderTechnicianAssignmentTest (new)
- [ ] WorkOrderPrintTest (new)
- [ ] WorkOrderTaxTest (new)

# Invoices (20 new)
- [ ] InvoiceCreationTest (new)
- [ ] InvoiceZatcaTest (new)
- [ ] InvoicePaymentStatusTest (exists, expand)
- [ ] InvoiceCancelTest (new)
- [ ] InvoiceRefundTest (new)

# Payments (15 new)
- [ ] PaymentRecordTest (new)
- [ ] PaymentRefundTest (new)
- [ ] PaymentCrossSyncTest (exists, expand)
- [ ] AutoInvoiceOnFullPaymentTest (exists, expand)

# Inventory (25 new)
- [ ] InventoryReceiptTest (new)
- [ ] InventoryIssueTest (new)
- [ ] InventoryTransferTest (new)
- [ ] InventoryAdjustmentTest (new)
- [ ] InventoryServiceTest (new, unit)
- [ ] WACCalculationTest (new)
- [ ] NegativeStockTest (new)

# Purchasing (20 new)
- [ ] PurchaseOrderFlowTest (new)
- [ ] GoodsReceivedNoteTest (new)
- [ ] PurchaseInvoiceTest (new)
- [ ] PurchaseReturnTest (new)
- [ ] ThreeWayMatchTest (new)

# HR (25 new)
- [ ] EmployeeCrudTest (new)
- [ ] AttendanceCalculationTest (new)
- [ ] LeaveRequestFlowTest (new)
- [ ] PayrollCalculationTest (new)
- [ ] GosiCalculationTest (new)
- [ ] EndOfServiceTest (new)

# Auth (10 new)
- [ ] RolePermissionsContractTest (exists, expand)
- [ ] TwoFactorFlowTest (new)
- [ ] PhoneOtpTest (new)
- [ ] PasswordResetTest (exists, expand)

# API (15 new)
- [ ] ApiAuthenticationTest (new)
- [ ] ApiRateLimitTest (new)
- [ ] MobileApiCustomerTest (new)
- [ ] MobileApiWorkOrderTest (new)

# Security (10 new)
- [ ] IDorProtectionTest (new)
- [ ] MassAssignmentTest (new)
- [ ] XssProtectionTest (new)
- [ ] SqlInjectionTest (new)
- [ ] AuthorizationTest (new)

# Unit tests (20+ new)
- [ ] PricingHelperTest (new)
- [ ] PhoneNumberTest (new)
- [ ] TenancyContextTest (new)
- [ ] WorkOrderSuggestionServiceTest (new)
- [ ] InventoryServiceTest (new)
- [ ] PaymentServiceTest (new)
- [ ] All 4 Payment Gateways Test (new)
- [ ] TwoFactorServiceTest (new)
- [ ] NotificationServiceTest (new)
- [ ] TaxCalculatorTest (new)
```

### الأسبوع 7-8: Events, Listeners, Jobs

#### Week 7: Events + Listeners (Decoupling)
```bash
# app/Events/ (25 events)
- [ ] WorkOrderCreated
- [ ] WorkOrderUpdated
- [ ] WorkOrderStatusChanged
- [ ] WorkOrderItemAdded
- [ ] WorkOrderCompleted
- [ ] WorkOrderCancelled
- [ ] WorkOrderOnHold
- [ ] WorkOrderResumed
- [ ] QuoteCreated
- [ ] QuoteApproved
- [ ] QuoteRejected
- [ ] QuoteConverted
- [ ] InvoiceCreated
- [ ] InvoiceIssued
- [ ] InvoiceCancelled
- [ ] InvoiceRefunded
- [ ] PaymentRecorded
- [ ] PaymentRefunded
- [ ] CustomerCreated
- [ ] VehicleAdded
- [ ] SupplierCreated
- [ ] PartCreated
- [ ] StockLow
- [ ] StockOut
- [ ] EmployeeHired
- [ ] EmployeeTerminated
- [ ] LeaveRequested
- [ ] LeaveApproved
- [ ] LeaveRejected
- [ ] PayrollProcessed
- [ ] SubscriptionRenewed
- [ ] SubscriptionExpired
- [ ] LoginSuccessful
- [ ] LoginFailed
- [ ] PasswordChanged
- [ ] TwoFactorEnabled
- [ ] TwoFactorDisabled
- [ ] BackupCompleted
- [ ] ReportGenerated

# app/Listeners/ (30 listeners)
- [ ] LogActivityOnWorkOrderStatusChange
- [ ] SendNotificationOnWorkOrderCreated
- [ ] SendNotificationOnWorkOrderCompleted
- [ ] UpdateInvoiceStatusOnPayment
- [ ] CreateJournalEntryOnInvoice
- [ ] DeductInventoryOnPartIssued
- [ ] RestockInventoryOnPartReturned
- [ ] UpdateCustomerStats
- [ ] SendWelcomeEmailOnCustomerCreated
- [ ] SendWelcomeEmailOnEmployeeHired
- [ ] NotifyManagerOnEmployeeAbsence
- [ ] SendLeaveDecisionNotification
- [ ] GeneratePayrollOnPeriodClose
- [ ] UpdateSubscriptionStatus
- [ ] SendSubscriptionRenewalReminder
- [ ] SendPaymentReceiptEmail
- [ ] SendInvoiceEmail
- [ ] LogLoginAttempt
- [ ] SendLoginAlertOnSuspiciousActivity
- [ ] InvalidateCacheOnModelUpdate
- [ ] UpdateSearchIndex
- [ ] BroadcastRealTimeUpdate (WebSocket)
- [ ] SendPushNotification
- [ ] TrackAiUsageOnCompletion
- [ ] ScheduleReportEmail
- [ ] SyncToExternalSystem
- [ ] TriggerWebhookOnEvent
- [ ] UpdateAnalyticsCounters
- [ ] InvalidateFrontendCache
- [ ] NotifySlackChannel
```

#### Week 8: Jobs (Async Operations)
```bash
# app/Jobs/ (20 jobs)
- [ ] AutoCreateInvoiceForDoneWorkOrderJob (replace Payment::boot)
- [ ] ProcessSubscriptionRenewalJob
- [ ] SendSubscriptionExpiryReminderJob
- [ ] SendWhatsappMessageJob
- [ ] SendSmsMessageJob
- [ ] SendEmailJob (with template)
- [ ] NotifyOwnerJob (replace sync)
- [ ] IssueZatcaInvoiceJob (ZATCA API call)
- [ ] ProcessBiometricBatchJob
- [ ] GenerateReportJob
- [ ] ExportDataJob
- [ ] BackupDatabaseJob
- [ ] CleanupOldLogsJob
- [ ] RecalculateWorkOrderTotalsJob
- [ ] SendWelcomeEmailJob
- [ ] SendPaymentReceiptJob
- [ ] ProcessScheduledReportsJob
- [ ] SyncIntegrationDataJob
- [ ] InvalidateCdnCacheJob
- [ ] GenerateInvoicePdfJob

# Replace sync calls in:
- [ ] Payment::boot() → dispatch(AutoCreateInvoiceJob)
- [ ] WorkOrderStatusController::complete() → dispatch(IssueZatcaJob)
- [ ] All NotificationService::notifyOwner() → dispatch(NotifyOwnerJob)
- [ ] All Mail::send() → dispatch(SendEmailJob)
- [ ] SmsService::send() → dispatch(SendSmsJob)
- [ ] WhatsappService::send() → dispatch(SendWhatsappJob)
```

### Phase 1 Deliverables
- ✅ 80+ FormRequests (DRY)
- ✅ 14 God Controllers → 40+ focused
- ✅ 200+ backend tests
- ✅ 30+ frontend tests
- ✅ 38 events
- ✅ 30 listeners
- ✅ 20 jobs (replacing sync side effects)
- ✅ 0 missing critical policies

**Phase 1 Outcome: 75% completion (from 65%)**

---

# 💳 PHASE 2 — Payment & ZATCA Integration (الشهر 2)
**"تفعيل الربح" — payment gateways + ZATCA compliance**

### الأسبوع 9-10: Payment Gateway Integration

#### Week 9: Wire Up 4 Gateways
```bash
# Step 1: Dead code resurrection
- [ ] app/Services/Payment/CheckoutService.php (new)
  - Wraps PaymentManager + Gateway + Checkout flow

# Step 2: Controllers (per gateway)
- [ ] app/Http/Controllers/App/Checkout/CheckoutController.php
  - Methods: start, success, cancel, failure
- [ ] app/Http/Controllers/App/Checkout/TamaraController.php
  - createSession, handleWebhook
- [ ] app/Http/Controllers/App/Checkout/TabbyController.php
  - createSession, handleWebhook
- [ ] app/Http/Controllers/App/Checkout/TapController.php
  - createCharge, handleWebhook
- [ ] app/Http/Controllers/App/Checkout/MoyasarController.php
  - createInvoice, handleWebhook

# Step 3: Webhook handlers (separate routes)
- [ ] app/Http/Controllers/Webhook/TamaraWebhookController.php
- [ ] app/Http/Controllers/Webhook/TabbyWebhookController.php
- [ ] app/Http/Controllers/Webhook/TapWebhookController.php
- [ ] app/Http/Controllers/Webhook/MoyasarWebhookController.php
- [ ] app/Http/Controllers/Webhook/StripeWebhookController.php (add Stripe)

# Step 4: Webhook signature verification
- [ ] app/Http/Middleware/VerifyWebhookSignature.php
  - Verify HMAC for each provider
  - Idempotency: prevent duplicate processing

# Step 5: Routes
- [ ] routes/web.php
  Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::post('/start', [CheckoutController::class, 'start']);
    Route::get('/success', [CheckoutController::class, 'success']);
    Route::get('/cancel', [CheckoutController::class, 'cancel']);
    Route::get('/failure', [CheckoutController::class, 'failure']);
  });
  
  Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/tamara', [TamaraWebhookController::class, 'handle']);
    Route::post('/tabby', [TabbyWebhookController::class, 'handle']);
    Route::post('/tap', [TapWebhookController::class, 'handle']);
    Route::post('/moyasar', [MoyasarWebhookController::class, 'handle']);
  });

# Step 6: Fix hard-coded values
- [ ] TamaraGateway::initiate() - remove hard-coded country/city/instalments
- [ ] TabbyGateway - same
- [ ] TapGateway - same
- [ ] MoyasarGateway - same

# Step 7: Tests
- [ ] tests/Feature/Checkout/TamaraCheckoutTest.php
- [ ] tests/Feature/Checkout/TabbyCheckoutTest.php
- [ ] tests/Feature/Checkout/TapCheckoutTest.php
- [ ] tests/Feature/Checkout/MoyasarCheckoutTest.php
- [ ] tests/Feature/Checkout/WebhookSignatureTest.php
- [ ] tests/Feature/Checkout/PaymentReconciliationTest.php
- [ ] tests/Unit/Services/Payment/PaymentGatewayInterfaceTest.php
- [ ] tests/Unit/Services/Payment/CheckoutServiceTest.php
```

#### Week 10: Payment UX
```bash
# Frontend pages
- [ ] resources/js/Pages/App/Checkout/Start.vue
- [ ] resources/js/Pages/App/Checkout/Success.vue
- [ ] resources/js/Pages/App/Checkout/Cancel.vue
- [ ] resources/js/Pages/App/Checkout/Failure.vue
- [ ] resources/js/Pages/App/Payments/Index.vue (admin view)
- [ ] resources/js/Pages/App/Payments/Reconciliation.vue
- [ ] resources/js/Pages/App/Payments/Refund.vue

# Components
- [ ] resources/js/Components/Checkout/PaymentMethodSelector.vue
- [ ] resources/js/Components/Checkout/TamaraWidget.vue
- [ ] resources/js/Components/Checkout/TabbyWidget.vue
- [ ] resources/js/Components/Checkout/TapWidget.vue
- [ ] resources/js/Components/Checkout/MoyasarWidget.vue
- [ ] resources/js/Components/Common/PaymentStatusBadge.vue
- [ ] resources/js/Components/Common/RefundModal.vue

# Server-side rendering for payment iframes
- [ ] SSR for checkout success/cancel pages
```

### الأسبوع 11-12: ZATCA Compliance

#### Week 11: ZATCA API Integration
```bash
# ZATCA APIs
- [ ] app/Services/Zatca/ZatcaService.php (new)
  - onboardTenant (CSR generation)
  - complianceCSID
  - productionCSID
  - complianceClearance
  - complianceReporting
  - productionClearance
  - productionReporting
  - generateInvoiceHash
  - signInvoice
  - generateQR

# [ ] app/Services/Zatca/ZatcaCryptoService.php
  - ECDSA signing
  - CSR generation
  - Certificate parsing
  - Hash generation (SHA-256)

# [ ] app/Jobs/IssueZatcaInvoiceJob.php
  - Handles async ZATCA submission
  - Retries on failure (3x with exponential backoff)

# [ ] app/Http/Controllers/App/Invoice/ZatcaController.php
  - onboard, getStatus, retryFailed

# Models
- [ ] app/Models/ZatcaCertificate.php (new)
  - tenant_id, certificate, expiry, type (compliance/production)
  - private_key_encrypted (encrypt at rest)
- [ ] app/Models/ZatcaSubmission.php (new)
  - invoice_id, request_xml, response_xml, status, retry_count
- [ ] app/Models/ZatcaErrorLog.php (new)
  - tenant_id, invoice_id, error_code, error_message, raw_request, raw_response

# Migration
- [ ] database/migrations/2026_09_15_create_zatca_tables.php

# Refactor existing
- [ ] app/Services/InvoiceService::issueInvoice() → dispatch(IssueZatcaInvoiceJob)
- [ ] app/Models/Invoice → add zatca_submission_id, zatca_status
- [ ] app/Models/Invoice → remove direct ZATCA hash logic
- [ ] app/Services/InvoiceService → delegate to ZatcaService

# Routes
- [ ] POST /app/invoices/{invoice}/zatca/submit
- [ ] POST /app/invoices/{invoice}/zatca/clear
- [ ] GET /app/invoices/{invoice}/zatca/status
- [ ] POST /app/settings/zatca/onboard
- [ ] GET /app/settings/zatca/certificates

# Tests
- [ ] tests/Unit/Services/Zatca/ZatcaCryptoServiceTest.php
- [ ] tests/Unit/Services/Zatca/ZatcaServiceTest.php
- [ ] tests/Feature/Zatca/OnboardingTest.php
- [ ] tests/Feature/Zatca/InvoiceClearanceTest.php
- [ ] tests/Feature/Zatca/InvoiceReportingTest.php
- [ ] tests/Feature/Zatca/CertificateRenewalTest.php
- [ ] tests/Feature/Zatca/ErrorHandlingTest.php
```

#### Week 12: ZATCA Frontend + Reporting
```bash
# Frontend
- [ ] resources/js/Pages/App/Settings/Zatca/Index.vue
  - Onboarding wizard
  - Certificate management
  - Compliance status
  - Production switch
- [ ] resources/js/Pages/App/Settings/Zatca/Certificates.vue
- [ ] resources/js/Pages/App/Settings/Zatca/OnboardingWizard.vue
- [ ] resources/js/Pages/App/Invoices/ZatcaStatus.vue
- [ ] resources/js/Components/Zatca/OnboardingStep.vue
- [ ] resources/js/Components/Zatca/CertificateCard.vue
- [ ] resources/js/Components/Zatca/SubmissionStatus.vue
- [ ] resources/js/Components/Zatca/ErrorLog.vue

# Reports
- [ ] app/Http/Controllers/App/Reports/ZatcaReportController.php
  - Daily submission report
  - Failed submissions
  - Compliance status
  - Tax summary
- [ ] resources/js/Pages/App/Reports/Zatca/Index.vue
- [ ] resources/js/Pages/App/Reports/Zatca/Failed.vue
- [ ] resources/js/Pages/App/Reports/Zatca/Summary.vue

# Console commands
- [ ] app/Console/Commands/Zatca/RetryFailedSubmissions.php
- [ ] app/Console/Commands/Zatca/CheckCertificateExpiry.php
- [ ] app/Console/Commands/Zatca/DailyReport.php

# Tests
- [ ] tests/Feature/Reports/ZatcaReportsTest.php
```

### Phase 2 Deliverables
- ✅ 5 payment gateways integrated (4 + Stripe)
- ✅ Webhook handlers with signature verification
- ✅ ZATCA full integration (clearance + reporting)
- ✅ ZATCA certificate management
- ✅ Failed submission retry
- ✅ 50+ payment tests
- ✅ 30+ ZATCA tests

**Phase 2 Outcome: 80% completion (from 75%)**

---

# 📱 PHASE 3 — Mobile API & Multi-tenant Hardening (الشهر 3)
**"التوسع" — Mobile-ready API + region support**

### الأسبوع 13-14: Mobile API (v1)

#### Week 13: Core API
```bash
# Authentication API
- [ ] app/Http/Controllers/Api/V1/Auth/LoginController.php
  - POST /api/v1/auth/login (email/phone)
  - POST /api/v1/auth/logout
  - POST /api/v1/auth/refresh
  - GET /api/v1/auth/me
  - POST /api/v1/auth/2fa/enable
  - POST /api/v1/auth/2fa/verify

# Customer API
- [ ] app/Http/Controllers/Api/V1/Customers/CustomerController.php
  - GET /api/v1/customers (paginated, filterable)
  - POST /api/v1/customers
  - GET /api/v1/customers/{id}
  - PUT /api/v1/customers/{id}
  - DELETE /api/v1/customers/{id}
  - GET /api/v1/customers/{id}/vehicles
  - GET /api/v1/customers/{id}/work-orders
  - GET /api/v1/customers/{id}/invoices
  - POST /api/v1/customers/{id}/check-phone

# Vehicle API
- [ ] app/Http/Controllers/Api/V1/Vehicles/VehicleController.php
  - GET /api/v1/vehicles
  - POST /api/v1/vehicles
  - GET /api/v1/vehicles/{id}
  - PUT /api/v1/vehicles/{id}
  - DELETE /api/v1/vehicles/{id}
  - POST /api/v1/vehicles/check-plate
  - GET /api/v1/vehicles/{id}/service-history
  - POST /api/v1/vehicles/{id}/mileage-logs

# WorkOrder API
- [ ] app/Http/Controllers/Api/V1/WorkOrders/WorkOrderController.php
  - GET /api/v1/work-orders
  - POST /api/v1/work-orders
  - GET /api/v1/work-orders/{id}
  - PUT /api/v1/work-orders/{id}
  - DELETE /api/v1/work-orders/{id}
  - POST /api/v1/work-orders/{id}/items
  - DELETE /api/v1/work-orders/{id}/items/{itemId}
  - POST /api/v1/work-orders/{id}/status
  - POST /api/v1/work-orders/{id}/photos
  - POST /api/v1/work-orders/{id}/signatures
  - GET /api/v1/work-orders/{id}/timeline

# Quote API
- [ ] app/Http/Controllers/Api/V1/Quotes/QuoteController.php
  - GET /api/v1/quotes
  - POST /api/v1/quotes
  - GET /api/v1/quotes/{id}
  - PUT /api/v1/quotes/{id}
  - DELETE /api/v1/quotes/{id}
  - POST /api/v1/quotes/{id}/approve
  - POST /api/v1/quotes/{id}/reject
  - POST /api/v1/quotes/{id}/convert
- [ ] app/Http/Controllers/Api/V1/Quotes/PublicQuoteController.php
  - GET /api/v1/public/quotes/{uuid}
  - POST /api/v1/public/quotes/{uuid}/approve
  - POST /api/v1/public/quotes/{uuid}/reject

# Invoice API
- [ ] app/Http/Controllers/Api/V1/Invoices/InvoiceController.php
  - GET /api/v1/invoices
  - GET /api/v1/invoices/{id}
  - POST /api/v1/invoices/{id}/payments
  - GET /api/v1/invoices/{id}/pdf

# Payment API
- [ ] app/Http/Controllers/Api/V1/Payments/PaymentController.php
  - GET /api/v1/payments
  - GET /api/v1/payments/{id}
  - POST /api/v1/payments/{id}/refund

# Notification API
- [ ] app/Http/Controllers/Api/V1/Notifications/NotificationController.php
  - GET /api/v1/notifications
  - POST /api/v1/notifications/{id}/read
  - POST /api/v1/notifications/read-all
  - GET /api/v1/notifications/unread-count

# Reports API
- [ ] app/Http/Controllers/Api/V1/Reports/ReportController.php
  - GET /api/v1/reports/dashboard
  - GET /api/v1/reports/revenue?from=&to=
  - GET /api/v1/reports/top-customers
  - GET /api/v1/reports/top-parts

# Offline Sync
- [ ] app/Http/Controllers/Api/V1/Sync/OfflineSyncController.php
  - POST /api/v1/sync/work-orders (batch upload)
  - GET /api/v1/sync/changes?since=timestamp
  - POST /api/v1/sync/resolve-conflicts

# Profile API
- [ ] app/Http/Controllers/Api/V1/Profile/ProfileController.php
  - GET /api/v1/profile
  - PUT /api/v1/profile
  - POST /api/v1/profile/photo
  - POST /api/v1/profile/change-password
  - POST /api/v1/profile/switch-center
```

#### Week 14: API Resources + Documentation
```bash
# API Resources (15+)
- [ ] app/Http/Resources/Api/V1/CustomerResource.php
- [ ] app/Http/Resources/Api/V1/VehicleResource.php
- [ ] app/Http/Resources/Api/V1/WorkOrderResource.php
- [ ] app/Http/Resources/Api/V1/WorkOrderItemResource.php
- [ ] app/Http/Resources/Api/V1/WorkOrderPartResource.php
- [ ] app/Http/Resources/Api/V1/QuoteResource.php
- [ ] app/Http/Resources/Api/V1/QuoteLineResource.php
- [ ] app/Http/Resources/Api/V1/InvoiceResource.php
- [ ] app/Http/Resources/Api/V1/InvoiceLineResource.php
- [ ] app/Http/Resources/Api/V1/PaymentResource.php
- [ ] app/Http/Resources/Api/V1/PartResource.php
- [ ] app/Http/Resources/Api/V1/ServiceResource.php
- [ ] app/Http/Resources/Api/V1/EmployeeResource.php
- [ ] app/Http/Resources/Api/V1/UserResource.php
- [ ] app/Http/Resources/Api/V1/NotificationResource.php

# Standardized error responses
- [ ] app/Http/Responses/ApiError.php
  - validation, notFound, unauthorized, forbidden, server, business
- [ ] app/Exceptions/Handler.php (override)
  - Render JSON for all API exceptions

# OpenAPI documentation
- [ ] composer require darkaonline/l5-swagger
- [ ] config/l5-swagger.php
- [ ] Generate from Controllers
- [ ] Serve at /api/documentation

# API versioning
- [ ] routes/api.php → prefix v1
- [ ] Add Sunset header for v0 → v1 migration
- [ ] Deprecation policy documented

# Rate limiting
- [ ] app/Providers/RouteServiceProvider.php
  - Per-user, per-tenant rate limits
  - Burst protection
  - API tier-based limits

# Pagination standardization
- [ ] app/Http/Resources/Concerns/PaginatesResources.php
  - Standardized pagination response
  - includes: data, meta, links

# Tests (50+ API tests)
- [ ] tests/Feature/Api/V1/AuthTest.php
- [ ] tests/Feature/Api/V1/CustomerApiTest.php
- [ ] tests/Feature/Api/V1/VehicleApiTest.php
- [ ] tests/Feature/Api/V1/WorkOrderApiTest.php
- [ ] tests/Feature/Api/V1/QuoteApiTest.php
- [ ] tests/Feature/Api/V1/InvoiceApiTest.php
- [ ] tests/Feature/Api/V1/PaymentApiTest.php
- [ ] tests/Feature/Api/V1/NotificationApiTest.php
- [ ] tests/Feature/Api/V1/OfflineSyncTest.php
- [ ] tests/Feature/Api/RateLimitTest.php
- [ ] tests/Feature/Api/TenantIsolationTest.php
- [ ] tests/Feature/Api/ApiErrorFormatTest.php
```

### الأسبوع 15-16: PWA + Real-time

#### Week 15: PWA
```bash
# PWA Configuration
- [ ] composer require laravel/pwa (or build manually)
- [ ] public/manifest.json
  - name, short_name, theme_color, background_color
  - icons (192, 512, maskable)
  - display: standalone
  - start_url: /app
- [ ] public/sw.js (Service Worker)
  - Cache strategy (network-first for API, cache-first for static)
  - Background sync
  - Push notification handling
- [ ] public/offline.html
- [ ] resources/js/pwa/registerSW.js

# Offline capability
- [ ] app/Http/Controllers/Api/V1/Sync/OfflineSyncController.php
- [ ] resources/js/stores/offlineSync.js
- [ ] resources/js/composables/useOfflineSync.js
- [ ] IndexedDB schema:
  - work_orders (offline draft)
  - customers (cached)
  - parts (cached)
  - vehicles (cached)

# Push notifications
- [ ] app/Services/PushNotificationService.php
- [ ] app/Models/DeviceToken.php (new)
- [ ] app/Http/Controllers/Api/V1/Devices/DeviceController.php
  - POST /api/v1/devices/register
  - DELETE /api/v1/devices/{id}
- [ ] Web Push integration (VAPID keys)
- [ ] Push subscription management UI
- [ ] resources/js/composables/usePushNotifications.js
```

#### Week 16: Real-time + Cache
```bash
# Broadcasting (Laravel Echo + Pusher/Soketi)
- [ ] composer require pusher/pusher-php-server
- [ ] composer require laravel/echo (frontend)
- [ ] config/broadcasting.php
- [ ] resources/js/bootstrap.js → import Echo

# Channels
- [ ] routes/channels.php
  - tenant.{tenantId} (private)
  - work-order.{workOrderId} (presence)
  - center.{centerId} (private)

# Events to broadcast
- [ ] WorkOrderStatusChanged → broadcast
- [ ] PaymentReceived → broadcast
- [ ] NewNotification → broadcast (private)
- [ ] StockLow → broadcast

# Frontend
- [ ] resources/js/composables/useRealtime.js
- [ ] Real-time notification badge
- [ ] Real-time work order status
- [ ] Real-time dashboard updates
- [ ] Live inventory levels

# Cache strategy (Redis)
- [ ] app/Providers/CacheServiceProvider.php
- [ ] Tagged cache for tenant-scoped data
- [ ] Cache invalidation via events:
  - WorkOrderUpdated → invalidate work-order.{id}
  - CustomerUpdated → invalidate customer.{id}, dashboard
  - PaymentRecorded → invalidate invoice.{id}, dashboard

# Redis-backed sessions
- [ ] SESSION_DRIVER=redis
- [ ] CACHE_DRIVER=redis
- [ ] QUEUE_CONNECTION=redis

# Test
- [ ] tests/Feature/Broadcasting/WorkOrderStatusBroadcastTest.php
- [ ] tests/Feature/CacheInvalidationTest.php
```

### Phase 3 Deliverables
- ✅ 30+ API controllers
- ✅ 15+ API resources
- ✅ OpenAPI/Swagger docs
- ✅ PWA configured
- ✅ Web Push notifications
- ✅ Real-time updates
- ✅ Redis caching
- ✅ 50+ API tests

**Phase 3 Outcome: 85% completion (from 80%)**

---

# 📊 PHASE 4 — Reports & Analytics (الشهر 4)
**"الذكاء" — Reports, Dashboards, Insights**

### الأسبوع 17-18: Core Reports

#### Week 17: Financial Reports
```bash
# Reports infrastructure
- [ ] app/Services/Reports/ReportService.php (new)
  - Generic report runner
  - Caching strategy
  - Tenant-scoped
  - Export to PDF/Excel/CSV

# Reports
- [ ] app/Http/Controllers/App/Reports/Financial/DailyReportController.php
  - Revenue, expenses, profit
  - Per payment method
  - Per customer type
  - vs previous day comparison
- [ ] app/Http/Controllers/App/Reports/Financial/ProfitAndLossController.php
  - Income statement
  - Monthly/Quarterly/Yearly
  - By category
- [ ] app/Http/Controllers/App/Reports/Financial/CashFlowController.php
  - Cash in/out
  - Net cash flow
  - Cumulative
- [ ] app/Http/Controllers/App/Reports/Financial/AgingReceivablesController.php
  - 0-30, 31-60, 61-90, 90+ days
  - Per customer
  - Per invoice
- [ ] app/Http/Controllers/App/Reports/Financial/AgingPayablesController.php
  - 0-30, 31-60, 61-90, 90+ days
  - Per supplier

# Frontend
- [ ] resources/js/Pages/App/Reports/Financial/Index.vue
- [ ] resources/js/Pages/App/Reports/Financial/Daily.vue
- [ ] resources/js/Pages/App/Reports/Financial/PnL.vue
- [ ] resources/js/Pages/App/Reports/Financial/CashFlow.vue
- [ ] resources/js/Pages/App/Reports/Financial/AgingReceivables.vue
- [ ] resources/js/Pages/App/Reports/Financial/AgingPayables.vue

# Components
- [ ] resources/js/Components/Reports/ReportFilters.vue
- [ ] resources/js/Components/Reports/ChartCard.vue
- [ ] resources/js/Components/Reports/ExportButton.vue
- [ ] resources/js/Components/Reports/ComparisonCard.vue
```

#### Week 18: Operational Reports
```bash
# Operations reports
- [ ] app/Http/Controllers/App/Reports/Operations/WorkOrderReportController.php
  - By status, center, technician
  - Cycle time analysis
  - Completion rate
  - Revenue per WO
- [ ] app/Http/Controllers/App/Reports/Operations/InventoryReportController.php
  - Stock valuation
  - Slow-moving (90+ days)
  - Dead stock (180+ days)
  - Turnover ratio
  - Reorder alerts
- [ ] app/Http/Controllers/App/Reports/Operations/CustomerReportController.php
  - Top customers (revenue)
  - Customer LTV
  - Acquisition cost
  - Churn rate
  - Repeat rate
- [ ] app/Http/Controllers/App/Reports/Operations/EmployeeReportController.php
  - Work orders per tech
  - Revenue per tech
  - Attendance summary
  - Productivity
- [ ] app/Http/Controllers/App/Reports/Operations/SupplierReportController.php
  - Top suppliers
  - Lead time analysis
  - Quality score
  - Spend analysis

# Frontend (15+ pages)
- [ ] resources/js/Pages/App/Reports/Operations/WorkOrderReport.vue
- [ ] resources/js/Pages/App/Reports/Operations/InventoryReport.vue
- [ ] resources/js/Pages/App/Reports/Operations/CustomerReport.vue
- [ ] resources/js/Pages/App/Reports/Operations/EmployeeReport.vue
- [ ] resources/js/Pages/App/Reports/Operations/SupplierReport.vue
- [ ] resources/js/Components/Reports/InventoryTable.vue
- [ ] resources/js/Components/Reports/CustomerLtvChart.vue

# Scheduled reports (email)
- [ ] app/Console/Commands/SendDailyReport.php
- [ ] app/Console/Commands/SendWeeklyReport.php
- [ ] app/Console/Commands/SendMonthlyReport.php
- [ ] app/Models/ScheduledReport.php (new)
  - user_id, report_type, frequency, recipients
- [ ] app/Http/Controllers/App/Reports/ScheduledReportController.php
- [ ] resources/js/Pages/App/Reports/Scheduled/Index.vue
```

### الأسبوع 19-20: Advanced Analytics

#### Week 19: BI-style Dashboards
```bash
# Dashboards
- [ ] app/Http/Controllers/App/Reports/AnalyticsController.php
  - GET /app/analytics/overview
  - GET /app/analytics/cohorts
  - GET /app/analytics/funnel
  - GET /app/analytics/forecast

# Cohort analysis (customer retention)
- [ ] app/Services/Analytics/CohortService.php
  - Monthly cohorts
  - Retention curves
  - Revenue per cohort
- [ ] app/Http/Controllers/App/Analytics/CohortController.php
- [ ] resources/js/Pages/App/Analytics/Cohorts.vue

# Funnel (lead → customer → WO → paid)
- [ ] app/Services/Analytics/FunnelService.php
  - Lead to Customer conversion
  - Customer to WO
  - WO to Paid
  - Drop-off analysis
- [ ] resources/js/Pages/App/Analytics/Funnel.vue

# Forecasting (AI)
- [ ] app/Services/Analytics/ForecastService.php
  - Time-series forecasting
  - Prophet or simple linear regression
  - Demand forecasting for parts
- [ ] app/Http/Controllers/App/Analytics/ForecastController.php
- [ ] resources/js/Pages/App/Analytics/Forecast.vue

# Custom dashboards (per user)
- [ ] app/Models/DashboardLayout.php (new)
  - user_id, layout_json, is_default
- [ ] app/Http/Controllers/App/Dashboard/DashboardController.php (extend)
  - saveLayout, loadLayout, shareLayout
- [ ] resources/js/Components/Dashboard/WidgetPicker.vue
- [ ] resources/js/Components/Dashboard/WidgetGrid.vue

# Chart components
- [ ] resources/js/Components/Charts/LineChart.vue
- [ ] resources/js/Components/Charts/BarChart.vue
- [ ] resources/js/Components/Charts/PieChart.vue
- [ ] resources/js/Components/Charts/HeatmapChart.vue
- [ ] resources/js/Components/Charts/ForecastChart.vue
- [ ] resources/js/Components/Charts/CohortChart.vue
```

#### Week 20: Report Builder
```bash
# Custom report builder
- [ ] app/Services/Reports/ReportBuilderService.php
  - Define columns, filters, aggregations
- [ ] app/Models/CustomReport.php (new)
  - tenant_id, name, definition_json, is_public
- [ ] app/Http/Controllers/App/Reports/BuilderController.php
  - GET /app/reports/builder - schema
  - POST /app/reports - create
  - GET /app/reports/{id} - execute
- [ ] resources/js/Pages/App/Reports/Builder/Index.vue
- [ ] resources/js/Pages/App/Reports/Builder/Edit.vue
- [ ] resources/js/Pages/App/Reports/Builder/Execute.vue
- [ ] resources/js/Components/Reports/FieldPicker.vue
- [ ] resources/js/Components/Reports/FilterBuilder.vue
- [ ] resources/js/Components/Reports/AggregationPicker.vue
- [ ] resources/js/Components/Reports/ChartPreview.vue

# Export
- [ ] app/Exports/ReportExport.php (new, generic)
- [ ] PDF: barryvdh/laravel-dompdf (already installed)
- [ ] Excel: maatwebsite/excel (already installed)
- [ ] CSV: simple

# Tests
- [ ] tests/Feature/Reports/FinancialReportsTest.php
- [ ] tests/Feature/Reports/OperationalReportsTest.php
- [ ] tests/Feature/Reports/ScheduledReportsTest.php
- [ ] tests/Feature/Reports/CustomReportBuilderTest.php
- [ ] tests/Feature/Reports/ExportTest.php
- [ ] tests/Unit/Services/Reports/ReportServiceTest.php
- [ ] tests/Unit/Services/Analytics/CohortServiceTest.php
```

### Phase 4 Deliverables
- ✅ 20+ financial reports
- ✅ 15+ operational reports
- ✅ Cohort analysis
- ✅ Funnel analysis
- ✅ Forecasting (AI)
- ✅ Custom report builder
- ✅ Scheduled email reports
- ✅ Export to PDF/Excel/CSV
- ✅ 30+ report tests

**Phase 4 Outcome: 90% completion (from 85%)**

---

# 🚀 PHASE 5 — DevOps & Production Readiness (الشهر 5)
**"الجاهزية الكاملة" — Docker, CI/CD, Monitoring, Backup**

### الأسبوع 21-22: Docker + CI/CD

#### Week 21: Docker
```bash
# Dockerfile (multi-stage)
- [ ] Dockerfile
  - Stage 1: composer install
  - Stage 2: npm build
  - Stage 3: production image (php-fpm-alpine)
- [ ] .dockerignore
- [ ] docker/nginx/default.conf
- [ ] docker/php/php.ini (production-tuned)
- [ ] docker/php/supervisor.conf (queue, scheduler, octane)

# Docker Compose
- [ ] docker-compose.yml (base)
  - app (php-fpm)
  - nginx
  - mysql 8.0
  - redis 7
  - meilisearch
  - mailhog (dev)
  - minio (S3 local)
- [ ] docker-compose.dev.yml (dev overrides)
  - xdebug
  - mailhog
  - phpmyadmin
  - redis-commander
- [ ] docker-compose.prod.yml (prod overrides)
  - External services
  - Resource limits
  - Restart policies
  - Health checks

# Dev environment
- [ ] make up / make down / make logs / make shell
- [ ] Database seeding on first boot
- [ ] Storage permissions auto-fix
- [ ] Vite HMR working in Docker

# Octane (optional but recommended)
- [ ] composer require laravel/octane
- [ ] php artisan octane:install --server=frankenphp
- [ ] Configure for production
- [ ] Benchmark Octane vs traditional FPM
```

#### Week 22: CI/CD Pipeline
```bash
# GitHub Actions (existing workflows)
- [ ] .github/workflows/ci.yml (existing, refactor)
  - Backend tests (PHP 8.2, 8.3, 8.4)
  - Frontend tests
  - Lint (Pint, ESLint)
  - Type check
  - Build

# NEW workflows
- [ ] .github/workflows/security-scan.yml
  - Trivy image scan
  - Composer audit
  - npm audit
  - OWASP ZAP baseline
  - Trivy filesystem scan
  
- [ ] .github/workflows/deploy-staging.yml
  - On push to develop
  - Build Docker image
  - Push to ECR/DockerHub
  - Deploy to staging (k8s or ECS)
  - Run smoke tests
  - Notify Slack

- [ ] .github/workflows/deploy-production.yml
  - On tag (v*)
  - Build Docker image (signed)
  - Push to registry
  - Run database migrations (with safety checks)
  - Blue/green deploy
  - Health check
  - Rollback on failure
  - Notify Slack

- [ ] .github/workflows/lighthouse.yml
  - Performance budget check
  - Accessibility check
  - SEO check
  - Best practices check
  - Block PR if scores < thresholds

# Deployment manifests
- [ ] k8s/
  - namespace.yaml
  - configmap.yaml
  - secret.yaml (sealed-secrets or external-secrets)
  - deployment.yaml (with HPA)
  - service.yaml
  - ingress.yaml (TLS via cert-manager)
  - pdb.yaml (PodDisruptionBudget)
  - networkpolicy.yaml
  - mysql-statefulset.yaml (or use managed)
  - redis-statefulset.yaml (or use managed)
  - meilisearch-deployment.yaml
  - jobs/migration-job.yaml
  - cron/scheduler-cronjob.yaml
  - hpa.yaml (autoscaling)
  - servicemonitor.yaml (Prometheus)
  - prometheusrule.yaml (alerts)
  - ingressroute.yaml (if using Traefik)

# Helm chart (optional, easier deployment)
- [ ] charts/carag-v2/
  - Chart.yaml
  - values.yaml (dev, staging, prod)
  - templates/

# Deploy script
- [ ] scripts/deploy.sh
  - Build, tag, push
  - Run migrations
  - Run cache clear
  - Reload php-fpm or octane
  - Health check
- [ ] scripts/rollback.sh
```

### الأسبوع 23-24: Monitoring + Backup

#### Week 23: Observability
```bash
# Logging (existing)
- [ ] Enhance JsonFormatter with:
  - trace_id (from Sentry)
  - span_id
  - request_id
  - user_id
  - tenant_id
  - center_id
  - route, method, status
  - duration_ms
- [ ] Daily log rotation
- [ ] Log retention policy (30 days)

# Metrics (Prometheus)
- [ ] app/Services/Metrics/PrometheusService.php
  - HTTP request duration histogram
  - Database query duration
  - Queue size
  - Cache hit rate
  - Active users
  - Active tenants
  - Revenue per hour
  - Failed payments
- [ ] /metrics endpoint (protected)

# Tracing (OpenTelemetry)
- [ ] composer require open-telemetry/opentelemetry
- [ ] Auto-instrumentation:
  - HTTP requests
  - Database queries
  - Queue jobs
  - HTTP client (outgoing)
  - Cache operations
- [ ] Trace context propagation
- [ ] Export to Jaeger/Tempo

# APM (Application Performance Monitoring)
- [ ] Sentry Performance (already integrated)
- [ ] Configure sample rates:
  - Production: 10%
  - Staging: 100%
  - Dev: 100%
- [ ] Set up alerts:
  - Error rate > 1%
  - P95 latency > 1s
  - Failed transactions

# Dashboards (Grafana)
- [ ] Grafana dashboard JSON
  - HTTP request rate
  - HTTP error rate
  - P50/P95/P99 latency
  - Database connection pool
  - Queue depth
  - Cache hit rate
  - Active users
  - Active tenants
  - Revenue metrics
- [ ] Business KPIs:
  - WO completion rate
  - Average WO value
  - Payment success rate
  - Customer acquisition

# Alerting (Alertmanager)
- [ ] Critical alerts (PagerDuty):
  - Service down
  - Database connection lost
  - Queue backed up
  - Disk > 90%
  - Error rate > 5%
- [ ] Warning alerts (Slack):
  - High latency
  - Slow queries
  - Memory > 80%
  - Disk > 70%
  - Failed jobs

# Uptime monitoring
- [ ] UptimeRobot / BetterStack / Oh Dear
  - /healthz
  - /readyz
  - Main marketing site
  - API endpoints
  - 5-minute interval
  - 2 regions (US + EU)
  - 99.95% SLA tracking
```

#### Week 24: Backup + DR
```bash
# Database backups
- [ ] app/Console/Commands/Backup/CreateDatabaseBackup.php
  - mysqldump to S3
  - Daily at 02:00 UTC
  - 30-day retention
  - Compression
  - Encryption
  - Integrity check (md5)
- [ ] app/Console/Commands/Backup/UploadToS3.php
- [ ] app/Console/Commands/Backup/VerifyBackup.php
  - Restore to staging nightly
  - Verify data integrity
- [ ] app/Console/Commands/Backup/CleanupOldBackups.php
- [ ] app/Console/Commands/Backup/RestoreFromBackup.php (manual)
- [ ] app/Models/BackupRecord.php
  - type, size, location, status, verified_at
- [ ] app/Http/Controllers/System/BackupController.php
  - List, download, restore, verify
- [ ] resources/js/Pages/System/Backups/Index.vue

# Storage backups (S3)
- [ ] app/Console/Commands/Backup/SyncStorageToS3.php
  - Daily at 03:00 UTC
  - All tenant files
  - Incremental (delta only)

# DR Plan
- [ ] docs/operations/backup-dr.md
- [ ] RTO target: < 1 hour
- [ ] RPO target: < 15 minutes
- [ ] Quarterly DR drill
- [ ] Runbook: Restore from backup (step by step)
- [ ] Runbook: Database failover
- [ ] Runbook: Cache rebuild
- [ ] Runbook: DNS failover

# Multi-region
- [ ] Phase 1: Single region (Riyadh)
- [ ] Phase 2: Active-passive (Riyadh + Dubai)
  - Database replication
  - Storage replication
  - DNS failover (Route53)
  - Health check in primary
- [ ] Phase 3: Active-active (multi-region)
  - Database sharding
  - Conflict resolution
  - Read replicas per region

# Disaster scenarios & runbooks
- [ ] Database corruption → restore from latest backup
- [ ] Accidental delete → point-in-time recovery
- [ ] Region failure → failover to secondary
- [ ] Security breach → isolate, rotate keys, restore clean
- [ ] DDoS → enable Cloudflare, rate limit
- [ ] Bad deploy → rollback via blue/green
```

### Phase 5 Deliverables
- ✅ Multi-stage Dockerfile
- ✅ Docker Compose (dev + prod)
- ✅ 5 CI/CD workflows
- ✅ K8s manifests (or ECS)
- ✅ Prometheus + Grafana
- ✅ OpenTelemetry tracing
- ✅ Sentry APM
- ✅ Daily DB backups to S3
- ✅ 30-day retention
- ✅ DR runbook
- ✅ Multi-region ready
- ✅ Uptime monitoring

**Phase 5 Outcome: 95% completion (from 90%)**

---

# 🎨 PHASE 6 — UX Polish & Performance (الشهر 6)
**"اللمسة الأخيرة" — UX, Performance, Accessibility, I18n**

### الأسبوع 25-26: UX & Accessibility

#### Week 25: Accessibility (WCAG 2.1 AA)
```bash
# Audit
- [ ] Run axe-core on all pages
- [ ] Document violations
- [ ] Prioritize fixes

# Fixes
- [ ] All interactive elements have proper ARIA roles
- [ ] Form fields have <label for>
- [ ] Error messages associated with fields (aria-describedby)
- [ ] Focus visible (focus-visible:ring)
- [ ] Tab order correct
- [ ] Skip to content link
- [ ] Heading hierarchy correct (h1 → h2 → h3)
- [ ] Color contrast 4.5:1 (text), 3:1 (UI)
- [ ] All images have alt text
- [ ] All buttons have accessible name
- [ ] All form fields have accessible name
- [ ] Live regions for async updates
- [ ] Keyboard navigation works
- [ ] Screen reader testing (NVDA, JAWS, VoiceOver)
- [ ] High contrast mode support
- [ ] Reduced motion support (prefers-reduced-motion)

# Tools
- [ ] Add @axe-core/playwright
- [ ] Add eslint-plugin-vuejs-accessibility
- [ ] CI check: lighthouse accessibility >= 95
```

#### Week 26: UX Refinements
```bash
# Common components (extract)
- [ ] resources/js/Components/Common/EmptyState.vue
- [ ] resources/js/Components/Common/ErrorBoundary.vue
- [ ] resources/js/Components/Common/LoadingButton.vue
- [ ] resources/js/Components/Common/SearchInput.vue (debounced)
- [ ] resources/js/Components/Common/FilterPanel.vue
- [ ] resources/js/Components/Common/SortHeader.vue
- [ ] resources/js/Components/Common/Pagination.vue (server-side)
- [ ] resources/js/Components/Common/StatusBadge.vue
- [ ] resources/js/Components/Common/AmountDisplay.vue
- [ ] resources/js/Components/Common/PhoneInput.vue
- [ ] resources/js/Components/Common/DateRangePicker.vue
- [ ] resources/js/Components/Common/FileUploader.vue (drag-drop)
- [ ] resources/js/Components/Common/ImageCropper.vue
- [ ] resources/js/Components/Common/Avatar.vue
- [ ] resources/js/Components/Common/Tag.vue
- [ ] resources/js/Components/Common/Tabs.vue
- [ ] resources/js/Components/Common/Modal.vue (headless)
- [ ] resources/js/Components/Common/Drawer.vue
- [ ] resources/js/Components/Common/Toast.vue
- [ ] resources/js/Components/Common/Tooltip.vue
- [ ] resources/js/Components/Common/Popover.vue
- [ ] resources/js/Components/Common/CommandPalette.vue (Cmd+K)
- [ ] resources/js/Components/Form/Field.vue
- [ ] resources/js/Components/Form/ErrorMessage.vue
- [ ] resources/js/Components/Form/Label.vue
- [ ] resources/js/Components/Form/HelpText.vue
- [ ] resources/js/Components/Table/DataTable.vue (sortable, filterable)
- [ ] resources/js/Components/Table/EmptyRow.vue
- [ ] resources/js/Components/Table/LoadingRow.vue

# UX improvements
- [ ] Skeleton loaders (not spinners)
- [ ] Optimistic UI updates
- [ ] Toast on all actions (success/error)
- [ ] Confirm dialogs for destructive actions
- [ ] Unsaved changes warning
- [ ] Auto-save drafts
- [ ] Bulk actions with selection
- [ ] Drag-and-drop reordering
- [ ] Inline editing
- [ ] Smart search with suggestions
- [ ] Recent items (per user)
- [ ] Pinned items
- [ ] Dark mode toggle (visible)
- [ ] Color blind mode
- [ ] Font size adjustment
- [ ] Keyboard shortcuts
- [ ] Onboarding tour (first-time user)
- [ ] Help center integration
- [ ] Feedback button
- [ ] What’s new changelog
```

### الأسبوع 27-28: Performance & i18n

#### Week 27: Performance
```bash
# Backend optimization
- [ ] Database query analysis (EXPLAIN slow queries)
- [ ] Add missing indexes (from Stage 5 #39)
- [ ] N+1 fix (Customer::show, WorkOrderController::getStatsForStatuses)
- [ ] Eager loading audit
- [ ] Pagination on all lists
- [ ] Caching strategy:
  - Dashboard: 1 minute
  - Reports: 5 minutes
  - Catalog (services, parts): 1 hour
  - Settings: 1 hour
  - User permissions: 5 minutes
- [ ] Database read replicas (for reports)
- [ ] Materialized views for complex aggregations
- [ ] Partitioning for huge tables (audit, logs)
- [ ] Archive old data (soft-deleted > 1 year)
- [ ] Octane / FrankenPHP for 3-5x throughput

# Frontend optimization
- [ ] Code splitting per route (Inertia does this)
- [ ] Lazy load components
- [ ] Lazy load images (loading="lazy")
- [ ] Image optimization (WebP, AVIF)
- [ ] CDN for static assets
- [ ] Service Worker for offline cache
- [ ] Bundle size analysis (vite-bundle-visualizer)
- [ ] Tree-shake unused code
- [ ] Compress images on upload (Intervention/Image)
- [ ] Debounce search inputs
- [ ] Virtual scrolling for long lists
- [ ] Skeleton screens

# CDN
- [ ] CloudFront / Cloudflare for:
  - Static assets (CSS, JS, images)
  - User-uploaded files (S3 + CloudFront)
  - Print PDFs (signed URLs)
  - Invoice PDFs
- [ ] Cache-Control headers
- [ ] Gzip / Brotli compression

# Performance budgets (Lighthouse)
- [ ] Performance >= 90
- [ ] Accessibility >= 95
- [ ] Best practices >= 95
- [ ] SEO >= 90
- [ ] LCP < 2.5s
- [ ] FID < 100ms
- [ ] CLS < 0.1
- [ ] Total bundle < 250KB (gzipped)
- [ ] Initial JS < 100KB

# Performance tests
- [ ] tests/Feature/Performance/N1QueryTest.php
  - Assert no N+1 in critical paths
- [ ] tests/Feature/Performance/IndexUsageTest.php
  - Assert all queries use indexes
- [ ] Lighthouse CI in pipeline
- [ ] Load test (k6) for critical endpoints
- [ ] Stress test (1000 concurrent users)
```

#### Week 28: i18n + Multi-currency
```bash
# Multi-language
- [ ] Add languages:
  - [ ] ar (Arabic) - existing
  - [ ] en (English) - existing
  - [ ] fr (French)
  - [ ] ur (Urdu)
  - [ ] hi (Hindi)
  - [ ] tr (Turkish)
  - [ ] fa (Persian/Farsi)
- [ ] Translation coverage:
  - [ ] All UI strings
  - [ ] All email templates
  - [ ] All notification messages
  - [ ] All error messages
  - [ ] All PDF templates
- [ ] RTL support verification for all languages
- [ ] Pluralization rules per language
- [ ] Date/number formatting per locale
- [ ] Currency formatting per locale
- [ ] Direction-aware CSS (logical properties)
- [ ] Test all pages in all languages

# Multi-currency
- [ ] app/Models/Currency.php (new)
  - code, name, symbol, exchange_rate, is_active
- [ ] app/Models/ExchangeRate.php (new)
  - from_currency, to_currency, rate, fetched_at
- [ ] app/Services/Currency/CurrencyService.php
  - convert, format, getRate
- [ ] app/Services/Currency/ExchangeRateProvider.php
  - Fetch from API (exchangerate-api.com or similar)
- [ ] app/Console/Commands/Currency/UpdateExchangeRates.php
  - Daily at 06:00 UTC
- [ ] app/Console/Commands/Currency/SeedDefaultCurrencies.php
- [ ] Update all monetary fields to use currency_code
- [ ] Money display: respect user preference
- [ ] Reports in base currency (tenant config)
- [ ] Multi-currency invoices (display in customer's currency)
- [ ] Exchange rate snapshot on invoice creation
- [ ] app/Http/Controllers/App/Settings/CurrencyController.php
- [ ] resources/js/Pages/App/Settings/Currencies/Index.vue
- [ ] resources/js/Components/Common/CurrencySelector.vue
- [ ] resources/js/Composables/useCurrency.js

# Tests
- [ ] tests/Feature/MultiLanguageTest.php
- [ ] tests/Feature/MultiCurrencyTest.php
- [ ] tests/Feature/ExchangeRateTest.php
- [ ] tests/Feature/TranslationCoverageTest.php
```

### Phase 6 Deliverables
- ✅ WCAG 2.1 AA compliance
- ✅ 30+ shared components
- ✅ Optimistic UI
- ✅ Skeleton loaders
- ✅ CDN integration
- ✅ Lighthouse score 90+
- ✅ 6+ languages
- ✅ 8+ currencies
- ✅ Real-time exchange rates
- ✅ 50+ performance tests

**Phase 6 Outcome: 95%+ completion**

---

# 📊 الـ Score النهائي المتوقع

| المعيار | قبل | بعد |
|---|---|---|
| Project Completion | 62.5% | **96%** |
| Module Coverage | 62% | **100%** |
| Pages | 68% | **95%** |
| CRUD | 45% | **92%** |
| APIs | 25% | **95%** |
| Workflows | 50% | **95%** |
| Policies | 85% | **100%** |
| FormRequests | 15% | **95%** |
| Jobs/Events/Listeners | 0% | **90%** |
| Webhooks | 0% | **100%** |
| Tests | 15% | **85%+** |
| Documentation | 10% | **90%** |
| DevOps | 5% | **95%** |
| Mobile API | 0% | **95%** |
| Backup | 0% | **100%** |
| Monitoring | 20% | **95%** |
| Multi-region | 0% | **85%** |
| i18n | 30% | **95%** |
| Multi-currency | 0% | **90%** |
| Performance | 50% | **95%+** |
| Accessibility | 50% | **95%+** |

---

# 👥 الـ Team المطلوب

| الدور | العدد | المهام |
|---|---|---|
| **Tech Lead / Architect** | 1 | Architecture decisions, code review, security |
| **Backend Senior (Laravel)** | 2 | Jobs, Events, Payment, ZATCA, API, Tests |
| **Frontend Senior (Vue 3)** | 1 | Components, PWA, Real-time, Accessibility, Tests |
| **DevOps Senior** | 1 | Docker, K8s, CI/CD, Monitoring, Backup |
| **QA Engineer** | 1 | Test strategy, E2E tests, Manual testing |
| **Designer (UI/UX)** | 0.5 | UX refinements, accessibility review |

**Total: 6.5 FTE**

---

# 💰 تقدير التكلفة

| الفئة | التكلفة (SAR) |
|---|---|
| **Team (6 أشهر × 6.5 FTE × 25K/شهر)** | 975,000 |
| **Infrastructure (dev/staging/prod)** | 30,000 |
| **Third-party services** | 15,000 |
| **Security audit (external)** | 50,000 |
| **Penetration testing** | 25,000 |
| **Contingency (15%)** | 165,000 |
| **Total** | **1,260,000 SAR** |

**Equivalent: ~$336K USD**

---

# 🎯 Quick Wins vs Long-term

| Quick Wins (1-2 أسبوع) | Long-term (6 أشهر) |
|---|---|
| XSS fixes | Mobile app |
| 2FA mass assignment | Multi-region |
| Payment type migration | ZATCA full integration |
| Rate limiting | Payment gateways |
| Tenant scope (55 models) | Real-time updates |
| 4 critical policies | Custom report builder |
| | PWA |
| | Multi-currency |
| | i18n (6+ languages) |
| | DR + backup |
| | CI/CD + Docker |

**Quick wins → 65% (1-2 أسبوع)**
**Long-term → 95%+ (6 أشهر)**

---

# ✅ معايير القبول النهائية (Production Ready Checklist)

- [ ] All 7 critical blockers fixed
- [ ] All 4 missing critical policies added
- [ ] All 55 models with tenant scope
- [ ] All 4 payment gateways integrated
- [ ] ZATCA full integration
- [ ] 30+ API endpoints
- [ ] 200+ backend tests (all passing)
- [ ] 50+ frontend tests (all passing)
- [ ] 50+ E2E tests (Playwright)
- [ ] Lighthouse scores ≥ 90
- [ ] WCAG 2.1 AA compliance
- [ ] P95 latency < 200ms
- [ ] 99.95% uptime SLA tracking
- [ ] Daily backups verified
- [ ] DR runbook tested
- [ ] Multi-region failover tested
- [ ] Security audit passed
- [ ] Penetration test passed
- [ ] 6+ languages supported
- [ ] 8+ currencies supported
- [ ] Documentation complete
- [ ] CI/CD pipeline deployed
- [ ] Monitoring + alerting configured
- [ ] Team trained on operations

---

**Final Score Target: 96% / 100**

---

# 📞 القرار يا أحمد

تحب:

1. **أبدأ Phase 0 (Critical Blockers)** — أسبوع 1-2، نصلح الـ 7 ثغرات الحرجة → 65%
2. **أبدأ Phase 1 (Foundation)** — شهر 1، نبني الأساس المتين → 75%
3. **أعمل خطة مُبسّطة** (Phase 0 + Phase 2 فقط) — 3 أشهر، Critical + Payment + ZATCA → 85%
4. **أعمل Plan مخصص** حسب أولوياتك

قل لي وين تحب نبدأ يا أحمد، وأنا جاهز ننفذ! 🚀

---

**تم إعداد الخطة بواسطة:** Mavis
**التاريخ:** 2026-07-20
**الـ Branch:** `integration/p0-print-settings`
