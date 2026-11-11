<template>
    <SystemLayout>
        <Head :title="$t('system.announcements.create')" />

        <div class="max-w-4xl mx-auto space-y-6">
            <PageHeader :title="$t('system.announcements.create_title')">
                <template #back>
                    <Link
                        :href="route('system.announcements.index')"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="$t('common.back')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700"
            >
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.announcements.title_required') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.title"
                            required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.announcements.content_required') }}
                        </label>
                        <textarea
                            v-model="form.content"
                            rows="4"
                            required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <SearchableSelect
                                v-model="form.type"
                                :options="typeOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.announcements.col.type')"
                            />
                        </div>
                        <div>
                            <SearchableSelect
                                v-model="form.target"
                                :options="targetOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.announcements.col.audience')"
                            />
                        </div>
                    </div>

                    <!-- Specific Tenants -->
                    <div v-if="form.target === 'specific'">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.announcements.select_tenants') }}
                        </label>
                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1"
                        >
                            <label
                                v-for="tenant in tenants"
                                :key="tenant.id"
                                class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer"
                            >
                                <AppToggle :value="tenant.id" v-model="form.target_tenant_ids" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ tenant.trade_name || tenant.name }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('system.announcements.channels') }}
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <AppToggle value="in_app" v-model="form.channels" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $t('system.announcements.in_app') }}
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <AppToggle value="email" v-model="form.channels" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $t('center_settings.contact.email') }}
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <AppToggle value="sms" v-model="form.channels" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">SMS</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.announcements.expiry_date') }}
                        </label>
                        <CustomDatePicker v-model="form.expires_at" dir="ltr" />
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-medium disabled:opacity-50"
                        >
                            {{ $t('system.announcements.create_draft') }}
                        </button>
                        <Link
                            :href="route('system.announcements.index')"
                            class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                        >
                            {{ $t('common.cancel') }}
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    tenants: Array,
});

const typeOptions = computed(() => [
    { value: 'info', label: t('system.announcements.type.info') },
    { value: 'warning', label: t('system.announcements.type.warning') },
    { value: 'important', label: t('system.announcements.type.important') },
    { value: 'maintenance', label: t('system.announcements.type.maintenance') },
]);

const targetOptions = computed(() => [
    { value: 'all', label: t('system.backups.filter_all_tenants') },
    { value: 'active', label: t('system.announcements.active_only') },
    { value: 'trial', label: t('system.announcements.trial_only') },
    { value: 'expired', label: t('system.announcements.subscriptions_ended') },
    { value: 'specific', label: t('system.announcements.specific_tenants') },
]);

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    target: 'all',
    target_tenant_ids: [],
    channels: ['in_app'],
    expires_at: '',
});

const submit = () => form.post('/system/announcements');
</script>
