# 📋 دليل النماذج (Modals) - نظام Carag

## نظرة عامة

هذا الملف يوثق جميع النماذج المنبثقة (Modals) في النظام مع شرح استخدامها والـ props المطلوبة.

---

## 📁 هيكلة الملفات

```
resources/js/Components/
├── BaseModal.vue                    # النموذج الأساسي (Base)
├── ConfirmModal.vue                 # نموذج التأكيد
├── DialogModal.vue                  # نموذج الحوار
├── Modal.vue                        # نموذج بسيط
│
├── Customers/
│   ├── CustomerFormModal.vue        # إنشاء/تعديل عميل
│   ├── CustomerImportModal.vue      # استيراد العملاء
│   └── CustomerMergeModal.vue       # دمج العملاء
│
├── Vehicles/
│   └── VehicleFormModal.vue         # إنشاء/تعديل مركبة
│
├── Quotes/
│   ├── QuoteFormModal.vue           # إنشاء/تعديل عرض سعر
│   └── QuoteServiceModal.vue        # إضافة خدمة للعرض
│
├── WorkOrders/
│   ├── WorkOrderFormModal.vue       # إنشاء/تعديل كرت العمل
│   ├── WorkOrderServiceModal.vue    # إضافة خدمة للكرت
│   ├── WorkOrderItemModal.vue       # تعديل خدمة (شامل)
│   ├── PrintOptionsModal.vue        # خيارات الطباعة
│   ├── PaymentsListModal.vue        # قائمة المدفوعات
│   └── PaymentFormModal.vue         # إضافة دفعة
│
├── Inventory/
│   └── AddPartModal.vue             # إضافة قطع غيار
│
├── ServiceFormModal.vue             # إضافة خدمة (عام)
└── DepartmentFormModal.vue          # إضافة قسم
```

---

## 🔧 النماذج الأساسية

### 1. BaseModal
**الموقع:** `Components/BaseModal.vue`

النموذج الأساسي الذي تبنى عليه جميع النماذج الأخرى.

```vue
<BaseModal :show="showModal" @close="showModal = false" size="xl">
    <template #title>العنوان</template>
    <!-- المحتوى -->
    <template #footer>الأزرار</template>
</BaseModal>
```

**Props:**
| Prop | النوع | الافتراضي | الوصف |
|------|-------|-----------|-------|
| `show` | Boolean | `false` | إظهار/إخفاء النموذج |
| `size` | String | `'md'` | الحجم: `sm`, `md`, `lg`, `xl`, `2xl` |
| `closeable` | Boolean | `true` | السماح بالإغلاق |

---

## 👥 نماذج العملاء

### 2. CustomerFormModal
**الموقع:** `Components/Customers/CustomerFormModal.vue`

إنشاء أو تعديل بيانات عميل.

```vue
<CustomerFormModal
    :show="showModal"
    :customer="customerToEdit"      <!-- null للإنشاء -->
    @close="showModal = false"
    @saved="handleSaved"
/>
```

**Props:**
| Prop | النوع | الوصف |
|------|-------|-------|
| `show` | Boolean | إظهار النموذج |
| `customer` | Object/null | بيانات العميل للتعديل أو null للإنشاء |

**Events:**
- `@close` - عند إغلاق النموذج
- `@saved` - عند حفظ العميل بنجاح

---

### 3. CustomerImportModal
**الموقع:** `Components/Customers/CustomerImportModal.vue`

استيراد العملاء من ملف Excel.

```vue
<CustomerImportModal
    :show="showImportModal"
    @close="showImportModal = false"
    @imported="refreshList"
/>
```

---

### 4. CustomerMergeModal
**الموقع:** `Components/Customers/CustomerMergeModal.vue`

دمج عميلين في عميل واحد.

```vue
<CustomerMergeModal
    :show="showMergeModal"
    :source="sourceCustomer"
    :target="targetCustomer"
    @close="showMergeModal = false"
    @merged="handleMerged"
/>
```

