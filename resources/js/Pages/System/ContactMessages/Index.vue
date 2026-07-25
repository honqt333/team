<script setup>
import SystemLayout from '@/Layouts/SystemLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { debounce } from 'lodash-es';

const { t } = useI18n();

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
            route('system.contact_messages.index'),
            { search: value },
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

const deleteMessage = (id) => {
    if (confirm(t('system.contact_messages.confirm_delete'))) {
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

<template>
    <Head :title="t('system.contact_messages.title')" />

    <SystemLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ t('system.contact_messages.title') }}
                    </h1>
                </div>
            </div>

            <!-- Search -->
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('system.contact_messages.search_placeholder')"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
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
                                v-for="message in messages.data"
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
                                            class="text-indigo-600 hover:text-indigo-900"
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
                            <tr v-if="messages.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                                >
                                    {{ t('system.contact_messages.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="messages.links?.length > 3"
                    class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center"
                >
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in messages.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-safe-html="link.label"
                            class="px-3 py-1 rounded text-sm transition-all"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-50 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Detail Modal -->
        <div
            v-if="selectedMessage"
            class="fixed inset-0 z-50 overflow-y-auto"
            @click="closeMessage"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full"
                    @click.stop
                >
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"
                    >
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ t('system.contact_messages.detail_title') }}
                        </h3>
                        <button @click="closeMessage" class="text-gray-400 hover:text-gray-500">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-500 block">
                                    {{ t('system.contact_messages.col.sender') }}
                                </label>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ selectedMessage.name }}
                                </div>
                                <div class="text-sm text-indigo-600 dark:text-indigo-400">
                                    {{ selectedMessage.email }}
                                </div>
                                <div
                                    v-if="selectedMessage.phone"
                                    class="text-sm text-gray-500 font-mono"
                                >
                                    {{ selectedMessage.phone }}
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block">
                                    {{ t('system.contact_messages.col.subject') }}
                                </label>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{
                                        selectedMessage.subject ||
                                        t('system.contact_messages.no_subject')
                                    }}
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
                                        date: new Date(selectedMessage.created_at).toLocaleString(
                                            'ar-EG'
                                        ),
                                    })
                                }}
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between items-center gap-4"
                    >
                        <div class="flex gap-2">
                            <a
                                :href="`mailto:${selectedMessage.email}`"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all"
                            >
                                <svg
                                    class="w-4 h-4"
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
                                {{ t('system.contact_messages.reply_email') }}
                            </a>
                            <a
                                v-if="selectedMessage.phone"
                                :href="`tel:${selectedMessage.phone}`"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition-all"
                            >
                                <svg
                                    class="w-4 h-4"
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
                </div>
            </div>
        </div>
    </SystemLayout>
</template>
