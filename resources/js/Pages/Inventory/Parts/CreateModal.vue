<template>
    <DialogModal :show="show" @close="handleClose" max-width="4xl">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-xl font-bold">{{ form.id ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}</span>
                <span v-if="form.id" class="text-sm font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">{{ form.sku }}</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Image Upload Section -->
                <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl bg-gray-50/50 dark:bg-gray-900/30 transition-all hover:bg-gray-100 dark:hover:bg-gray-800/50 group relative overflow-hidden">
                    <input
                        type="file"
                        ref="imageInput"
                        class="hidden"
                        accept="image/*"
                        @change="handleImageChange"
                    />
                    
                    <div v-if="imagePreview" class="relative group/preview">
                        <img :src="imagePreview" class="w-32 h-32 object-cover rounded-2xl shadow-xl border-4 border-white dark:border-gray-800 transition-transform group-hover/preview:scale-105" />
                        <button 
                            @click="removeImage"
                            class="absolute -top-2 -right-2 p-1.5 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover/preview:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <div v-else @click="imageInput.click()" class="flex flex-col items-center cursor-pointer">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ $t('inventory.parts.add_image') || 'Add Part Image' }}</span>
                        <span class="text-[10px] text-gray-400 mt-1">{{ $t('common.max_size', {size: '2MB'}) }}</span>
                    </div>
                    
                    <p v-if="form.errors.image" class="mt-2 text-xs text-red-500">{{ form.errors.image }}</p>
                </div>

                <!-- Top Row: SKU, Barcode, Unit -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.sku') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.sku"
                                type="text"
                                dir="ltr"
                                @input="sanitizeInput($event, 'sku')"
                                :class="['w-full pl-4 pr-3 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.sku ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                :placeholder="$t('inventory.parts.sku_placeholder')"
                            />
                        </div>
                        <p v-if="form.errors.sku" class="mt-1 text-xs text-red-500">{{ form.errors.sku }}</p>
                    </div>

                    <!-- Barcode -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.barcode') }}
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.barcode"
                                type="text"
                                dir="ltr"
                                @input="sanitizeInput($event, 'barcode')"
                                :class="['w-full pl-10 pr-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.barcode ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                placeholder="Scan..."
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h2v14H3V5zm4 0h2v14H7V5zm4 0h1v14h-1V5zm3 0h2v14h-2V5zm4 0h1v14h-1V5zm3 0h2v14h-2V5z" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="form.errors.barcode" class="mt-1 text-xs text-red-500">{{ form.errors.barcode }}</p>
                    </div>

                    <!-- Unit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.unit') }} <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.unit_id"
                            :options="unitOptions"
                            option-label="label"
                            option-value="value"
                            :label="''"
                            :placeholder="$t('common.select')"
                            :error="form.errors.unit_id"
                            required
                        />
                    </div>
                </div>

                <!-- Second Row: Names -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name AR -->
                    <div :class="isAr ? 'order-1' : 'order-2'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.name_ar') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name_ar"
                            type="text"
                            :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.name_ar ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                            :placeholder="$t('inventory.parts.name_ar_placeholder')"
                        />
                        <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.name_ar }}</p>
                    </div>

                    <!-- Name EN -->
                    <div :class="isAr ? 'order-2' : 'order-1'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.name_en') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name_en"
                            type="text"
                            dir="ltr"
                            :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.name_en ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                            :placeholder="$t('inventory.parts.name_en_placeholder')"
                        />
                        <p v-if="form.errors.name_en" class="mt-1 text-xs text-red-500">{{ form.errors.name_en }}</p>
                    </div>
                </div>

                <!-- Third Row: Description -->
                <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        :placeholder="$t('inventory.parts.description_placeholder')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all resize-none"
                    ></textarea>
                </div>

                <!-- Collapsible Sections -->
                <div class="space-y-4">
                     <!-- Category / Make & Model (Using Category for now as per code, but labeled to match image specific request idea) -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <button @click="toggleSection('category')" class="w-full px-4 py-3 flex items-center justify-between text-start hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $t('inventory.parts.category') }} / {{ $t('inventory.parts.make_model_placeholder') }}</span>
                            <svg :class="{'rotate-180': sections.category}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="sections.category" class="px-4 py-4 border-t border-gray-100 dark:border-gray-700">
                             <SearchableSelect
                                v-model="form.category_id"
                                :options="categoryOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('inventory.parts.category')"
                                :placeholder="$t('common.select')"
                                :error="form.errors.category_id"
                            />
                            <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $t('inventory.parts.make_model_hint') }}
                            </p>
                        </div>
                    </div>

                    <!-- Pricing & Inventory Section -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <button @click="toggleSection('pricing')" class="w-full px-4 py-3 flex items-center justify-between text-start hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $t('inventory.parts.inventory_pricing') }}</span>
                            <svg :class="{'rotate-180': sections.pricing}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="sections.pricing" class="px-4 py-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Default Sale Price -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('inventory.parts.default_sale_price') }}</label>
                                    <input v-model="form.default_sale_price" type="text" @input="sanitizeInput($event, 'default_sale_price')" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm font-mono" dir="ltr" />
                                </div>
                                
                                <!-- Min Sale Price -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('inventory.parts.min_sale_price') }}</label>
                                    <input v-model="form.min_sale_price" type="text" @input="sanitizeInput($event, 'min_sale_price')" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm font-mono" dir="ltr" />
                                </div>

                                <!-- Min Qty -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('inventory.parts.min_qty') }}</label>
                                    <input v-model="form.min_qty" type="text" @input="sanitizeInput($event, 'min_qty')" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm font-mono" dir="ltr" />
                                </div>

                                <!-- Reorder Qty -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('inventory.parts.reorder_qty') }}</label>
                                    <input v-model="form.reorder_qty" type="text" @input="sanitizeInput($event, 'reorder_qty')" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm font-mono" dir="ltr" />
                                </div>

                                <!-- Cost (Read Only / Placeholder for now as it comes from Stock) -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('inventory.stock.wac') }} ({{ $t('common.read_only') }})</label>
                                    <input disabled :value="0" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-500 text-sm cursor-not-allowed font-mono" dir="ltr" />
                                </div>

                                <!-- Active Toggle -->
                                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                                    <button 
                                        type="button" 
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" 
                                        :class="[form.is_active ? 'bg-indigo-600' : 'bg-gray-200']" 
                                        role="switch" 
                                        @click="form.is_active = !form.is_active"
                                    >
                                        <span 
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" 
                                            :class="[
                                                form.is_active 
                                                    ? (isAr ? '-translate-x-5' : 'translate-x-5') 
                                                    : 'translate-x-0'
                                            ]"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between w-full">
                <button
                    @click="close"
                    class="px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors"
                >
                    {{ $t('common.cancel') }}
                </button>
                <div class="flex gap-3">
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="px-8 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 transition-all disabled:opacity-50 disabled:shadow-none"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    part: Object,
    units: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n();