---

## 🚗 نماذج المركبات

### 5. VehicleFormModal
**الموقع:** `Components/Vehicles/VehicleFormModal.vue`

إنشاء أو تعديل مركبة.

```vue
<VehicleFormModal
    :show="showModal"
    :vehicle="vehicleToEdit"
    :customers="customersList"
    :makes="makesList"
    :colors="colorsList"
    :modelsByMake="modelsObject"
    @close="showModal = false"
    @saved="handleSaved"
    @customer-created="handleNewCustomer"
/>
```

**Props:**
| Prop | النوع | الوصف |
|------|-------|-------|
| `show` | Boolean | إظهار النموذج |
| `vehicle` | Object/null | بيانات المركبة للتعديل |
| `customers` | Array | قائمة العملاء |
| `makes` | Array | قائمة الشركات المصنعة |
| `colors` | Array | قائمة الألوان |
| `modelsByMake` | Object | الموديلات مجمعة حسب الشركة |

---

## 📋 نماذج كروت العمل

### 6. WorkOrderFormModal
**الموقع:** `Components/WorkOrders/WorkOrderFormModal.vue`

إنشاء أو تعديل كرت عمل.

```vue
<WorkOrderFormModal
    :show="showEditModal"
    :workOrder="workOrderToEdit"
    :vehicle="preSelectedVehicle"
    :customers="customers"
    :makes="makes"
    :colors="colors"
    :modelsByMake="modelsByMake"
    :departments="departments"
    @close="showEditModal = false"
    @saved="refreshWorkOrder"
/>
```

**Props:**
| Prop | النوع | الوصف |
|------|-------|-------|
| `show` | Boolean | إظهار النموذج |
| `workOrder` | Object/null | كرت العمل للتعديل |
| `vehicle` | Object/null | مركبة محددة مسبقاً |
| `departments` | Array | قائمة الأقسام |

---

### 7. WorkOrderServiceModal
**الموقع:** `Components/WorkOrders/WorkOrderServiceModal.vue`

إضافة خدمة جديدة لكرت العمل.

```vue
<WorkOrderServiceModal
    :show="showServiceModal"
    :workOrderId="workOrder.id"
    :departmentId="selectedDeptId"
    :services="servicesList"
    @close="closeServiceModal"
    @saved="refreshAfterSave"
/>
```

**Props:**
| Prop | النوع | الوصف |
|------|-------|-------|
| `workOrderId` | Number | معرف كرت العمل |
| `departmentId` | Number | معرف القسم |
| `services` | Array | قائمة الخدمات المتاحة |

---

### 8. WorkOrderItemModal
**الموقع:** `Components/WorkOrders/WorkOrderItemModal.vue`

تعديل خدمة في كرت العمل (مع تبويبات: التفاصيل، الفنيين، قطع الغيار، الملاحظات).

```vue
<WorkOrderItemModal
    :show="showItemModal"
    :item="selectedItem"
    :technicians="techniciansList"
    @close="closeItemModal"
    @saved="refreshAfterSave"
/>
```

**Props:**
| Prop | النوع | الوصف |
|------|-------|-------|
| `item` | Object | الخدمة المراد تعديلها |
| `technicians` | Array | قائمة الفنيين |

---

### 9. PrintOptionsModal
**الموقع:** `Components/WorkOrders/PrintOptionsModal.vue`

خيارات طباعة كرت العمل.

```vue
<PrintOptionsModal
    :show="showPrintModal"
    @close="showPrintModal = false"
    @print="handlePrint"
/>
```

**Events:**
- `@print(type)` - إختيار نوع الطباعة: `condition`, `work_order`, `proforma`, `payments`

---

### 10. PaymentsListModal
**الموقع:** `Components/WorkOrders/PaymentsListModal.vue`

