<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SignaturePad from '@/Components/Common/SignaturePad.vue';

const props = defineProps({
    workOrder: {
        type: Object,
        required: true
    },
    isReadOnly: {
        type: Boolean,
        default: false
    }
});

const { t } = useI18n();
const { success } = useToast();

const showSignatureModal = ref(false);
const signatureType = ref('reception');
const signatureData = ref(null);
const isSavingSignature = ref(false);

function formatDateTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

function openSignatureModal(type) {
    signatureType.value = type;
    signatureData.value = null;
    showSignatureModal.value = true;
}

function saveSignature() {
    if (!signatureData.value) return;

    isSavingSignature.value = true;
    router.post(route('work-orders.signatures.store', props.workOrder.id), {
        type: signatureType.value,
        signature: signatureData.value
    }, {
        onSuccess: () => {
            showSignatureModal.value = false;
            success(t('common.saved_success'));
        },
        onFinish: () => {
            isSavingSignature.value = false;
        }
    });
}
</script>

<template>
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Reception Signature -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-gray-900 dark:text-white">{{
                        $t('work_orders.show.signatures.reception') }}</h4>
                    <span v-if="workOrder.reception_signed_at" class="text-xs text-green-500 font-bold">
                        {{ $t('work_orders.show.signatures.signed_at', {
                            date: formatDateTime(workOrder.reception_signed_at)
                        }) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">{{ $t('work_orders.show.signatures.reception_hint') }}</p>

                <div v-if="workOrder.reception_signature" class="relative group">
                    <img :src="'/storage/' + workOrder.reception_signature"
                        class="w-full h-48 object-contain bg-white rounded-xl border border-gray-200 dark:border-gray-700">
                    <button v-if="!isReadOnly" @click="openSignatureModal('reception')"
                        class="absolute inset-0 bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center font-bold">
                        {{ $t('work_orders.show.signatures.sign_now') }}
                    </button>
                </div>
                <div v-else-if="!isReadOnly"
                    class="h-48 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl flex items-center justify-center bg-gray-50 dark:bg-gray-900/50">
                    <PrimaryButton @click="openSignatureModal('reception')">
                        {{ $t('work_orders.show.signatures.sign_now') }}
                    </PrimaryButton>
                </div>
                <div v-else
                    class="h-48 border border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-center text-gray-400 italic">
                    {{ $t('common.no_data') }}
                </div>
            </div>

            <!-- Delivery Signature -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-gray-900 dark:text-white">{{
                        $t('work_orders.show.signatures.delivery') }}</h4>
                    <span v-if="workOrder.delivery_signed_at" class="text-xs text-green-500 font-bold">
                        {{ $t('work_orders.show.signatures.signed_at', {
                            date: formatDateTime(workOrder.delivery_signed_at)
                        }) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">{{ $t('work_orders.show.signatures.delivery_hint') }}</p>

                <div v-if="workOrder.delivery_signature" class="relative group">
                    <img :src="'/storage/' + workOrder.delivery_signature"
                        class="w-full h-48 object-contain bg-white rounded-xl border border-gray-200 dark:border-gray-700">
                    <button v-if="!isReadOnly || workOrder.status === 'done'"
                        @click="openSignatureModal('delivery')"
                        class="absolute inset-0 bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center font-bold">
                        {{ $t('work_orders.show.signatures.sign_now') }}
                    </button>
                </div>
                <div v-else-if="!isReadOnly || workOrder.status === 'done'"
                    class="h-48 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl flex items-center justify-center bg-gray-50 dark:bg-gray-900/50">
                    <PrimaryButton @click="openSignatureModal('delivery')">
                        {{ $t('work_orders.show.signatures.sign_now') }}
                    </PrimaryButton>
                </div>
                <div v-else
                    class="h-48 border border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-center text-gray-400 italic">
                    {{ $t('common.no_data') }}
                </div>
            </div>
        </div>

        <!-- Signature Modal -->
        <Modal :show="showSignatureModal" @close="showSignatureModal = false" max-width="lg">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    {{ signatureType === 'reception' ? $t('work_orders.show.signatures.reception') :
                        $t('work_orders.show.signatures.delivery') }}
                </h2>

                <SignaturePad v-if="showSignatureModal" v-model="signatureData" />

                <div class="mt-8 flex justify-end gap-3">
                    <SecondaryButton @click="showSignatureModal = false">{{ $t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton @click="saveSignature" :disabled="!signatureData" :loading="isSavingSignature">
                        {{ $t('work_orders.show.signatures.save') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
