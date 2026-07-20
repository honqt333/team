# 🗂️ Carag V2 — Audit Registry (سجل المراجعة الشامل)

**التاريخ:** 2026-07-20
**المُعد:** Mavis (مدير مراجعة المشروع)
**الـ Branch:** `integration/p0-print-settings`
**الهدف:** تتبع 100% من ملفات المشروع وحالة مراجعتها

---

# 📊 ملخص الأرقام

| الفئة | العدد | المراجَع | النسبة |
|---|---|---|---|
| Models | 110 | 7 | 6% |
| Controllers | 121 | 11 | 9% |
| Services | 43 | 7 | 16% |
| Actions | 3 | 3 | 12% |
| Policies | 27 | 1 | 4% |
| Middleware | 11 | 0 | 0% |
| Requests | 15 | 3 | 20% |
| Migrations | 207 | 0 | 0% |
| Seeders | 18 | 0 | 0% |
| Factories | 10 | 0 | 0% |
| Vue Pages | 156 | 0 | 0% |
| Vue Components | 131 | 0 | 0% |
| Composables | 11 | 0 | 0% |
| Tests | 57 | 5 | 9% |
| Routes | 489 | 0 | 0% |
| Console Commands | 6 | 0 | 0% |
| **Jobs** | **0** | **0** | **N/A** |
| **Events** | **0** | **0** | **N/A** |
| **Listeners** | **0** | **0** | **N/A** |
| Observers | 3 | 0 | 0% |
| Mail | 5 | 0 | 0% |
| Notifications | 3 | 0 | 0% |
| Exceptions | 1 | 0 | 0% |
| Providers | 1 | 0 | 0% |
| Traits | 11 | 0 | 0% |
| Support | 5 | 0 | 0% |
| Imports/Exports | 5 | 0 | 0% |

---

# 🔍 رموز حالة الملف

| الرمز | الحالة |
|---|---|
| 🟢 | تمت مراجعته بالكامل |
| 🟡 | تمت مراجعته لكن يحتاج مراجعة لاحقة |
| 🟠 | تمت مراجعته جزئياً |
| 🔴 | لم تتم مراجعته |
| ⚫ | لم يتم استلامه بعد (المجلد غير موجود) |

---

# 📋 Audit Registry — Models (110 ملف)

## 🟢 تمت مراجعته بالكامل (7 ملفات)

| # | الملف | المشاكل | يحتاج إعادة؟ | يعتمد على |
|---|---|---|---|---|
| 1 | `app/Models/WorkOrder.php` | 6 (booted, recalculate, scope, raw SQL) | نعم (depth) | Concerns, Traits |
| 2 | `app/Models/Customer.php` | 1 (center_id race) | لا | TenantScoped |
| 3 | `app/Models/Service.php` | 2 (formatted duration bug, items BTM) | نعم | TenantScoped |
| 4 | `app/Models/Payment.php` | 4 (booted, observer, type mixed) | نعم | TenantScoped |
| 5 | `app/Models/Invoice.php` | 2 (scope bypass, type mixed) | نعم | CenterScoped, HasTaxSnapshot |
| 6 | `app/Models/Quote.php` | 1 (totals recalc duplication) | لا | CenterScoped |
| 7 | `app/Models/Supplier.php` | 1 (code race) | لا | CenterScoped |

## 🟠 تمت مراجعته جزئياً (3 ملفات)

| # | الملف | المشاكل | يحتاج إعادة؟ |
|---|---|---|---|
| 8 | `app/Models/User.php` | 2 (2FA fillable, phone fallback) | نعم |
| 9 | `app/Models/WorkOrderItem.php` | 1 (cascade save) | نعم |
| 10 | `app/Models/WorkOrderItemPart.php` | (مذكور في Stage 4) | نعم |

## 🔴 لم تتم مراجعته (100 ملف)

### `app/Models/Billing/` (7 ملفات)
- `Installment.php` 🔴
- `Plan.php` 🔴
- `PromoCode.php` 🔴
- `PromoCodeUsage.php` 🔴
- `Subscription.php` 🔴
- `SubscriptionInvoice.php` 🔴
- `SubscriptionPayment.php` 🔴

### `app/Models/Credits/` (8 ملفات)
- `SmsPackage.php` 🔴
- `SmsPurchase.php` 🔴
- `SmsUsageLog.php` 🔴
- `TenantSmsBalance.php` 🔴
- `TenantWhatsappBalance.php` 🔴
- `WhatsappPackage.php` 🔴
- `WhatsappPurchase.php` 🔴
- `WhatsappUsageLog.php` 🔴

### `app/Models/Developer/` (5 ملفات)
- `AiMemory.php` 🔴
- `AuditSnapshot.php` 🔴
- `AuditViolation.php` 🔴
- `ComponentStat.php` 🔴
- `SlowQueryLog.php` 🔴

### `app/Models/HR/` (18 ملف)
- `Allowance.php` 🔴
- `Attendance.php` 🔴
- `AttendanceSettings.php` 🔴
- `BiometricDevice.php` 🔴
- `Deduction.php` 🔴
- `Employee.php` 🔴
- `EmployeeContract.php` 🔴
- `EmployeeDocument.php` 🔴
- `EmployeeShift.php` 🔴
- `EmployeeType.php` 🔴
- `HRRegulation.php` 🔴
- `JobTitle.php` 🔴
- `Leave.php` 🔴
- `OtherPayment.php` 🔴
- `Payroll.php` 🔴
- `PayrollItem.php` 🔴
- `PayrollRun.php` 🔴
- `Shift.php` 🔴

### `app/Models/Integration/` (3 ملفات)
- `Integration.php` 🔴
- `IntegrationLog.php` 🔴
- `TenantIntegration.php` 🔴