عرض قائمة المدفوعات مع إمكانية الإضافة والتعديل.

```vue
<PaymentsListModal
    :show="showPaymentsListModal"
    :work-order-id="workOrder.id"
    :payments="workOrder.payments"
    :grand-total="total"
    :total-paid="paid"
    :balance="balance"
    @close="showPaymentsListModal = false"
    @refresh="refreshWorkOrder"
/>
```

---

### 11. PaymentFormModal
**الموقع:** `Components/WorkOrders/PaymentFormModal.vue`

إضافة أو تعديل دفعة.

```vue
<PaymentFormModal
    :show="showPaymentForm"
    :workOrderId="workOrderId"
    :payment="paymentToEdit"
    :balance="remainingBalance"
    @close="showPaymentForm = false"
    @saved="handlePaymentSaved"
/>
```

---

## 🔧 نماذج المخزون

### 12. AddPartModal
**الموقع:** `Components/Inventory/AddPartModal.vue`

إضافة قطع غيار لكرت العمل.

```vue
<AddPartModal
    v-model="showAddPartModal"
    :workOrderId="workOrder.id"
    :items="workOrder.items"
    :warehouses="warehouses"
    :inventoryParts="inventoryParts"
    @saved="handlePartSaved"
/>
```

---

## 💰 نماذج عروض الأسعار

### 13. QuoteFormModal
**الموقع:** `Components/Quotes/QuoteFormModal.vue`

إنشاء أو تعديل عرض سعر.

```vue
<QuoteFormModal
    :show="showQuoteModal"
    :quote="quoteToEdit"
    :customers="customers"
    :vehicles="vehicles"
    @close="showQuoteModal = false"
    @saved="handleSaved"
/>
```

---

### 14. QuoteServiceModal
**الموقع:** `Components/Quotes/QuoteServiceModal.vue`

إضافة خدمة لعرض السعر.

```vue
<QuoteServiceModal
    :show="showServiceModal"
    :quoteId="quote.id"
    :services="services"
    @close="showServiceModal = false"
    @saved="refreshQuote"
/>
```

---

## ⚙️ نماذج الإعدادات

### 15. ServiceFormModal
**الموقع:** `Components/ServiceFormModal.vue`

إضافة أو تعديل خدمة.

```vue
<ServiceFormModal
    :show="showModal"
    :service="serviceToEdit"
    :departments="departments"
    @close="showModal = false"
    @saved="refreshList"
/>
```

---

### 16. DepartmentFormModal
**الموقع:** `Components/DepartmentFormModal.vue`

إضافة أو تعديل قسم.

```vue
<DepartmentFormModal
    :show="showModal"
    :department="departmentToEdit"
    @close="showModal = false"
    @saved="refreshList"
/>
```

---

## 💡 أفضل الممارسات

### 1. استخدام النماذج
```vue
<script setup>
import { ref } from 'vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';

const showModal = ref(false);
const customerToEdit = ref(null);

function openCreate() {
    customerToEdit.value = null;
    showModal.value = true;
}

function openEdit(customer) {
    customerToEdit.value = customer;
    showModal.value = true;
}

function handleSaved() {
    showModal.value = false;
    // تحديث البيانات
}
</script>
```

### 2. التعامل مع الأحداث
```vue
<CustomerFormModal
    :show="showModal"
    :customer="customerToEdit"
    @close="showModal = false"
    @saved="handleSaved"
/>
```

### 3. أحجام النماذج
- `sm` - نماذج صغيرة (تأكيد، رسائل)
- `md` - نماذج متوسطة (نماذج بسيطة)
- `lg` - نماذج كبيرة (نماذج متعددة الحقول)
- `xl` - نماذج كبيرة جداً (نماذج مع تبويبات)
- `2xl` - نماذج كاملة العرض

---

## 📅 آخر تحديث
- **التاريخ:** 2026-01-01
- **الإصدار:** 1.0
