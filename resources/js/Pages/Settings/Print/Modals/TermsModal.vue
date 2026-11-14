<template>
    <BaseModal :show="!!show" size="md" @close="$emit('close')">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-selection-bg flex items-center justify-center">
                    <svg
                        class="w-6 h-6 text-action-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        />
                    </svg>
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('print_settings.official_signatures') }}
                    </div>
                    <p class="text-xs font-normal text-gray-400">
                        {{ $t('print_settings.' + docKey) }}
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-3">
            <!-- Column headers -->
            <div
                class="grid grid-cols-[1.5rem_1fr_1fr_2rem_2rem] gap-2 px-1 text-[9px] font-black text-gray-400 uppercase tracking-widest"
            >
                <span></span>
                <span>{{ $t('print_settings.name_arabic') }}</span>
                <span>Name (English)</span>
                <span class="text-center">{{ $t('print_settings.visible') }}</span>
                <span></span>
            </div>

            <!-- Signatures List -->
            <div
                v-for="(sig, index) in localSignatures"
                :key="index"
                class="grid grid-cols-[1.5rem_1fr_1fr_2rem_2rem] items-center gap-2 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/30 group hover:border-action-primary/25 transition-all"
            >
                <span
                    class="w-6 h-6 rounded-lg bg-selection-bg text-action-primary text-[10px] font-black flex items-center justify-center"
                >
                    {{ index + 1 }}
                </span>

                <AppInput
                    v-model="sig.name_ar"
                    type="text"
                    size="sm"
                    placeholder="الاسم بالعربي"
                    class="text-end"
                />

                <AppInput
                    v-model="sig.name_en"
                    type="text"
                    size="sm"
                    placeholder="English name"
                    dir="ltr"
                />

                <AppToggle v-model="sig.show" size="sm" :title="sig.show ? 'مرئي' : 'مخفي'" />

                <button
                    @click="deleteSignature(index)"
                    class="w-6 h-6 rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-600 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100"
                    title="حذف"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </button>
            </div>

            <!-- Empty state -->
            <div
                v-if="localSignatures.length === 0"
                class="py-8 flex flex-col items-center gap-2 text-gray-400"
            >
                <svg
                    class="w-10 h-10 opacity-20"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                    />
                </svg>
                <span class="text-sm">{{ $t('print_settings.no_signatures') }}</span>
            </div>

            <!-- Add Button -->
            <button
                @click="addSignature"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 border-2 border-dashed border-action-primary/25 rounded-xl text-action-primary text-xs font-bold hover:bg-selection-bg hover:border-action-primary transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                {{ $t('print_settings.add_signature_new') }}
            </button>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton type="button" variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton type="button" variant="primary" @click="handleSave">
                    {{ $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import { ref, watch } from 'vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';

const DEFAULT_SIGNATURES = [
    { name_ar: 'توقيع المدير', name_en: 'Manager Signature', order: 1, show: true },
    { name_ar: 'توقيع العميل', name_en: 'Customer Signature', order: 2, show: true },
];

const props = defineProps({
    show: Boolean,
    document: Object,
    docKey: String,
});

const emit = defineEmits(['close', 'save']);

function initSignatures(doc) {
    const sigs = doc?.signatures ?? [];
    if (sigs.length === 0) {
        return DEFAULT_SIGNATURES.map((s) => ({ ...s }));
    }
    return sigs.map((s) => ({ ...s }));
}

const localSignatures = ref(initSignatures(props.document));

watch(
    () => props.document,
    (newVal) => {
        localSignatures.value = initSignatures(newVal);
    },
    { deep: true }
);

function addSignature() {
    localSignatures.value.push({
        name_ar: '',
        name_en: '',
        order: localSignatures.value.length + 1,
        show: true,
    });
}

function deleteSignature(index) {
    localSignatures.value.splice(index, 1);
    localSignatures.value.forEach((s, i) => {
        s.order = i + 1;
    });
}

function handleSave() {
    emit('save', {
        ...props.document,
        signatures: [...localSignatures.value],
    });
    emit('close');
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
