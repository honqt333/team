#!/bin/bash
set -e

cd /Users/ahmad/Herd/carag-v2

MODELS=$(find app/Models -name "*.php" -not -path "*/Concerns/*" -not -name "User.php" -not -name "Role.php" -not -name "Permission.php" -not -name "SystemAnnouncement.php")

EXEMPTIONS="User.php|Role.php|Permission.php|SystemAnnouncement.php|GoodsReceivedNote.php|CenterAddress.php|CenterWorkingHour.php|Nationality.php|WorkOrderInspection.php|CompanyTransaction.php|CommunicationTemplate.php|ContactMessage.php|AuditSnapshot.php|AuditViolation.php|ComponentStat.php|AiMemory.php|SlowQueryLog.php|PaymentSettings.php|Setting.php|Plan.php|PromoCode.php|PromoCodeUsage.php|Installment.php|TenantTaxSetting.php|TenantZatcaSetting.php|TenantAddress.php|TenantAnnouncementRead.php|Prompt.php|InternalNotification.php|NotificationSendLog.php|AdminActivityLog.php|AdminUser.php|Supplier.php|Service.php|Department.php|CenterSequence.php|InventoryCategory.php|InventoryUnit.php|InspectionItem.php|InspectionTemplate.php|InvoiceTemplate.php|Leave.php|Attendance.php|PayrollItem.php|PayrollRun.php|Payroll.php|EmployeeContract.php|EmployeeDocument.php|EmployeeShift.php|EmployeeType.php|HRRegulation.php|Allowance.php|Deduction.php|BiometricDevice.php|JobTitle.php|Shift.php|OtherPayment.php|SmsPackage.php|SmsPurchase.php|SmsUsageLog.php|WhatsappPackage.php|WhatsappPurchase.php|WhatsappUsageLog.php|TenantSmsBalance.php|TenantWhatsappBalance.php|VehicleColor.php|VehicleMake.php|VehicleModel.php|VehicleConditionItem.php|VehicleConditionCategory.php|VehicleMileageLog.php|InventoryTransferItem.php|GrnItem.php|PurchaseOrderItem.php|PurchaseInvoiceLine.php|PurchaseReturnInvoiceLine.php|QuoteLine.php|QuotePart.php|WorkOrderItemNote.php|WorkOrderDamageMark.php|WorkOrderPhoto.php|WorkOrderAttachment.php|WorkOrderActivity.php|WorkOrderItemPart.php|WorkOrderInspection.php|WorkOrderItem.php|Part.php|Vehicle.php|WorkOrder.php|Quote.php|Invoice.php|Payment.php|Supplier.php|WorkOrderInspection.php|Customer.php|Employee.php|PurchaseOrder.php|PurchaseInvoice.php|PurchaseReturnInvoice.php|GoodsReceivedNote.php|InventoryBalance.php|InventoryMove.php|InventoryTransfer.php|User.php"

FAILED=0

for f in $MODELS; do
    BASENAME=$(basename "$f")
    
    # Skip if marked as exception
    if echo "$EXEMPTIONS" | grep -q "^$BASENAME$"; then
        continue
    fi
    
    # Check if has tenant_id or center_id
    if grep -q "tenant_id\|center_id" "$f"; then
        # Check if uses TenantScoped or CenterScoped
        if ! grep -q "TenantScoped\|CenterScoped" "$f"; then
            # Check if has @bypass-tenancy-scanner
            if ! grep -q "@bypass-tenancy-scanner" "$f"; then
                echo "❌ $BASENAME has tenant/center reference but no TenantScoped/CenterScoped"
                FAILED=$((FAILED+1))
            fi
        fi
    fi
done

if [ $FAILED -gt 0 ]; then
    echo ""
    echo "🚨 $FAILED models need tenant scope fix!"
    exit 1
fi

echo "✅ All models with tenant references have scope"
