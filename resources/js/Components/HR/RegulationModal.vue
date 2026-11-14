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
                <AppInput v-model="form.title_ar" type="text" required />
            </div>

            <!-- Title EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.title') }} (English)
                </label>
                <AppInput v-model="form.title_en" type="text" />
            </div>

            <!-- Content AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.content') }} {{ $t('hr.arabic_suffix') }}
                    <span class="text-red-500">*</span>
                </label>
                <AppTextarea v-model="form.content_ar" required rows="4" />
            </div>

            <!-- Content EN -->
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">
                    {{ $t('common.content') }} (English)
                </label>
                <AppTextarea v-model="form.content_en" rows="4" />
            </div>

            <!-- Is Active -->
            <div>
                <AppToggle v-model="form.is_active" :label="$t('common.active')" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <AppButton type="button" variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="button"
                    variant="primary"
                    @click="submit"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </AppButton>
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
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
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