### باقي الـ Models (39 ملف)
- `AdminActivityLog.php` 🔴
- `AdminUser.php` 🔴
- `Center.php` 🔴
- `CenterAddress.php` 🔴
- `CenterSequence.php` 🔴
- `CenterWorkingHour.php` 🔴
- `CommunicationTemplate.php` 🔴
- `CompanyTransaction.php` 🔴
- `ContactMessage.php` 🔴
- `Department.php` 🔴
- `GoodsReceivedNote.php` 🔴
- `GrnItem.php` 🔴
- `IncomeCategory.php` 🔴
- `InspectionItem.php` 🔴
- `InspectionTemplate.php` 🔴
- `InternalNotification.php` 🔴
- `InventoryBalance.php` 🔴
- `InventoryCategory.php` 🔴
- `InventoryMove.php` 🔴
- `InventoryTransfer.php` 🔴
- `InventoryTransferItem.php` 🔴
- `InventoryUnit.php` 🔴
- `InvoiceLine.php` 🔴
- `InvoiceTemplate.php` 🔴
- `Nationality.php` 🔴
- `NotificationSendLog.php` 🔴
- `Part.php` 🔴
- `PaymentSettings.php` 🔴
- `Prompt.php` 🔴
- `PurchaseInvoice.php` 🔴
- `PurchaseInvoiceLine.php` 🔴
- `PurchaseOrder.php` 🔴
- `PurchaseOrderItem.php` 🔴
- `PurchaseReturnInvoice.php` 🔴
- `PurchaseReturnInvoiceLine.php` 🔴
- `QuoteLine.php` 🔴
- `QuotePart.php` 🔴
- `Role.php` 🔴
- `Setting.php` 🔴
- `SystemAnnouncement.php` 🔴
- `Tenant.php` 🔴
- `TenantAddress.php` 🔴
- `TenantAnnouncementRead.php` 🔴
- `TenantTaxSetting.php` 🔴
- `TenantZatcaSetting.php` 🔴
- `Vehicle.php` 🔴
- `VehicleColor.php` 🔴
- `VehicleConditionCategory.php` 🔴
- `VehicleConditionItem.php` 🔴
- `VehicleMake.php` 🔴
- `VehicleMileageLog.php` 🔴
- `VehicleModel.php` 🔴
- `Warehouse.php` 🔴
- `WorkOrderActivity.php` 🔴
- `WorkOrderAttachment.php` 🔴
- `WorkOrderDamageMark.php` 🔴
- `WorkOrderInspection.php` 🔴
- `WorkOrderItemNote.php` 🔴
- `WorkOrderPhoto.php` 🔴

---

# 📋 Audit Registry — Controllers (121 ملف)

## 🟢 تمت مراجعته بالكامل (11 ملف)

| # | الملف | المشاكل |
|---|---|---|
| 1 | `app/Http/Controllers/App/WorkOrders/WorkOrderController.php` | 12 (withoutGlobalScope, N+1, raw SQL) |
| 2 | `app/Http/Controllers/App/WorkOrders/WorkOrderStatusController.php` | 2 (transaction, exit) |
| 3 | `app/Http/Controllers/App/WorkOrders/WorkOrderPaymentController.php` | 2 (no transaction, dupe) |
| 4 | `app/Http/Controllers/App/CustomerController.php` | 1 (phone logic) |
| 5 | `app/Http/Controllers/App/ServiceController.php` | (partial) |
| 6 | `app/Http/Controllers/App/Quotes/QuoteController.php` | (partial) |
| 7 | `app/Http/Controllers/App/DashboardController.php` | (raw SQL) |
| 8 | `app/Http/Controllers/App/CompanyProfileController.php` | (no authorize) |
| 9 | `app/Http/Controllers/Api/WorkOrderSuggestionController.php` | ✅ (good) |
| 10 | `app/Http/Controllers/Api/AiDemoController.php` | (partial) |
| 11 | `app/Http/Controllers/Api/HR/BiometricAttendanceController.php` | (partial) |

## 🔴 لم تتم مراجعته (110 ملف)

### `app/Http/Controllers/App/` (51 ملف)
- `BranchesController.php` 🔴
- `CenterSettingsController.php` 🔴
- `CompanyTransactionController.php` 🔴
- `CustomerImportExportController.php` 🔴
- `CustomerMergeController.php` 🔴
- `DepartmentController.php` 🔴
- `EmployeePortalController.php` 🔴
- `GoodsReceivedNotesController.php` 🔴
- `IncomeCategoryController.php` 🔴
- `InventoryBalanceController.php` 🔴
- `InventoryMoveController.php` 🔴
- `InventorySettingsController.php` 🔴
- `InventoryTransfersController.php` 🔴
- `InvoicesController.php` 🔴
- `NotificationController.php` 🔴
- `PartsController.php` 🔴
- `PaymentsController.php` 🔴
- `PrintSettingsSignatureController.php` 🔴
- `ProfileController.php` 🔴
- `PurchaseInvoicesController.php` 🔴
- `PurchaseOrdersController.php` 🔴
- `PurchaseReturnsController.php` 🔴
- `PurchasingHubController.php` 🔴
- `PurchasingInvoicesController.php` 🔴
- `QuoteApprovalController.php` 🔴
- `ReportsController.php` 🔴
- `RoleController.php` 🔴
- `SettingsController.php` 🔴
- `SuppliersController.php` 🔴
- `SystemSettingsController.php` 🔴
- `TwoFactorAuthenticatedSessionController.php` 🔴
- `TwoFactorController.php` 🔴
- `UserController.php` 🔴
- `VehicleColorController.php` 🔴
- `VehicleConditionCategoryController.php` 🔴
- `VehicleConditionItemController.php` 🔴
- `VehicleController.php` 🔴
- `VehicleMakeController.php` 🔴
- `VehicleMileageController.php` 🔴
- `VehicleModelController.php` 🔴

### `app/Http/Controllers/App/HR/` (12 ملف)
- `AttendanceController.php` 🔴
- `BiometricDeviceController.php` 🔴
- `EmployeeController.php` 🔴
- `EmployeeContractsController.php` 🔴
- `EmployeeDocumentsController.php` 🔴
- `EmployeeFinancialsController.php` 🔴
- `EmployeePermissionsController.php` 🔴
- `EmployeeShiftController.php` 🔴
- `HRController.php` 🔴
- `HRPayrollSettingsController.php` 🔴
- `HRRegulationsController.php` 🔴
- `LeaveController.php` 🔴
- `OtherPaymentsController.php` 🔴
- `PayrollController.php` 🔴
- `SettingsController.php` 🔴
- `ShiftController.php` 🔴

### `app/Http/Controllers/App/WorkOrders/` (8 ملفات فرعية)
- `WorkOrderInspectionController.php` 🔴
- `WorkOrderItemController.php` 🔴
- `WorkOrderMediaController.php` 🔴
- `WorkOrderNotesController.php` 🔴
- `WorkOrderPartsController.php` 🔴
- `WorkOrderPrintController.php` 🔴
- `WorkOrderSignatureController.php` 🔴
- `WorkOrderTechnicianController.php` 🔴
- `WorkOrderWarrantiesController.php` 🔴

### `app/Http/Controllers/Auth/` (10 ملفات)
- `AuthenticatedSessionController.php` 🔴
- `ConfirmablePasswordController.php` 🔴
- `EmailVerificationNotificationController.php` 🔴
- `EmailVerificationPromptController.php` 🔴
- `NewPasswordController.php` 🔴
- `PasswordController.php` 🔴
- `PasswordResetLinkController.php` 🔴
- `PhoneVerificationController.php` 🔴
- `RegisteredUserController.php` 🔴
- `SetPasswordController.php` 🔴
- `VerifyEmailController.php` 🔴

