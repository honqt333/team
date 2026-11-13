<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center"
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
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </div>
                {{ $t('work_orders.show.tabs.attachments') }}
            </div>
        </template>

        <div class="space-y-4">
            <AppFileUpload
                v-model="selectedFiles"
                multiple
                accept=".pdf,image/png,image/jpeg"
                :max-size-mb="1"
                :upload-button-text="$t('work_orders.photos.browse')"
                :hint="`PDF, PNG, JPG (${$t('common.max')} 1MB)`"
                @invalid="
                    (payload) => toast.error(payload?.message || t('messages.invalid_file_type'))
                "
            />
        </div>

        <template #footer>
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
                :disabled="form.processing || selectedFiles.length === 0"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
            >
                <svg
                    v-if="form.processing"
                    class="animate-spin h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
});

const emit = defineEmits(['close', 'saved']);
const toast = useToast();
const { t } = useI18n();

const selectedFiles = ref([]);

const form = useForm({
    attachments: [],
});

function submit() {
    form.attachments = selectedFiles.value;
    form.post(route('work-orders.attachments.store', props.workOrder.id), {
        onSuccess: () => {
            selectedFiles.value = [];
            emit('saved');
            emit('close');
        },
        forceFormData: true,
    });
}
</script>
