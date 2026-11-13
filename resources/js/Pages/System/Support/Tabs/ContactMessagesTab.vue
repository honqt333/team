<template>
    <div class="space-y-6">
        <!-- Search -->
        <div class="w-full max-w-md">
            <AppInput
                v-model="search"
                type="search"
                :placeholder="t('system.contact_messages.search_placeholder')"
            />
        </div>

        <!-- Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="overflow-x-auto">
                <DataTable
                    structured
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ t('system.contact_messages.col.status') }}
                            </th>
                            <th
                                class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ t('system.contact_messages.col.sender') }}
                            </th>
                            <th
                                class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ t('system.contact_messages.col.subject') }}
                            </th>
                            <th
                                class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ t('system.contact_messages.col.date') }}
                            </th>
                            <th
                                class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ t('system.backups.col.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="message in messages?.data || []"
                            :key="message.id"
                            :class="{ 'bg-blue-50 dark:bg-blue-900/10': !message.is_read }"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer transition-colors"
                            @click="openMessage(message)"
                        >
                            <td class="px-6 py-4 align-middle text-center">
                                <span
                                    v-if="!message.is_read"
                                    class="flex h-2 w-2 rounded-full bg-blue-600 mx-auto"
                                ></span>
                                <span
                                    v-else
                                    class="flex h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600 mx-auto"
                                ></span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-900 dark:text-white align-middle"
                            >
                                <div class="font-medium">{{ message.name }}</div>
                                <div class="text-xs text-gray-500">{{ message.email }}</div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-middle"
                            >
                                {{ message.subject || t('system.contact_messages.no_subject') }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-middle"
                            >
                                {{ new Date(message.created_at).toLocaleDateString('ar-EG') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium align-middle text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a
                                        :href="`mailto:${message.email}`"
                                        @click.stop
                                        class="text-action-primary hover:text-selection-text"
                                        :title="t('system.contact_messages.send_email')"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </a>
                                    <a
                                        v-if="message.phone"
                                        :href="`tel:${message.phone}`"
                                        @click.stop
                                        class="text-green-600 hover:text-green-900"
                                        :title="t('system.contact_messages.call')"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                    </a>
                                    <button
                                        @click.stop="deleteMessage(message.id)"
                                        class="text-red-600 hover:text-red-900"
                                        :title="t('system.actions.delete')"
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!messages?.data || messages.data.length === 0">
                            <td
                                colspan="5"
                                class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ t('system.contact_messages.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>

            <!-- Pagination -->
            <AppPagination
                :links="messages?.links || []"
                :from="messages?.from"
                :to="messages?.to"
                :total="messages?.total"
            />
        </div>
    </div>

    <!-- Message Detail Modal -->
    <BaseModal
        :show="!!selectedMessage"
        :title="t('system.contact_messages.detail_title')"
        size="md"
        @close="closeMessage"
    >
        <div v-if="selectedMessage" class="space-y-4">
            <div>
                <label class="text-xs text-gray-500 block">
                    {{ t('system.contact_messages.col.sender') }}
                </label>
                <div class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ selectedMessage.name }}
                </div>
                <div class="text-sm text-action-primary">
                    {{ selectedMessage.email }}
                </div>
                <div v-if="selectedMessage.phone" class="text-sm text-gray-500 font-mono">
                    {{ selectedMessage.phone }}
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 block">
                    {{ t('system.contact_messages.col.subject') }}
                </label>
                <div class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ selectedMessage.subject || t('system.contact_messages.no_subject') }}
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 block">
                    {{ t('system.contact_messages.body_label') }}
                </label>
                <div
                    class="mt-1 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed"
                >
                    {{ selectedMessage.message }}
                </div>
            </div>
            <div class="text-xs text-gray-400">
                {{
                    t('system.contact_messages.sent_at', {
                        date: new Date(selectedMessage.created_at).toLocaleString('ar-EG'),
                    })
                }}
            </div>
        </div>
        <template v-if="selectedMessage" #footer>
            <div class="flex justify-between items-center gap-4 w-full">
                <div class="flex gap-2">
                    <a
                        :href="`mailto:${selectedMessage.email}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-action-primary text-white rounded-lg text-sm font-bold hover:bg-action-primary-hover transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                        {{ t('system.contact_messages.reply_email') }}
                    </a>
                    <a
                        v-if="selectedMessage.phone"
                        :href="`tel:${selectedMessage.phone}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            />
                        </svg>
                        {{ t('system.contact_messages.call_action') }}
                    </a>
                </div>
                <button
                    @click="deleteMessage(selectedMessage.id)"
                    class="text-sm font-medium text-red-600 hover:text-red-700"
                >
                    {{ t('system.contact_messages.delete_message') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppInput from '@/Components/App/AppInput.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { debounce } from 'lodash-es';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();
const { confirm } = useConfirm();

const props = defineProps({
    messages: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedMessage = ref(null);

watch(
    search,
    debounce((value) => {
        router.get(
            '/system/support',
            { tab: 'contact_messages', search: value },
            { preserveState: true, replace: true }
        );
    }, 300)
);

const markAsRead = (id) => {
    router.put(
        route('system.contact_messages.read', id),
        {},
        {
            onSuccess: () => {
                if (selectedMessage.value && selectedMessage.value.id === id) {
                    selectedMessage.value.is_read = true;
                }
            },
        }
    );
};

const deleteMessage = async (id) => {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.contact_messages.confirm_delete'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.delete(route('system.contact_messages.destroy', id), {
            onSuccess: () => (selectedMessage.value = null),
        });
    }
};

const openMessage = (message) => {
    selectedMessage.value = message;
    if (!message.is_read) {
        markAsRead(message.id);
    }
};

const closeMessage = () => {
    selectedMessage.value = null;
};
</script>