### `app/Http/Controllers/System/` (23 ملف)
- `AdminUsersController.php` 🔴
- `AnnouncementsController.php` 🔴
- `CommunicationTemplatesController.php` 🔴
- `ContactMessageController.php` 🔴
- `DeveloperController.php` 🔴
- `GeneralSettingsController.php` 🔴
- `ImpersonationController.php` 🔴
- `InstallmentsController.php` 🔴
- `IntegrationsController.php` 🔴
- `PaymentController.php` 🔴
- `PaymentSettingsController.php` 🔴
- `PlansController.php` 🔴
- `ProfileController.php` 🔴
- `PromoCodesController.php` 🔴
- `SmsCreditsController.php` 🔴
- `SubscriptionInvoicesController.php` 🔴
- `SubscriptionsController.php` 🔴
- `SystemDashboardController.php` 🔴
- `TenantSecurityController.php` 🔴
- `TenantsController.php` 🔴
- `TwoFactorController.php` 🔴
- `WebsiteSettingsController.php` 🔴
- `WhatsappCreditsController.php` 🔴

### `app/Http/Controllers/Public/` (2 ملفات)
- `PublicLandingController.php` 🔴
- `PublicQuoteController.php` 🔴

### `app/Http/Controllers/Api/` (1 ملف)
- `WorkOrderController.php` 🔴

### Base + Health (4 ملفات)
- `Controller.php` 🔴
- `HealthController.php` 🔴
- `LocaleController.php` 🔴
- `ProfileController.php` 🔴

---

# 📋 Audit Registry — Services (43 ملف)

## 🟢 تمت مراجعته بالكامل (7 ملفات)
- `app/Services/AI/WorkOrderSuggestionService.php` (1 مشكلة: حجم 765 سطر)
- `app/Services/AI/AiProvider.php` ✅
- `app/Services/AI/ProviderRegistry.php` ✅
- `app/Services/AI/CompletionRequest.php` ✅
- `app/Services/AI/CompletionResponse.php` ✅
- `app/Services/AI/Providers/MockProvider.php` ✅

## 🟠 تمت مراجعته جزئياً (8 ملفات)
- `app/Services/AI/Providers/OpenAiProvider.php` (partial)
- `app/Services/AI/Providers/AnthropicProvider.php` (partial)
- `app/Services/AI/MockSuggester.php` (partial)
- `app/Services/PaymentService.php` (1: duplicate logic)
- `app/Services/TwoFactorService.php` (✅)
- `app/Services/NotificationService.php` (1: static methods)
- `app/Services/Inventory/InventoryService.php` (1: race condition)
- `app/Services/Payment/Gateways/TamaraGateway.php` (1: hard-coded)
- `app/Services/Payment/Gateways/TabbyGateway.php` (partial)
- `app/Services/Payment/Gateways/TapGateway.php` (partial)
- `app/Services/Payment/Gateways/MoyasarGateway.php` (partial)
- `app/Services/Payment/Contracts/PaymentGatewayInterface.php` ✅
- `app/Services/Payment/PaymentManager.php` (partial)
- `app/Services/Inventory/WorkOrderPartsService.php` (partial)
- `app/Services/Sms/AuthenticaService.php` (1: silent failure)
- `app/Services/QuoteConversionService.php` (partial)
- `app/Services/Purchasing/PurchasingService.php` (partial)

## 🔴 لم تتم مراجعته (23 ملف)

### `app/Services/Billing/` (2 ملف)
- `InstallmentService.php` 🔴
- `SubscriptionRenewalService.php` 🔴

### `app/Services/Developer/` (10 ملفات)
- `AuditOrchestrator.php` 🔴
- `Contracts/ScannerInterface.php` 🔴
- `Scanners/ArchitectureScanner.php` 🔴
- `Scanners/BusinessLogicScanner.php` 🔴
- `Scanners/DatabaseScanner.php` 🔴
- `Scanners/PerformanceScanner.php` 🔴
- `Scanners/TestScanner.php` 🔴
- `Scanners/UiScanner.php` 🔴

### `app/Services/HR/` (1 ملف)
- `AttendanceCalculationService.php` 🔴

### `app/Services/Invoice/` (1 ملف)
- `SubscriptionInvoiceService.php` 🔴

### `app/Services/Messaging/` (3 ملفات)
- `EmailService.php` 🔴
- `SmsService.php` 🔴
- `WhatsappService.php` 🔴

### `app/Services/Email/` (1 ملف)
- `SmtpConfigService.php` 🔴

### Others (5 ملفات)
- `InvoiceService.php` 🔴
- `Optimization/TaxCalculator.php` 🔴
- `TenantSetupService.php` 🔴

---

# 📋 Audit Registry — ملفات أخرى

## 🟡 Concerns/Scopes (3 ملفات)
- `app/Models/Concerns/TenantScoped.php` 🟢
- `app/Models/Concerns/CenterScoped.php` 🟢
- `app/Models/Concerns/HasTaxSnapshot.php` 🟢

## 🟢 Policies (1 راجع)
- `app/Policies/WorkOrderPolicy.php` 🟢

## 🔴 Policies الباقية (26)
- `CustomerPolicy.php` 🔴
- `DepartmentPolicy.php` 🔴
- `EmployeeContractPolicy.php` 🔴
- `GoodsReceivedNotePolicy.php` 🔴
- `HR/AttendancePolicy.php` 🔴
- `HR/EmployeePolicy.php` 🔴
- `HR/PayrollRunPolicy.php` 🔴
- `InventoryBalancePolicy.php` 🔴
- `InventoryMovePolicy.php` 🔴
- `InventoryTransferPolicy.php` 🔴
- `InvoicePolicy.php` 🔴
- `PartPolicy.php` 🔴
- `PurchaseInvoicePolicy.php` 🔴
- `PurchaseOrderPolicy.php` 🔴
- `QuoteLinePolicy.php` 🔴
- `QuotePolicy.php` 🔴
- `ServicePolicy.php` 🔴
- `SupplierPolicy.php` 🔴
- `TenantPolicy.php` 🔴
- `UserPolicy.php` 🔴
- `VehicleColorPolicy.php` 🔴
- `VehicleMakePolicy.php` 🔴
- `VehicleModelPolicy.php` 🔴
- `VehiclePolicy.php` 🔴
- `WorkOrderInspectionPolicy.php` 🔴

## 🔴 Middleware (11 ملف، 0 مراجعة)
- `ConvertArabicNumerals.php` 🔴
- `EnsureCenterContext.php` 🔴
- `EnsureSystemAdmin.php` 🔴
- `EnsureTenantActive.php` 🔴
- `EnsureTwoFactorEnabled.php` 🔴
- `HandleInertiaRequests.php` 🔴
- `PreventBackHistory.php` 🔴
- `SentryContext.php` 🔴
- `SetLocale.php` 🔴
- `SetPermissionsTeam.php` 🔴
- `TrackAiUsage.php` 🔴

