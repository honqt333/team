<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            {{ item ? $t('common.edit') : $t('common.add') }}
            {{ $t('hr.settings.regulations.title') }}
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Category -->
            <div>
                <SearchableSelect
                    v-model="form.category"
                    :options="categoryOptions"
                    option-label="label"
                    option-value="value"
                    :label="$t('hr.settings.regulations.category')"
                    required
                />
            </div>

            <!-- Title AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.title') }} {{ $t('hr.arabic_suffix') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.title_ar"
                    type="text"
                    required
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring"
                />
            </div>

            <!-- Title EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.title') }} (English)
                </label>
                <input
                    v-model="form.title_en"
                    type="text"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring"
                />
            </div>

            <!-- Content AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.content') }} {{ $t('hr.arabic_suffix') }}
                    <span class="text-red-500">*</span>
                </label>
                <textarea
                    v-model="form.content_ar"
                    required
                    rows="4"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring"
                ></textarea>
            </div>

            <!-- Content EN -->
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">
                    {{ $t('common.content') }} (English)
                </label>
                <textarea
                    v-model="form.content_en"
                    rows="4"
                    class="w-full px-4 py-2.5 text-sm border border-border-strong rounded-xl bg-surface text-text-primary focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                ></textarea>
            </div>

            <!-- Is Active -->
            <div>
                <AppToggle v-model="form.is_active" :label="$t('common.active')" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    show: Boolean,
    item: Object,
});

const emit = defineEmits(['close', 'saved']);

const categoryOptions = computed(() => [
    { value: 'work_hours', label: t('hr.settings.regulations.categories.work_hours') },
    { value: 'discipline', label: t('hr.settings.regulations.categories.discipline') },
    { value: 'benefits', label: t('hr.settings.regulations.categories.benefits') },
    { value: 'safety', label: t('hr.settings.regulations.categories.safety') },
    { value: 'other', label: t('hr.settings.regulations.categories.other') },
]);

const form = useForm({
    category: 'other',
    title_ar: '',
    title_en: '',
    content_ar: '',
    content_en: '',
    is_active: true,
});

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            if (props.item) {
                form.category = props.item.category || 'other';
                form.title_ar = props.item.title_ar || '';
                form.title_en = props.item.title_en || '';
                form.content_ar = props.item.content_ar || '';
                form.content_en = props.item.content_en || '';
                form.is_active = props.item.is_active ?? true;
            } else {
                form.reset();
            }
        }
    }
);

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
            emit('saved');
        },
    };

    if (props.item) {
        form.put(route('app.hr.settings.regulations.update', props.item.id), options);
    } else {
        form.post(route('app.hr.settings.regulations.store'), options);
    }
}
</script>
