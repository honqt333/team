<template>
    <BaseModal :show="show" @close="$emit('close')" size="md" z-index-class="z-[60]">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-xs"
                >
                    <span class="text-lg">🤝</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-text-primary">
                        {{
                            item
                                ? $t('purchasing.supplier_services.edit')
                                : $t('purchasing.supplier_services.add')
                        }}
                    </h3>
                    <p class="text-xs text-text-secondary">
                        {{ $t('purchasing.supplier_services.title') }}
                    </p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-4 py-1">
            <!-- Work Order Department & Service Selectors (when Work Order has items) -->
            <div v-if="hasWorkOrderItems" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <FormField :label="$t('purchasing.supplier_services.department')">
                    <SearchableSelect
                        v-model="form.department_id"
                        :options="workOrderDepartmentOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('purchasing.supplier_services.select_department')"
                        class="w-full"
                    />
                </FormField>

                <FormField :label="$t('purchasing.supplier_services.service_from_order')">
                    <SearchableSelect
                        v-model="form.work_order_item_id"
                        :options="filteredWorkOrderServiceOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('purchasing.supplier_services.select_service')"
                        class="w-full"
                        @change="onWorkOrderServiceSelected"
                    />
                </FormField>
            </div>

            <!-- Service Name (Auto-filled from Work Order Service or free text) -->
            <FormField
                v-slot="field"
                :label="$t('purchasing.supplier_services.service_name')"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    type="text"
                    v-model="form.service_name"
                    required
                    :placeholder="$t('purchasing.supplier_services.service_name_placeholder')"
                    class="w-full"
                />
                <p v-if="hasWorkOrderItems" class="text-[11px] text-text-muted mt-1">
                    {{ $t('purchasing.supplier_services.custom_service_toggle') }}
                </p>
            </FormField>

            <!-- Cost Price & VAT -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <FormField
                    v-slot="field"
                    :label="$t('purchasing.supplier_services.cost_price')"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        type="number"
                        v-model.number="form.unit_cost"
                        min="0"
                        step="any"
                        required
                        class="w-full font-mono font-bold"
                        dir="ltr"
                    />
                </FormField>

                <FormField v-slot="field" :label="$t('purchasing.supplier_services.tax_rate')">
                    <div class="flex items-center gap-2">
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            type="number"
                            v-model.number="form.tax_rate"
                            min="0"
                            max="100"
                            step="any"
                            class="w-full font-mono font-bold"
                            dir="ltr"
                        />
                        <span class="text-xs font-bold text-text-secondary font-mono">%</span>
                    </div>
                </FormField>
            </div>

            <!-- Calculation Summary Card -->
            <div
                class="p-3.5 rounded-xl bg-purple-50/60 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 space-y-2"
            >
                <div class="flex items-center justify-between text-xs text-text-secondary">
                    <span>{{ $t('purchasing.supplier_services.cost_price') }}:</span>
                    <span class="font-mono font-bold" dir="ltr">
                        {{ formatCurrency(calculatedCost) }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs text-text-secondary">
                    <span>
                        {{ $t('purchasing.supplier_services.tax_amount') }} ({{ form.tax_rate }}%):
                    </span>
                    <span
                        class="font-mono font-bold text-purple-700 dark:text-purple-300"
                        dir="ltr"
                    >
                        + {{ formatCurrency(calculatedTax) }}
                    </span>
                </div>
                <div
                    class="pt-2 border-t border-purple-200/60 dark:border-purple-900/60 flex items-center justify-between text-xs font-black text-text-primary"
                >
                    <span>{{ $t('purchasing.supplier_services.total') }}:</span>
                    <span class="text-sm font-mono text-purple-700 dark:text-purple-300" dir="ltr">
                        {{ formatCurrency(calculatedTotal) }}
                    </span>
                </div>
            </div>

            <!-- Notes -->
            <FormField :label="$t('purchasing.supplier_services.notes')">
                <textarea
                    v-model="form.notes"
                    rows="2"
                    :placeholder="$t('purchasing.supplier_services.notes_placeholder')"
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-border-subtle bg-surface text-text-primary focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary shadow-xs"
                ></textarea>
            </FormField>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!isValid"
                    class="inline-flex items-center justify-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 rounded-xl shadow-xs transition-all cursor-pointer"
                >
                    <span>✓</span>
                    <span>{{ $t('purchasing.supplier_services.save_btn') }}</span>
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    item: Object,
    workOrder: { type: Object, default: null },
    taxIncluded: { type: Boolean, default: false },
    tenantTaxSettings: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'saved']);
const { t, locale } = useI18n();
const { formatCurrency } = useNumberFormat();

const defaultVatRate = computed(() => {
    const rate = props.tenantTaxSettings?.services_vat_rate;
    return rate !== undefined && rate !== null ? Number(rate) : 15;
});

const activeWorkOrderItems = computed(() => {
    return (props.workOrder?.items || []).filter((it) => it.status !== 'cancelled');
});