## 🟢 Requests (3 راجع)
- `app/Http/Requests/WorkOrderStoreRequest.php` 🟢
- `app/Http/Requests/WorkOrder/WorkOrderSuggestionRequest.php` 🟢
- `app/Http/Requests/App/Purchasing/SupplierRequest.php` 🟢

## 🔴 Requests الباقية (12)
- `App/Print/UploadSignatureRequest.php` 🔴
- `App/Users/StoreUserRequest.php` 🔴
- `App/Users/UpdateUserRequest.php` 🔴
- `Auth/LoginRequest.php` 🔴
- `CustomerStoreRequest.php` 🔴
- `CustomerUpdateRequest.php` 🔴
- `ProfileUpdateRequest.php` 🔴
- `QuoteRequest.php` 🔴
- `SwitchCenterRequest.php` 🔴
- `VehicleStoreRequest.php` 🔴
- `VehicleUpdateRequest.php` 🔴
- `WorkOrderUpdateRequest.php` 🔴

## 🟡 Observers (3 ملفات، مذكورة)
- `CenterObserver.php` 🔴
- `EmployeeObserver.php` 🔴
- `InvoiceObserver.php` 🔴

## 🟡 Mail (5 ملفات)
- `SubscriptionExpiredMail.php` 🔴
- `SubscriptionInvoiceMail.php` 🔴
- `SubscriptionRenewalReminderMail.php` 🔴
- `TemplateMail.php` 🔴
- `TwoFactorCodeMail.php` 🔴

## 🟡 Notifications (3 ملفات)
- `ResetPasswordNotification.php` 🔴
- `VerifyEmailNotification.php` 🔴
- `WelcomeEmployeeInvitation.php` 🔴

## 🟢 Exceptions (1 ملف)
- `WorkOrderAiInvalidResponseException.php` 🟡

## ⚫ Jobs (0 ملفات) — **مجلد غير موجود**
## ⚫ Events (0 ملفات) — **مجلد غير موجود**
## ⚫ Listeners (0 ملفات) — **مجلد غير موجود**

## 🔴 Console Commands (0 من 6)
- `ProcessSubscriptionRenewals.php` 🔴
- `TranslationsCheck.php` 🔴
- `BackfillTenantDefaults.php` 🔴
- `FixInvoiceAddressSnapshots.php` 🔴
- `UpdateOverdueInstallments.php` 🔴
- `BackfillCenterWarehouses.php` 🔴

## 🔴 Providers (1 ملف)
- `AppServiceProvider.php` 🔴

## 🟡 Traits (11 ملف)
- `HasEmployeeRelations.php` 🔴
- `HasInventoryMoveRelations.php` 🔴
- `HasInventoryTransferRelations.php` 🔴
- `HasInvoiceRelations.php` 🔴
- `HasPurchaseInvoiceRelations.php` 🔴
- `HasPurchaseOrderRelations.php` 🔴
- `HasQuoteRelations.php` 🔴
- `HasVehicleRelations.php` 🔴
- `HasWorkOrderItemPartRelations.php` 🔴
- `HasWorkOrderOperations.php` 🔴
- `HasWorkOrderRelations.php` 🔴

## 🟡 Support (5 ملفات)
- `ArabicNumeralConverter.php` 🔴
- `CenterTypeGuard.php` 🔴
- `Permissions.php` 🔴
- `PricingHelper.php` 🟢
- `TenancyContext.php` 🟢

## 🟡 Imports/Exports (5 ملفات)
- `Imports/CustomersImport.php` 🔴
- `Exports/CustomersExport.php` 🔴
- `Exports/CustomersTemplateExport.php` 🔴
- `Exports/VehiclesExport.php` 🔴
- `Exports/WorkOrdersExport.php` 🔴

## 🔴 Frontend
- 156 Pages, 131 Components, 11 Composables (0 مراجعة بعمق)

---

# 🚨 ملفات متوقعة لم يتم استلامها (Expected Missing)

## 1️⃣ Jobs مفقودة (متوقع 15+)

| المتوقع | السبب |
|---|---|
| `AutoCreateInvoiceForDoneWorkOrderJob` | `Payment::booted()` يستدعي `invoiceService->issueInvoice()` sync |
| `ProcessSubscriptionRenewalJob` | `SubscriptionRenewalService` يحتاج job |
| `SendWhatsappMessageJob` | `WhatsappService` بدون queue |
| `SendSmsJob` | `SmsService` بدون queue |
| `NotifyOwnerJob` | `NotificationService::notifyOwner` sync |
| `IssueZatcaInvoiceJob` | `InvoiceService::issueInvoice` قد يكون بطيء |
| `ProcessBiometricBatchJob` | `BiometricAttendanceController::batch` يعالج 50 record sync |
| `GenerateReportJob` | `ReportsController` بدون queue |
| `ExportDataJob` | `CustomersImportExportController` exports بدون queue |
| `BackupDatabaseJob` | لا يوجد backup أصلاً |
| `CleanupOldLogsJob` | SlowQueryLog بدون rotation |
| `RecalculateWorkOrderTotalsJob` | يستبدل cascade save |
| `SyncIntegrationJob` | Integration log syncing |
| `EmailQueueJob` | Mail بدون queueing واضح |
| `RunSecurityScannersJob` | Developer center scanners |

## 2️⃣ Events مفقودة (متوقع 25+)

| المتوقع | السبب |
|---|---|
| `WorkOrderCreated` | `CreateWorkOrderAction` يطلق notify |
| `WorkOrderStatusChanged` | `WorkOrderStatusController` يطلق logActivity |
| `WorkOrderCompleted` | `WorkOrderStatusController::complete` |
| `PaymentRecorded` | `PaymentService::recordPayment` |
| `InvoiceIssued` | `InvoiceService::issueInvoice` |
| `InvoiceCancelled` | لا يوجد cancellation event |
| `PaymentRefunded` | لا يوجد |
| `CustomerCreated` | `CustomerController::store` |
| `VehicleAdded` | `VehicleController::store` |
| `SupplierCreated` | `SuppliersController::store` |
| `EmployeeHired` | `EmployeeController::store` |
| `EmployeeTerminated` | `EmployeeController::destroy` |
| `PayrollProcessed` | `PayrollController` |
| `LeaveRequested` | `LeaveController` |
| `LeaveApproved` | `LeaveController::approve` |
| `LeaveRejected` | `LeaveController::reject` |
| `SubscriptionRenewed` | `SubscriptionRenewalService` |
| `SubscriptionExpired` | `SubscriptionExpiredMail` بدون event |
| `IntegrationFailed` | `IntegrationLog` |
| `LoginSuccessful` | للأمان / audit |
| `LoginFailed` | brute force detection |
| `PasswordReset` | للأمان |
| `TwoFactorEnabled` | للأمان |
| `BackupCompleted` | لا يوجد backup أصلاً |