const { confirm } = useConfirm();
const page = usePage();
const { formatQuantity, formatCurrency, toEnglish, sanitizeInput: internalSanitize } = useNumberFormat();

const isAr = computed(() => locale.value === 'ar');

const imageInput = ref(null);
const imagePreview = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    form.remove_image = true;
    imagePreview.value = null;
    if (imageInput.value) imageInput.value.value = '';
};

const sanitizeInput = (event, field) => {
    const sanitized = internalSanitize(event);
    form[field] = sanitized;
};

const unitOptions = computed(() => {
    return props.units.map(unit => ({
        value: unit.id,
        label: isAr.value ? unit.name_ar : (unit.name_en || unit.name_ar)
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.id,
        label: isAr.value ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
});

const isDirty = ref(false);
const initialFormData = ref(null);

const sections = ref({
    category: true, // Open by default
    pricing: true   // Open by default
});

const toggleSection = (key) => {
    sections.value[key] = !sections.value[key];
};

const form = useForm({
    id: null,
    sku: '',
    barcode: '',
    name_ar: '',
    name_en: '',
    unit_id: '',
    category_id: '',
    description: '',
    min_qty: 0,
    reorder_qty: 0,
    default_sale_price: 0,
    min_sale_price: 0,
    is_active: true,
    image: null,
    remove_image: false,
    _method: 'POST',
});

watch(() => props.part, (part) => {
    if (part) {
        form.id = part.id;
        form.sku = part.sku;
        form.barcode = part.barcode;
        form.name_ar = part.name_ar;
        form.name_en = part.name_en;
        form.unit_id = part.unit_id;
        form.category_id = part.category_id;
        form.description = part.description;
        form.min_qty = parseFloat(part.min_qty || 0);
        form.reorder_qty = parseFloat(part.reorder_qty || 0);
        form.default_sale_price = parseFloat(part.default_sale_price || 0);
        form.min_sale_price = parseFloat(part.min_sale_price || 0);
        form.is_active = part.is_active;
        form.image = null;
        form.remove_image = false;
        imagePreview.value = part.image_url;
    } else {
        form.reset();
        form.id = null;
        form.is_active = true;
        form.unit_id = props.units.length > 0 ? props.units[0].id : '';
        form.image = null;
        form.remove_image = false;
        imagePreview.value = null;
    }
}, { immediate: true });

// Handle modal open
watch(() => props.show, (open) => {
    if (open) {
        // Reset sections state if desired, or keep as is
        setTimeout(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        }, 100);
    }
});

// Track form changes
watch(() => form.data(), (newData) => {
    if (initialFormData.value) {
        const currentData = JSON.stringify(newData);
        isDirty.value = currentData !== initialFormData.value;
    }
}, { deep: true });

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    isDirty.value = false;
    initialFormData.value = null;
    imagePreview.value = null;
};

async function handleClose() {
    if (isDirty.value) {
        const confirmed = await confirm({
            title: t('common.unsaved_changes'),
            message: t('common.unsaved_changes_message'),
            confirmText: t('common.yes_close'),
            cancelText: t('common.cancel'),
            type: 'warning',
        });
        if (!confirmed) return;
    }
    close();
}

const submit = () => {
    // For file uploads, we use POST with _method spoofing if it's an update
    if (form.id) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('app.inventory.parts.update', form.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('app.inventory.parts.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
        });
    }
};
</script>
