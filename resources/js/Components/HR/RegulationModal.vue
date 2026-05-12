<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            {{ item ? $t('common.edit') : $t('common.add') }} {{ $t('hr.settings.regulations.title') }}
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('hr.settings.regulations.category') }} <span class="text-red-500">*</span>
                </label>
                <SearchableSelect
                    v-model="form.category"
                    :options="categoryOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="{{ $t('common.choose') }}"
                    required
                />
            </div>

            <!-- Title AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.title') }} (العربية) <span class="text-red-500">*</span>
                </label>
                <input v-model="form.title_ar" type="text" required
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500" />
            </div>

            <!-- Title EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.title') }} (English)
                </label>
                <input v-model="form.title_en" type="text"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500" />
            </div>

            <!-- Content AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.content') }} (العربية) <span class="text-red-500">*</span>
                </label>
                <textarea v-model="form.content_ar" required rows="4"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"></textarea>
            </div>

            <!-- Content EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.content') }} (English)
                </label>
                <textarea v-model="form.content_en" rows="4"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"></textarea>
            </div>

            <!-- Is Active -->
            <div class="flex items-center gap-2">
                <input v-model="form.is_active" type="checkbox" id="reg_active" class="rounded text-violet-600" />
                <label for="reg_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('common.active') }}
                </label>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button type="button" @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    {{ $t('common.cancel') }}
                </button>
                <button @click="submit" :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 disabled:opacity-50">
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
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    show: Boolean,
    item: Object,
});

const emit = defineEmits(['close', 'saved']);

const categories = {
    work_hours: t('hr.settings.regulations.categories.work_hours'),
    discipline: t('hr.settings.regulations.categories.discipline'),
    benefits: t('hr.settings.regulations.categories.benefits'),
    safety: t('hr.settings.regulations.categories.safety'),
    other: t('hr.settings.regulations.categories.other'),
};

const form = useForm({
    category: 'other',
    title_ar: '',
    title_en: '',
    content_ar: '',
    content_en: '',
    is_active: true,
});

watch(() => props.show, (newVal) => {
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
});

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