## 3️⃣ Listeners مفقودة (متوقع 10+)

| المتوقع | السبب |
|---|---|
| `LogActivityOnWorkOrderStatusChange` | بدل logActivity manual |
| `SendNotificationOnWorkOrderCreated` | بدل NotificationService::notifyOwner manual |
| `UpdateInvoiceStatusOnPayment` | في `Payment::boot()` |
| `UpdateInventoryOnPartIssued` | في `InventoryService` |
| `CreateJournalEntryOnInvoice` | لا يوجد accounting |
| `SendWelcomeEmailOnTenantCreated` | |
| `TrackAiUsageOnCompletion` | في middleware فقط |
| `InvalidateWorkOrderCacheOnUpdate` | لا يوجد cache |
| `WebhookOnPaymentSuccess` | للـ gateways |
| `SyncToExternalSystemOnIntegration` | integrations |
| `NotifyManagerOnEmployeeAbsence` | HR |
| `GeneratePayrollOnPeriodClose` | HR |

## 4️⃣ Providers مفقودة (متوقع 8+)

| المتوقع | السبب |
|---|---|
| `AppServiceProvider.php` (موجود) | ✅ |
| `AuthServiceProvider.php` | ناقص (Policies registration) |
| `BroadcastServiceProvider.php` | ناقص |
| `EventServiceProvider.php` | ناقص |
| `RouteServiceProvider.php` | ناقص (rate limiters) |
| `HorizonServiceProvider.php` | ناقص (Redis queue) |
| `TelescopeServiceProvider.php` | ناقص (dev) |
| `InertiaServiceProvider.php` | (يمكن مدمج) |
| `SentryServiceProvider.php` | ناقص (custom config) |
| `MultiTenantServiceProvider.php` | مدمج جزئياً |

## 5️⃣ Controllers متوقعة مفقودة

| المتوقع | السبب |
|---|---|
| `Api/V1/Auth/LoginController.php` | Mobile API login |
| `Api/V1/Customers/CustomerController.php` | Mobile customers list |
| `Api/V1/Vehicles/VehicleController.php` | Mobile vehicles |
| `Api/V1/WorkOrders/WorkOrderListController.php` | Mobile list |
| `Api/V1/Notifications/NotificationController.php` | Mobile push |
| `Api/V1/Reports/ReportController.php` | Mobile reports |
| `Api/V1/Payments/PaymentController.php` | Mobile payments |
| `Webhook/StripeWebhookController.php` | لا يوجد Stripe |
| `Webhook/PaymentWebhookController.php` | Generic webhook |
| `Webhook/TamaraWebhookController.php` | Tamara callback |
| `Webhook/TabbyWebhookController.php` | Tabby callback |
| `Webhook/TapWebhookController.php` | Tap callback |
| `Webhook/MoyasarWebhookController.php` | Moyasar callback |
| `Api/V1/Sync/OfflineSyncController.php` | للموبايل offline |
| `Cron/RecalculateController.php` | scheduled jobs |

## 6️⃣ Requests مفقودة

| المتوقع | السبب |
|---|---|
| `App/WorkOrder/UpdateConditionRequest.php` | inline في `WorkOrderStatusController` |
| `App/WorkOrder/ChangeStatusRequest.php` | inline |
| `App/WorkOrder/AddItemRequest.php` | inline |
| `App/WorkOrder/UpdateItemRequest.php` | inline |
| `App/Customer/MergeRequest.php` | inline |
| `App/Payment/StorePaymentRequest.php` | inline في `WorkOrderPaymentController` |
| `App/Payment/UpdatePaymentRequest.php` | inline |
| `App/Payment/RefundRequest.php` | inline |
| `App/Invoice/StoreInvoiceRequest.php` | inline |
| `App/Invoice/IssueInvoiceRequest.php` | inline |
| `App/Quote/ApproveRequest.php` | inline |
| `App/Quote/RejectRequest.php` | inline |
| `App/Quote/ConvertRequest.php` | inline |
| `App/Supplier/UpdateRequest.php` | مفقود |
| `App/Part/UpdateRequest.php` | مفقود |
| `App/Service/UpdateRequest.php` | مفقود |
| `App/HR/Employee/StoreRequest.php` | مفقود |
| `App/HR/Leave/StoreRequest.php` | مفقود |
| `App/HR/Payroll/ProcessRequest.php` | مفقود |
| `Auth/TwoFactorVerifyRequest.php` | مفقود |
| `App/Print/StoreSettingsRequest.php` | مفقود |

## 7️⃣ Form Requests مكررة (DRY violation)

تحتاج base class للـ patterns المتكررة:
- `TenantAware` (يحق tenant_id automatically)
- `CenterAware`
- `PaginatedRequest`
- `SortableRequest`
- `FilterableRequest`

## 8️⃣ Vue Pages متوقعة مفقودة

| المتوقع | السبب |
|---|---|
| `WorkOrders/Create.vue` | (لديك Modal فقط — لكن route منفصل مفقود) |
| `WorkOrders/Edit.vue` | ❌ مفقود تماماً (لديك Modal) |
| `Customers/Create.vue` | (Modal) |
| `Customers/Edit.vue` | ❌ مفقود |
| `Quotes/Create.vue` | (Modal) |
| `Quotes/Edit.vue` | ❌ مفقود |
| `Vehicles/Create.vue` | (Modal) |
| `Vehicles/Edit.vue` | ❌ مفقود |
| `Invoices/Edit.vue` | (Modal؟) |
| `Invoices/Create.vue` | (auto from WO) |
| `Parts/Create.vue` | ✅ (موجود) |
| `Parts/Edit.vue` | ❌ مفقود |
| `Suppliers/Edit.vue` | ❌ مفقود |
| `HR/Employees/Create.vue` | ❌ مفقود (Modal) |
| `HR/Employees/Edit.vue` | ❌ مفقود |
| `Settings/Profile/Edit.vue` | (مع Index) |
| `Settings/Company/Edit.vue` | (مع Index) |
| `Settings/Print/Edit.vue` | ❌ مفقود (مع Index) |
| `Reports/Financial.vue` | (partial) |
| `Reports/Inventory.vue` | ❌ مفقود |
| `Reports/HR.vue` | ❌ مفقود |
| `Reports/Sales.vue` | ❌ مفقود |
| `Reports/Tax.vue` | ❌ مفقود (ZATCA) |
| `Public/Pricing.vue` | ❌ مفقود |
| `Public/About.vue` | ❌ مفقود |
| `Public/Terms.vue` | ❌ مفقود |
| `Public/Privacy.vue` | ❌ مفقود |
| `System/Tenants/Edit.vue` | ❌ مفقود |
| `System/Subscriptions/Edit.vue` | ❌ مفقود |

