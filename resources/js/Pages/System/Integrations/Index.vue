<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.integrations.title')"
                :subtitle="$t('system.integrations.subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Sections: SMS, WhatsApp, Email -->
            <div
                v-for="section in sections"
                :key="section.key"
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <span class="text-2xl">{{ section.icon }}</span>
                        {{ $t(`system.integrations.sections.${section.key}`) }}
                    </h2>
                    <button
                        @click="openAddModal(section.key)"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                    >
                        {{ $t('system.integrations.add_provider') }}
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="item in integrations[section.key]"
                        :key="item.id"
                        :class="['border rounded-lg p-4 transition-colors', section.hoverClass]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ item.name_ar }}
                            </span>
                            <span
                                :class="
                                    item.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                "
                                class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{
                                    $t(
                                        item.is_active
                                            ? 'system.integrations.status.enabled'
                                            : 'system.integrations.status.disabled'
                                    )
                                }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ item.name }}</p>
                        <div class="flex items-center gap-3">
                            <a
                                :href="`/system/integrations/${item.id}`"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                {{ $t('system.integrations.settings') }}
                            </a>
                            <span
                                v-if="item.is_default"
                                class="text-xs text-amber-600 dark:text-amber-400 font-medium"
                            >
                                ⭐ {{ $t('system.integrations.default') }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="!integrations[section.key]?.length"
                        class="col-span-full text-center text-sm text-gray-500 dark:text-gray-400 py-8"
                    >
                        {{ $t('system.integrations.no_providers') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Provider Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{
                                $t('system.integrations.add_provider_title', {
                                    type: $t(`system.integrations.sections.${selectedType}`),
                                })
                            }}
                        </h3>
                        <form @submit.prevent="addProvider" class="space-y-4">
                            <div class="grid grid-cols-1 gap-3">
                                <SelectableCard
                                    type="radio"
                                    name="form.provider"
                                    v-model="form.provider"
                                    :value="key"
                                    v-for="(info, key) in availableProviders"
                                    :key="key"
                                    :class="[
                                        'border rounded-lg p-4 cursor-pointer transition-colors',
                                        form.provider === key
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ info.icon }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ info.name_ar }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ info.name }}
                                            </p>
                                        </div>
                                    </div>
                                </SelectableCard>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="!form.provider || form.processing"
                                    class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                                >
                                    {{ $t('system.integrations.add_and_enable') }}
                                </button>
                                <button
                                    type="button"
                                    @click="showAddModal = false"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    {{ $t('system.actions.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const { t } = useI18n();

const props = defineProps({
    integrations: Object,
    providers: Object,
});

// Section config (extracted to a list so the template doesn't repeat
// the same card structure for SMS/WhatsApp/Email).
const sections = [
    {
        key: 'sms',
        icon: '📱',
        hoverClass: 'hover:border-indigo-300',
    },
    {
        key: 'whatsapp',
        icon: '💬',
        hoverClass: 'hover:border-emerald-300',
    },
    {
        key: 'email',
        icon: '📧',
        hoverClass: 'hover:border-blue-300',
    },
];

const showAddModal = ref(false);
const selectedType = ref('sms');

const form = useForm({
    type: '',
    provider: '',
});

const availableProviders = computed(() => props.providers?.[selectedType.value] || {});

const openAddModal = (type) => {
    selectedType.value = type;
    form.type = type;
    form.provider = '';
    showAddModal.value = true;
};

const addProvider = () => {
    form.post('/system/integrations', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
