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
            <AppButton type="button" variant="secondary" @click="$emit('close')">
                {{ $t('common.cancel') }}
            </AppButton>
            <AppButton
                type="button"
                variant="primary"
                @click="submit"
                :loading="form.processing"
                :disabled="form.processing || selectedFiles.length === 0"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </AppButton>
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
import AppButton from '@/Components/App/AppButton.vue';

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