## 9️⃣ Vue Components متوقعة مفقودة

| المتوقع | السبب |
|---|---|
| `Common/EmptyState.vue` | لازم يكون موجود |
| `Common/ErrorBoundary.vue` | للـ error handling |
| `Common/Pagination.vue` | (في Laravel pagination) |
| `Common/SearchInput.vue` | (inline) |
| `Common/FilterDropdown.vue` | (inline) |
| `Common/SortHeader.vue` | (inline) |
| `Common/StatusBadge.vue` | مكرر في كل مكان |
| `Common/AmountDisplay.vue` | (في `useFormatters` لكن component مفقود) |
| `Common/PhoneInput.vue` | (vue-tel-input مدمج) |
| `Common/DateRangePicker.vue` | (موجود جزئياً) |
| `Common/FileUpload.vue` | (inline) |
| `Common/ImageUploader.vue` | (inline) |
| `Common/Avatar.vue` | (inline) |
| `Common/Tag.vue` | (inline) |
| `Common/Tabs.vue` | (موجود `WorkOrderTabsContainer`) |
| `Form/Field.vue` | (AppInput) |
| `Form/ErrorMessage.vue` | (inline) |
| `Form/Label.vue` | (inline) |
| `Table/DataTable.vue` | (inline) |
| `Table/SortableHeader.vue` | (inline) |
| `Modal/Confirm.vue` | (ConfirmModal) |

## 🔟 API Resources مفقودة

كلها مفقودة:
- WorkOrderResource
- CustomerResource
- VehicleResource
- InvoiceResource
- PaymentResource
- PartResource
- ServiceResource
- SupplierResource
- EmployeeResource
- UserResource
- (15+ resources)

## 1️⃣1️⃣ Stores مفقودة

لا يوجد Pinia/Vuex store:
- `stores/auth.js` (auth state)
- `stores/notification.js` (notifications)
- `stores/cart.js` (لـ shopping cart — غير مطبق)
- `stores/workOrder.js` (work order draft)
- `stores/theme.js` (في composable — OK)

## 1️⃣2️⃣ Seeders مفقودة

| المتوقع | السبب |
|---|---|
| `PlanSeeder.php` | Plans في Billing |
| `SubscriptionSeeder.php` | |
| `PaymentMethodSeeder.php` | |
| `IntegrationSeeder.php` | (default integrations) |
| `BiometricDeviceSeeder.php` | |
| `PayrollItemSeeder.php` | |

## 1️⃣3️⃣ Factories مفقودة (66% ناقص)

| المتوقع | الحالة |
|---|---|
| `InvoiceFactory.php` | ❌ |
| `PaymentFactory.php` | ❌ |
| `QuoteFactory.php` | ❌ |
| `SupplierFactory.php` | ❌ |
| `PurchaseOrderFactory.php` | ❌ |
| `EmployeeFactory.php` | ❌ |
| `PayrollFactory.php` | ❌ |
| `LeaveFactory.php` | ❌ |
| `SubscriptionFactory.php` | ❌ |
| `PlanFactory.php` | ❌ |
| `IntegrationFactory.php` | ❌ |
| `BiometricDeviceFactory.php` | ❌ |
| `CenterFactory.php` | ✅ |
| `CustomerFactory.php` | ✅ |
| `WorkOrderFactory.php` | ✅ |
| `WorkOrderItemFactory.php` | ✅ |
| `ServiceFactory.php` | ✅ |
| `TenantFactory.php` | ✅ |
| `UserFactory.php` | ✅ |
| `VehicleFactory.php` | ✅ |
| `WarehouseFactory.php` | ✅ |
| `PartFactory.php` | ✅ |

**76 model, 10 factories = 13% factory coverage فقط** 🔴

## 1️⃣4️⃣ Policies مفقودة (متوقع 22+)

| المتوقع | الحالة |
|---|---|
| `PaymentPolicy.php` | ❌ |
| `PurchaseReturnPolicy.php` | ❌ |
| `LeavePolicy.php` | ❌ |
| `PayrollPolicy.php` | ❌ |
| `EmployeeDocumentPolicy.php` | ❌ |
| `PlanPolicy.php` | ❌ |
| `SubscriptionPolicy.php` | ❌ |
| `SettingPolicy.php` | ❌ |
| `CommunicationTemplatePolicy.php` | ❌ |
| `VehicleMileageLogPolicy.php` | ❌ |
| `InspectionTemplatePolicy.php` | ❌ |
| `NotificationPolicy.php` | ❌ |
| `AnnouncementPolicy.php` | ❌ |
| `IntegrationPolicy.php` | ❌ |
| `BiometricDevicePolicy.php` | ❌ |
| `ReportPolicy.php` | ❌ |

## 1️⃣5️⃣ Mail مفقود

| المتوقع | السبب |
|---|---|
| `WorkOrderCompletedMail.php` | ❌ |
| `InvoiceIssuedMail.php` | ❌ |
| `PaymentReceivedMail.php` | ❌ |
| `QuoteApprovedMail.php` | ❌ |
| `WelcomeCustomerMail.php` | ❌ |
| `LeaveApprovedMail.php` | ❌ |
| `PayrollProcessedMail.php` | ❌ |

## 1️⃣6️⃣ Notifications مفقودة

| المتوقع | السبب |
|---|---|
| `WorkOrderStatusChangedNotification.php` | ❌ (يستخدم InternalNotification) |
| `PaymentReceivedNotification.php` | ❌ |
| `InvoiceIssuedNotification.php` | ❌ |
| `LeaveApprovedNotification.php` | ❌ |
| `PayrollProcessedNotification.php` | ❌ |
| `PushNotification.php` (FCM/APNs) | ❌ |

## 1️⃣7️⃣ Exceptions مخصصة مفقودة

| المتوقع | السبب |
|---|---|
| `WorkOrderNotEditableException.php` | ❌ |
| `InsufficientStockException.php` | ❌ |
| `PaymentExceedsBalanceException.php` | ❌ |
| `InvalidWorkOrderStateException.php` | ❌ |
| `QuoteNotApprovableException.php` | ❌ |
| `DuplicatePlateNumberException.php` | ❌ |
| `CenterMismatchException.php` | ❌ |

## 1️⃣8️⃣ Console Commands مفقودة