const hasWorkOrderItems = computed(() => activeWorkOrderItems.value.length > 0);

const getDepartmentKey = (woItem) => {
    if (woItem.service?.type === 'package') return 'packages';
    return woItem.department_id ?? woItem.service?.department_id ?? null;
};

const getDepartmentLabel = (woItem) => {
    if (woItem.service?.type === 'package') {
        return t('purchasing.supplier_services.packages');
    }
    const dept = woItem.department || woItem.service?.department;
    if (!dept) return null;
    return locale.value === 'ar'
        ? dept.name_ar || dept.name_en || dept.name
        : dept.name_en || dept.name_ar || dept.name;
};

const getServiceLabel = (woItem) => {
    if (woItem.service) {
        return locale.value === 'ar'
            ? woItem.service.name_ar ||
                  woItem.service.name_en ||
                  woItem.service.name ||
                  woItem.title
            : woItem.service.name_en ||
                  woItem.service.name_ar ||
                  woItem.service.name ||
                  woItem.title;
    }
    return woItem.title || '';
};

const workOrderDepartmentOptions = computed(() => {
    const map = new Map();
    for (const woItem of activeWorkOrderItems.value) {
        const key = getDepartmentKey(woItem);
        const label = getDepartmentLabel(woItem);
        if (key !== null && label && !map.has(String(key))) {
            map.set(String(key), { value: key, label });
        }
    }
    return Array.from(map.values());
});

const form = ref({
    department_id: null,
    work_order_item_id: null,
    service_name: '',
    unit_cost: 0,
    tax_rate: 15,
    notes: '',
});

const filteredWorkOrderServiceOptions = computed(() => {
    let items = activeWorkOrderItems.value;
    if (form.value.department_id !== null && form.value.department_id !== '') {
        items = items.filter(
            (it) => String(getDepartmentKey(it)) === String(form.value.department_id)
        );
    }
    return items.map((it) => ({
        value: it.id,
        label: getServiceLabel(it),
        department_id: getDepartmentKey(it),
    }));
});

const onWorkOrderServiceSelected = (val) => {
    if (!val) return;
    const found = activeWorkOrderItems.value.find((it) => String(it.id) === String(val));
    if (found) {
        form.value.service_name = getServiceLabel(found);
        const deptKey = getDepartmentKey(found);
        if (deptKey !== null && !form.value.department_id) {
            form.value.department_id = deptKey;
        }
    }
};

watch(
    () => form.value.department_id,
    (newDept) => {
        if (!newDept || !form.value.work_order_item_id) return;
        const currentService = activeWorkOrderItems.value.find(
            (it) => String(it.id) === String(form.value.work_order_item_id)
        );
        if (currentService && String(getDepartmentKey(currentService)) !== String(newDept)) {
            form.value.work_order_item_id = null;
        }
    }
);

watch(
    () => props.show,
    (val) => {
        if (val) {
            if (props.item) {
                form.value = {
                    department_id: props.item.department_id ?? null,
                    work_order_item_id: props.item.work_order_item_id ?? null,
                    service_name:
                        props.item.name || props.item.custom_name || props.item.part?.name_ar || '',
                    unit_cost: Number(props.item.unit_cost || 0),
                    tax_rate:
                        props.item.tax_rate !== undefined
                            ? Number(props.item.tax_rate)
                            : defaultVatRate.value,
                    notes: props.item.notes || '',
                };
            } else {
                form.value = {
                    department_id: null,
                    work_order_item_id: null,
                    service_name: '',
                    unit_cost: 0,
                    tax_rate: defaultVatRate.value,
                    notes: '',
                };
            }
        }
    },
    { immediate: true }
);

const calculatedCost = computed(() => Number(form.value.unit_cost || 0));
const calculatedTax = computed(() => {
    const cost = calculatedCost.value;
    const rate = Number(form.value.tax_rate || 0);
    return Math.round(cost * (rate / 100) * 100) / 100;
});
const calculatedTotal = computed(() => {
    return Math.round((calculatedCost.value + calculatedTax.value) * 100) / 100;
});

const isValid = computed(() => {
    return form.value.service_name.trim().length > 0 && calculatedCost.value > 0;
});

const submit = () => {
    if (!isValid.value) return;

    const trimmedName = form.value.service_name.trim();
    emit('saved', {
        part_id: null,
        is_custom: true,
        is_service: true,
        department_id: form.value.department_id || null,
        work_order_item_id: form.value.work_order_item_id || null,
        name: trimmedName,
        custom_name: trimmedName,
        part: {
            name_ar: trimmedName,
            name_en: trimmedName,
            sku: 'SERVICE',
        },
        qty: 1,
        unit_cost: calculatedCost.value,
        discount: 0,
        tax_rate: Number(form.value.tax_rate || 0),
        tax_amount: calculatedTax.value,
        total: calculatedTotal.value,
        notes: form.value.notes?.trim() || null,
    });
};
</script>
