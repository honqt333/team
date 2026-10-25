<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">
            {{ $t('system.dunning.preferences_title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            {{ $t('system.dunning.preferences_subtitle') }}
        </p>

        <div class="mt-4 space-y-4">
            <label class="flex items-start gap-3">
                <input
                    v-model="form.dunning_opted_out"
                    type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-amber-600"
                />
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $t('system.dunning.opted_out_label') }}
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('system.dunning.opted_out_helper') }}
                    </p>
                </div>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white">
                    {{ $t('system.dunning.language_label') }}
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-2">
                    {{ $t('system.dunning.language_helper') }}
                </p>
                <SearchableSelect
                    v-model="form.dunning_language"
                    :options="languageOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="اختر اللغة..."
                />
            </div>

            <div class="flex justify-end">
                <button
                    @click="save"
                    :disabled="processing"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50"
                >
                    {{ processing ? '...' : $t('common.save') || 'Save' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
});

const languageOptions = [
    { value: 'ar', label: 'العربية' },
    { value: 'en', label: 'English' },
];

const form = ref({
    dunning_opted_out: !props.tenant.dunning_opted_out,
    // dunning_language: null = use the tenant's app locale
    dunning_language: props.tenant.dunning_language || '',
});

const processing = ref(false);

async function save() {
    processing.value = true;
    try {
        await router.post('/app/settings/billing/dunning-preferences', {
            dunning_opted_out: !form.value.dunning_opted_out,
            dunning_language: form.value.dunning_language || null,
        });
    } finally {
        processing.value = false;
    }
}
</script>