| المتوقع | السبب |
|---|---|
| `RecalculateOverdueInvoices` | ❌ |
| `SendSubscriptionReminders` | ❌ |
| `CleanupSoftDeletedRecords` | ❌ |
| `GenerateDailyReport` | ❌ |
| `SyncInventoryLevels` | ❌ |
| `ProcessPendingPayments` | ❌ |
| `BackupDatabase` | ❌ |
| `RotateLogs` | ❌ |
| `SendBirthdayGreetings` | ❌ |
| `CheckExpiringDocuments` | ❌ |
| `ProcessPayroll` | ❌ |
| `SendAppointmentReminders` | ❌ |

---

# 🚨 نواقص وظيفية (Functional Gaps)

## 1️⃣ Workflow ناقص

| الموجود | الناقص |
|---|---|
| إنشاء WorkOrder | نسخ/استنساخ WorkOrder |
| تحديث WorkOrder | bulk update |
| حذف WorkOrder | soft delete (موجود في DB لكن ما في UI confirmation) |
| Cancel WorkOrder | restore cancelled |
| Complete WorkOrder | reopen (لو admin اكتشف خطأ) |
| Create Invoice | cancel invoice (لا يوجد endpoint) |
| Issue ZATCA Invoice | regenerate QR |
| Refund | refund workflow (الـ type موجود بس UI ناقص) |
| Approve Quote | bulk approve |
| Pay Invoice | split payment (دفعات جزئية) |
| **Create Customer** | ❌ **لا يوجد archive** (soft delete فقط) |
| **Create Customer** | ❌ **لا يوجد merge history UI** (action موجودة) |
| **Create Vehicle** | ❌ **لا يوجد vehicle history report** |
| **Create Supplier** | ❌ **لا يوجد supplier rating/review** |
| **Create Employee** | ❌ **لا يوجد employee portal password reset** |
| **Create Leave** | ❌ **لا يوجد leave balance tracking** |
| **Create Payroll** | ❌ **لا يوجد payslip PDF email** |

## 2️⃣ Features مفقودة

### Reports
- ❌ **Daily Report** (sales, expenses)
- ❌ **Monthly P&L** (income statement)
- ❌ **Inventory Report** (slow-moving, dead stock)
- ❌ **Employee Performance** (WO per tech, time)
- ❌ **Customer Lifetime Value**
- ❌ **Supplier Performance** (lead time, quality)
- ❌ **Tax Report** (VAT summary for ZATCA)
- ❌ **Aging Report** (receivables, payables)
- ❌ **Cash Flow**

### Notifications
- ❌ **Customer-facing notifications** (SMS/Email for status updates)
- ❌ **Appointment reminders**
- ❌ **Payment reminders**
- ❌ **Service completion notifications**
- ❌ **Push notifications** (FCM/APNs)

### Integrations
- ❌ **Accounting** (QuickBooks, Xero, Zoho)
- ❌ **Calendar** (Google, Outlook)
- ❌ **WhatsApp Business API** (لديك credits لكن ما في Business API)
- ❌ **Email Marketing** (Mailchimp)
- ❌ **CRM sync** (HubSpot, Salesforce)
- ❌ **Telematics** (vehicle tracking)
- ❌ **OBD integration** (vehicle diagnostics)

### File Management
- ❌ **Image optimization** (sharp, intervention/image)
- ❌ **File deduplication** (حفظ نفس الصورة مرتين)
- ❌ **Bulk upload**
- ❌ **Drag-and-drop reorder**
- ❌ **CDN integration**

### Search
- ❌ **Full-text search** (لديك search بسيط، بدون Meilisearch/Algolia)
- ❌ **Search filters** (multi-select, date range)
- ❌ **Search history**
- ❌ **Saved searches**

### Audit
- ❌ **Audit log UI** (لديك `AdminActivityLog` لكن ما في صفحة عرض)
- ❌ **Change tracking** (field-level diff)
- ❌ **IP logging** (من وين الـ login)
- ❌ **Device tracking**

## 3️⃣ UX ناقص

| Feature | Status |
|---|---|
| Dark mode toggle (visible) | موجود في tokens، لكن toggle UI؟ |
| Mobile responsive | (مذكور لكن ما تم فحصه) |
| Offline mode | ❌ |
| Multi-language switcher | ✅ (LocaleController) |
| Onboarding tour | ❌ |
| Help/Tooltips | ❌ |
| Keyboard shortcuts | ❌ |
| Bulk actions | ❌ |
| Print preview | (للـ invoices) |
| Export to PDF/Excel | (في بعض الأماكن) |
| Email templates editor | ❌ (لديك CommunicationTemplate) |
| Custom dashboard widgets | ✅ (DashboardCustomizer) |
| Saved filters | ❌ |
| Recent items | (في Dashboard) |

## 4️⃣ Mobile App Support

| المتوقع | Status |
|---|---|
| Mobile API (v1) | ❌ (لديك api/v1/attendance, ai, work-orders فقط) |
| Push notifications | ❌ |
| Offline sync | ❌ |
| Mobile-specific endpoints | ❌ |
| Mobile auth flow | ❌ |

## 5️⃣ DevOps مفقود

| المتوقع | Status |
|---|---|
| Docker | ❌ |
| Docker Compose | ❌ |
| Kubernetes manifests | ❌ |
| Terraform/Pulumi | ❌ |
| CI/CD (deploy step) | ❌ (CI فقط، no deploy) |
| Backup strategy | ❌ |
| Disaster recovery runbook | ❌ |
| Uptime monitoring | ❌ |
| APM (Application Performance Monitoring) | ❌ |
| Log aggregation (ELK/Datadog) | ❌ (لديك structured logs لكن ما في aggregation) |
| Health check dashboard | ❌ |

## 6️⃣ Testing ناقص

| المتوقع | Status |
|---|---|
| Frontend tests | ❌ (0 tests) |
| E2E tests (Playwright/Cypress) | ❌ |
| Load tests (k6, JMeter) | ❌ |
| Security tests (OWASP ZAP) | ❌ |
| Visual regression (Chromatic) | ❌ |
| Mutation testing (Infection) | ❌ |
| Coverage report | ❌ |
| Browser tests (Laravel Dusk) | ❌ |

---

# 🔗 تحليل الترابط (Cross-Reference Analysis)

## Webhooks & Callbacks

| Gateway | Webhook Endpoint | Status |
|---|---|---|
| Tamara | ❌ | مفقود |
| Tabby | ❌ | مفقود |
| Tap | ❌ | مفقود |
| Moyasar | ❌ | مفقود |
| Sentry | ✅ (auto) | مدمج |
| Authentica SMS | ❌ | مفقود |
| WhatsApp | ❌ | مفقود |

## Service ↔ Controller dependencies

