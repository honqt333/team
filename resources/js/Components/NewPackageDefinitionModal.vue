<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                {{ $t('packages.add_new_definition') || 'إضافة تعريف باقة جديد' }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Names Row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_ar" dir="rtl"
                        :placeholder="$t('services_management.form.name_ar_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_ar }"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_ar }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_en" dir="ltr"
                        :placeholder="$t('services_management.form.name_en_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_en }"
                    />
                    <p v-if="form.errors.name_en" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_en }}</p>
                </div>
            </div>

            <!-- Descriptions Row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_ar') }}
                    </label>
                    <textarea v-model="form.description_ar" rows="2" dir="rtl"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_en') }}
                    </label>
                    <textarea v-model="form.description_en" rows="2" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
            </div>

            <!-- Package Items Section -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl">
                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center rounded-t-xl">
                    <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        {{ $t('packages.items') }}
                    </h3>
                </div>

                <div class="p-4 space-y-4 rounded-b-xl">
                    <!-- Add Item Row -->
                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('packages.select_service') }}</label>
                            <SearchableSelect v-model="newItem.id" :options="availableServices" :label="''"
                                :placeholder="$t('common.choose')" option-label="name" option-value="id" />
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('packages.quantity') }}</label>
                            <input type="number" v-model.number="newItem.quantity" min="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <button type="button" @click="addItem" :disabled="!newItem.id"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed h-[38px]">
                            {{ $t('packages.add_item') }}
                        </button>
                    </div>

                    <!-- Items List -->
                    <div v-if="form.items.length > 0" class="space-y-2">
                        <div v-for="(item, idx) in form.items" :key="idx"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                    {{ item.quantity }}x
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ getServiceName(item.id) }}</p>
                                </div>
                            </div>
                            <button type="button" @click="removeItem(idx)"
                                class="p-1.5 text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-xs text-center text-gray-400 py-3">{{ $t('packages.empty_items_hint') || 'لم يتم إضافة خدمات للباقة بعد.' }}</p>
                    <p v-if="form.errors.items" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.items }}</p>
                </div>
            </div>

            <!-- Toggles Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Active Toggle -->
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('services_management.form.is_active') }}</span>
                </div>

                <!-- Allow Price Override Toggle -->
                <div class="flex items-start gap-3">
                    <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                        <input type="checkbox" v-model="form.allow_price_override" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.allow_price_override') }}</span>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $t('pricing.allow_price_override_hint') }}</p>
                    </div>
                </div>
            </div>

            <!-- Notice: Price will be set separately -->
            <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200/50 dark:border-amber-700/30">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    {{ $t('packages.definition_price_notice') || 'ملاحظة: يتم تحديد تفاصيل سعر الباقة والخصم والحد الأدنى للبيع بشكل منفصل لكل فرع على حدة بعد حفظ التعريف.' }}
                </p>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button @click="submitForm" :disabled="form.processing || form.items.length === 0"
                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useLocalized } from '@/Composables/useLocalized';

const props = defineProps({
    show: { type: Boolean, default: false },
    availableServices: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();

const newItem = ref({ id: null, quantity: 1 });

const form = useForm({
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '',
    base_price: 0,
    min_price: 0,
    items: [],
    type: 'package',
    is_active: true,
    default_discount_type: 'none',
    default_discount_value: null,
    allow_price_override: false,
});

watch(() => props.show, (open) => {
    if (open) {
        form.reset();
        form.items = [];
        form.type = 'package';
        form.is_active = true;
        form.allow_price_override = false;
        newItem.value = { id: null, quantity: 1 };
    }
});

function getServiceName(id) {
    const service = props.availableServices.find(s => s.id === id);
    return service ? getName(service) : '';
}

function addItem() {
    if (newItem.value.id) {
        const existing = form.items.find(i => i.id === newItem.value.id);
        if (existing) {
            existing.quantity += newItem.value.quantity;
        } else {
            form.items.push({
                id: newItem.value.id,
                quantity: newItem.value.quantity
            });
        }
        newItem.value.id = null;
        newItem.value.quantity = 1;
        form.clearErrors('items');
    }
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function submitForm() {
    form.post('/app/services', {
        preserveScroll: true,
        onSuccess: () => {
            const newServiceId = usePage().props.flash?.new_service_id ?? null;
            form.reset();
            emit('saved', newServiceId);
        },
    });
}
</script>
