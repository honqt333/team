<template>
    <BaseModal :show="!!show" size="md" z-index-class="z-[70]" @close="$emit('close')">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center"
                >
                    <svg
                        class="w-6 h-6 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ term.id ? $t('common.edit') : $t('common.add') }}
                </span>
            </div>
        </template>

        <!-- Form Body -->
        <div class="space-y-6">
            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('print_settings.name_ar') }}
                </label>
                <textarea
                    v-model="localTerm.text_ar"
                    rows="4"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-all text-end"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('print_settings.name_en') }}
                </label>
                <textarea
                    v-model="localTerm.text_en"
                    rows="4"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-all"
                ></textarea>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('print_settings.order_by') }}
                </label>
                <input
                    v-model="localTerm.order"
                    type="number"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-all"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <button
                    @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    @click="handleSave"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                >
                    {{ $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    term: {
        type: Object,
        default: () => ({
            text_ar: '',
            text_en: '',
            order: 1,
        }),
    },
});

const emit = defineEmits(['close', 'save']);

const localTerm = ref({ ...props.term });

function handleSave() {
    emit('save', { ...localTerm.value });
    emit('close');
}
</script>