| Service | Used By | Status |
|---|---|---|
| `WorkOrderSuggestionService` | `WorkOrderSuggestionController` | ✅ wired |
| `InvoiceService` | `WorkOrderStatusController`, `Payment::boot()` | ✅ but boot() = anti-pattern |
| `InventoryService` | `WorkOrderPartsController`, `GoodsReceivedNotesController` | (راجع) |
| `TwoFactorService` | `TwoFactorController`, `TwoFactorAuthController` | ✅ |
| `NotificationService` | كل الـ controllers | ✅ but static |
| `PaymentService` | `PaymentsController` (؟), `WorkOrderPaymentController` (inline) | ❌ duplicate |
| `QuoteConversionService` | `QuoteController::convert` | ✅ |
| `WhatsappService` | ? | غير مربوط |
| `SmsService` | `PhoneVerificationController` | ✅ |
| `EmailService` | ? | غير مربوط |
| `TamaraGateway` | ? | غير مربوط بـ controller |
| `TabbyGateway` | ? | غير مربوط |
| `TapGateway` | ? | غير مربوط |
| `MoyasarGateway` | ? | غير مربوط |
| `SubscriptionRenewalService` | `ProcessSubscriptionRenewals` command | ✅ |

⚠️ **4 Payment Gateways بدون controller = payment integration ناقص!**

## Middleware Usage

| Middleware | Used in Routes | Status |
|---|---|---|
| `auth` | كل app/* | ✅ |
| `verified` | كل app/* | ✅ |
| `tenant.active` | كل app/* | ✅ |
| `center.context` | app/* | ✅ |
| `system.admin` | system/* | ✅ |
| `permission` | (بعض routes) | 🟠 ناقص |
| `role` | (بعض routes) | 🟠 ناقص |
| `signed` | invitations | ✅ |
| `throttle` | api/*, auth | 🟠 0 على web |
| `EnsureTwoFactorEnabled` | (؟) | 🟠 ما في routes |
| `SetLocale` | global | ✅ |
| `SetPermissionsTeam` | global | ✅ |
| `SentryContext` | global | ✅ |
| `ConvertArabicNumerals` | global | ✅ |
| `HandleInertiaRequests` | global | ✅ |
| `PreventBackHistory` | global | ✅ |

---

# 📊 نسبة اكتمال المراجعة (Audit Completion)

| البُعد | الملفات المستلمة | المراجَعة | الناقصة | المتوقعة | النسبة |
|---|---|---|---|---|---|
| **Models** | 110 | 7 | 103 | 110 | **6%** |
| **Controllers** | 121 | 11 | 110 | 121 | **9%** |
| **Services** | 43 | 7 | 36 | 43 | **16%** |
| **Actions** | 3 | 3 | 0 | 25+ (مفروض أكثر) | **12%** |
| **Policies** | 27 | 1 | 26 | 35+ (أكثر) | **3%** |
| **Middleware** | 11 | 0 | 11 | 11 | **0%** |
| **Requests** | 15 | 3 | 12 | 60+ (مفروض أكثر) | **5%** |
| **Migrations** | 207 | 0 (إحصائيات فقط) | 207 | 207 | **0%** |
| **Seeders** | 18 | 0 | 18 | 25+ | **0%** |
| **Factories** | 10 | 0 | 10 | 50+ | **0%** |
| **Vue Pages** | 156 | 0 (إحصائيات) | 156 | 180+ | **0%** |
| **Vue Components** | 131 | 0 | 131 | 160+ | **0%** |
| **Composables** | 11 | 0 | 11 | 15+ | **0%** |
| **Tests** | 57 | 5 (مذكورة) | 52 | 200+ | **3%** |
| **Routes** | 489 | 0 (إحصائيات) | 489 | 489 | **0%** |
| **Console Commands** | 6 | 0 | 6 | 15+ | **0%** |
| **Jobs** | **0** | 0 | 0 | 15+ | **0%** |
| **Events** | **0** | 0 | 0 | 25+ | **0%** |
| **Listeners** | **0** | 0 | 0 | 10+ | **0%** |
| **Observers** | 3 | 0 | 3 | 5+ | **0%** |
| **Mail** | 5 | 0 | 5 | 12+ | **0%** |
| **Notifications** | 3 | 0 | 3 | 10+ | **0%** |
| **Exceptions** | 1 | 0 | 1 | 10+ | **0%** |
| **Providers** | 1 | 0 | 1 | 8+ | **0%** |
| **Traits** | 11 | 0 | 11 | 11 | **0%** |
| **Support** | 5 | 0 | 5 | 5 | **0%** |
| **Imports/Exports** | 5 | 0 | 5 | 10+ | **0%** |
| **Middleware** | 11 | 0 | 11 | 11 | **0%** |
| **Frontend Tests** | **0** | 0 | 0 | 50+ | **0%** |

---

# 🎯 الإحصائيات الإجمالية

| المؤشر | الرقم |
|---|---|
| **الملفات المستلمة (موجودة في الكود)** | **1,002** |
| **الملفات المراجَعة فعلياً (بعمق)** | **31** |
| **الملفات الناقصة من المراجعة** | **971** |
| **الملفات المتوقعة لكن غير موجودة** | **280+** |
| **الوحدات الوظيفية غير المكتملة** | **25+** |
| **نسبة اكتمال المراجعة الحالية** | **3.1%** |
| **نسبة اكتمال المشروع (تقريبي)** | **65%** (بناءً على اللي شفته) |

---

# 🔴 أولويات الفحص القادمة

1. **🔴 Jobs folder** (موجود 0، متوقع 15+) — لازم أنشئ
2. **🔴 Events folder** (موجود 0، متوقع 25+) — لازم أنشئ
3. **🔴 Listeners folder** (موجود 0، متوقع 10+) — لازم أنشئ
4. **🔴 Middleware** (11 ملف، 0 مراجعة)
5. **🟠 Migrations** (207، 0 مراجعة)
6. **🟠 Tests** (57، راجعت 5 فقط)
7. **🟠 Service Files الباقية** (36)
8. **🟠 Controller الباقية** (110)
9. **🟠 Model الباقية** (103)
10. **🟠 Frontend** (287 ملف Vue)

---

# 📝 ملاحظات

- الـ registry محدّث بتاريخ 2026-07-20
- نسبة الإكمال الحالية: **3.1%**
- يجب أن تصل إلى **100%** لاكتمال المراجعة
- الأولوية: 1) إنشاء Jobs/Events/Listeners، 2) مراجعة Middleware، 3) تكملة Frontend
- المجلدات المفقودة: **app/Jobs/**، **app/Events/**، **app/Listeners/**
- 4 Payment Gateways بدون controller = payment integration معطّل

---

**تم إعداد التقرير بواسطة:** Mavis (مدير المراجعة)
**الـ Branch:** `integration/p0-print-settings`
**الـ Commit الأخير:** `45149217 zxz79`
