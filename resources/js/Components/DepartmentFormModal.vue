<template>
    <BaseModal :show="show" @close="$emit('close')" size="md">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-600 to-amber-600 flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </div>
                {{ department ? $t('departments.edit') : $t('departments.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Default Departments -->
            <div
                v-if="!department && filteredDefaultDepartments.length > 0"
                class="space-y-2 pb-4 border-b border-gray-100 dark:border-gray-800"
            >
                <label
                    class="block text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
                >
                    {{ $t('departments.choose_default') }}
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="dept in filteredDefaultDepartments"
                        :key="dept.name_en"
                        type="button"
                        @click="selectDefaultDepartment(dept)"
                        class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/50 hover:bg-orange-50/50 dark:hover:bg-orange-950/10 hover:border-orange-200 dark:hover:border-orange-800/50 transition-all group text-start active:scale-[0.98]"
                    >
                        <span class="text-xl group-hover:scale-110 transition-transform">
                            {{ dept.icon }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <span
                                class="block text-xs font-bold text-gray-800 dark:text-gray-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 truncate"
                            >
                                {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Name Arabic -->
            <FormField
                v-slot="field"
                :label="$t('departments.form.name_ar')"
                :error="form.errors.name_ar"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_ar"
                    dir="rtl"
                    :placeholder="$t('departments.form.name_ar_placeholder')"
                    class="w-full"
                    required
                />
            </FormField>

            <!-- Name English -->
            <FormField
                v-slot="field"
                :label="$t('departments.form.name_en')"
                :error="form.errors.name_en"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_en"
                    dir="ltr"
                    :placeholder="$t('departments.form.name_en_placeholder')"
                    class="w-full"
                    required
                />
            </FormField>

            <!-- Description -->
            <FormField :label="$t('departments.form.description')" :error="form.errors.description">
                <AppTextarea v-model="form.description" rows="3" class="resize-none" />
            </FormField>

            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('departments.form.sort_order') }}
                </label>
                <AppInput type="number" v-model.number="form.sort_order" min="0" />
            </div>

            <!-- Active Toggle -->
            <div>
                <AppToggle
                    v-model="form.is_active"
                    color="warning"
                    :label="$t('departments.form.is_active')"
                />
            </div>
        </form>

        <template #footer>
            <AppButton variant="secondary" @click="$emit('close')">
                {{ $t('common.cancel') }}
            </AppButton>
            <AppButton
                variant="action"
                @click="submitForm"
                :disabled="form.processing"
                :loading="form.processing"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </AppButton>
        </template>
    </BaseModal>
</template>

<script setup>
import { watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';

const { locale } = useI18n();

const DEFAULT_DEPARTMENTS = [
    {
        name_ar: 'الميكانيكا',
        name_en: 'Mechanics',
        description_ar: 'قسم ميكانيكا السيارات وإصلاح الأعطال الميكانيكية للسيارة.',
        description_en: 'Car mechanics department and mechanical repair.',
        icon: '🔧',
    },
    {
        name_ar: 'الكهرباء',
        name_en: 'Electricity',
        description_ar: 'قسم كهرباء السيارات وإصلاح الأعطال الكهربائية.',
        description_en: 'Car electricity department and electrical repair.',
        icon: '⚡',
    },
    {
        name_ar: 'الصيانة الدورية',
        name_en: 'Periodic Maintenance',
        description_ar: 'قسم الصيانة الدورية وتغيير الزيوت والفلاتر.',
        description_en: 'Periodic maintenance department, oil and filter changes.',
        icon: '📅',
    },
    {
        name_ar: 'السمكرة والدهان',
        name_en: 'Body & Paint',
        description_ar: 'قسم سمكرة السيارات ودهان الهيكل الخارجي.',
        description_en: 'Car body work and external paint department.',
        icon: '🎨',
    },
    {
        name_ar: 'تكييف الهواء',
        name_en: 'Air Conditioning',
        description_ar: 'قسم فحص وإصلاح مكيفات السيارات وتعبئة الفريون.',
        description_en: 'Car A/C inspection, repair, and Freon recharge.',
        icon: '❄️',
    },
    {
        name_ar: 'الفحص والبرمجة',
        name_en: 'Diagnostics & Programming',
        description_ar: 'قسم فحص الكمبيوتر وبرمجة الأنظمة الإلكترونية للسيارة.',
        description_en: 'Computer diagnostics and programming of car electronic systems.',
        icon: '💻',
    },
    {
        name_ar: 'الغسيل والتلميع',
        name_en: 'Washing & Polishing',
        description_ar: 'قسم غسيل السيارات وتلميع وهيكل وتنظيف المقاعد.',
        description_en: 'Car washing, polishing, body detailing, and seat cleaning.',
        icon: '🧼',
    },
    {
        name_ar: 'الإطارات والوزن',
        name_en: 'Tires & Alignment',
        description_ar: 'قسم ميزان السيارات ووزن الأذرعة وتغيير وترصيص الإطارات.',
        description_en: 'Wheel alignment, tire changing, and wheel balancing.',
        icon: '🚗',
    },
];

function selectDefaultDepartment(dept) {
    form.name_ar = dept.name_ar;
    form.name_en = dept.name_en;
    form.description = locale.value === 'ar' ? dept.description_ar : dept.description_en;
}
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    department: {
        type: Object,
        default: null,
    },
    existingDepartments: {
        type: Array,
        default: () => [],
    },
});

const filteredDefaultDepartments = computed(() => {
    const existingNames = new Set(
        props.existingDepartments
            .flatMap((d) => [d.name_ar?.trim().toLowerCase(), d.name_en?.trim().toLowerCase()])
            .filter(Boolean)
    );
    return DEFAULT_DEPARTMENTS.filter(
        (d) =>
            !existingNames.has(d.name_ar.trim().toLowerCase()) &&
            !existingNames.has(d.name_en.trim().toLowerCase())
    );
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name_ar: '',
    name_en: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

// Initialize form when department prop changes (for edit mode)
watch(
    () => props.department,
    (newDept) => {
        if (newDept) {
            form.name_ar = newDept.name_ar || '';
            form.name_en = newDept.name_en || '';
            form.description = newDept.description || '';
            form.sort_order = newDept.sort_order || 0;
            form.is_active = newDept.is_active ?? true;
        }
    },
    { immediate: true }
);

// Reset form when modal opens for create
watch(
    () => props.show,
    (open) => {
        if (open && !props.department) {
            form.reset();
            form.is_active = true;
        }
    }
);

function submitForm() {
    const url = props.department ? `/app/departments/${props.department.id}` : '/app/departments';

    const method = props.department ? 'put' : 'post';
    const isEdit = !!props.department;

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
    });
}
</script>
