<script setup>
import SystemLayout from '@/Layouts/SystemLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    messages: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const selectedMessage = ref(null);

watch(search, debounce((value) => {
    router.get(route('system.contact_messages.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

const markAsRead = (id) => {
    router.put(route('system.contact_messages.read', id), {}, {
        onSuccess: () => {
            if (selectedMessage.value && selectedMessage.value.id === id) {
                selectedMessage.value.is_read = true;
            }
        }
    });
};

const deleteMessage = (id) => {
    if (confirm('هل أنت متأكد من حذف هذه الرسالة؟')) {
        router.delete(route('system.contact_messages.destroy', id), {
            onSuccess: () => selectedMessage.value = null
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
    <Head title="رسائل الزوار" />

    <SystemLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                رسائل الزوار
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <!-- Search -->
                        <div class="mb-6 flex justify-between items-center">
                            <div class="relative w-full max-w-md">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="بحث عن اسم، بريد إلكتروني، أو موضوع..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-150 ease-in-out"
                                />
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المرسل</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الموضوع</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">التاريخ</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="message in messages.data" :key="message.id" 
                                        :class="{'bg-blue-50 dark:bg-blue-900/10': !message.is_read}"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                        @click="openMessage(message)"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="!message.is_read" class="flex h-2 w-2 rounded-full bg-blue-600"></span>
                                            <span v-else class="flex h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <div class="font-medium">{{ message.name }}</div>
                                            <div class="text-gray-500 text-xs">{{ message.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ message.subject || 'بدون موضوع' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ new Date(message.created_at).toLocaleDateString('ar-EG') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-3 rtl:space-x-reverse">
                                                <a :href="`mailto:${message.email}`" @click.stop class="text-indigo-600 hover:text-indigo-900" title="إرسال بريد">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                </a>
                                                <a v-if="message.phone" :href="`tel:${message.phone}`" @click.stop class="text-green-600 hover:text-green-900" title="اتصال هاتفي">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                </a>
                                                <button @click.stop="deleteMessage(message.id)" class="text-red-600 hover:text-red-900" title="حذف">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="messages.data.length === 0">
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">لا توجد رسائل حالياً</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 flex justify-center">
                            <div class="flex space-x-1 rtl:space-x-reverse">
                                <Link v-for="(link, i) in messages.links" :key="i"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    class="px-4 py-2 border rounded-md text-sm transition-all"
                                    :class="[
                                        link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Detail Modal -->
        <div v-if="selectedMessage" class="fixed inset-0 z-50 overflow-y-auto" @click="closeMessage">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full" @click.stop>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">تفاصيل الرسالة</h3>
                        <button @click="closeMessage" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-500 block">المرسل</label>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedMessage.name }}</div>
                                <div class="text-sm text-indigo-600 dark:text-indigo-400">{{ selectedMessage.email }}</div>
                                <div v-if="selectedMessage.phone" class="text-sm text-gray-500 font-mono">{{ selectedMessage.phone }}</div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block">الموضوع</label>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedMessage.subject || 'بدون موضوع' }}</div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block">الرسالة</label>
                                <div class="mt-1 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
                                    {{ selectedMessage.message }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-400">تاريخ الإرسال: {{ new Date(selectedMessage.created_at).toLocaleString('ar-EG') }}</div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between items-center gap-4">
                        <div class="flex gap-2">
                            <a :href="`mailto:${selectedMessage.email}`" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                رد بالبريد
                            </a>
                            <a v-if="selectedMessage.phone" :href="`tel:${selectedMessage.phone}`" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                اتصال هاتفي
                            </a>
                        </div>
                        <button @click="deleteMessage(selectedMessage.id)" class="text-sm font-medium text-red-600 hover:text-red-700">حذف الرسالة</button>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>
