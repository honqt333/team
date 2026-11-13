<template>
    <SystemLayout>
        <Head :title="`${$t('system.communication.edit_template')}: ${template.name}`" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="`${$t('system.communication.edit_template')}: ${template.name}`"
                :subtitle="template.code"
            >
                <template #back>
                    <Link
                        :href="route('system.communication.templates.index')"
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
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <Link
                        :href="route('system.communication.templates.index')"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{ $t('system.communication.cancel') }}
                    </Link>
                    <AppButton
                        variant="action-xs"
                        @click="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ $t('system.communication.save_changes') }}
                    </AppButton>
                </template>
            </PageHeader>

            <!-- Info & Variables Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Meta Info Card -->
                <div
                    class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-3"
                >
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">
                        {{ $t('system.communication.template_details') }}
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $t('system.communication.code') }}
                            </span>
                            <span
                                class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                            >
                                {{ template.code }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $t('system.communication.type') }}
                            </span>
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                            >
                                {{
                                    template.type === 'email'
                                        ? $t('system.communication.email')
                                        : $t('system.communication.sms_label')
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <AppToggle v-model="form.is_active" />
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('system.communication.enable_template') }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Available Variables Card -->
                <div
                    class="md:col-span-2 bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700"
                >
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">
                        {{ $t('system.communication.available_variables_click_to_copy') }}
                    </h3>
                    <div
                        v-if="template.variables && template.variables.length"
                        class="flex flex-wrap gap-2"
                    >
                        <button
                            v-for="variable in template.variables"
                            :key="variable"
                            type="button"
                            @click="copyVariable(variable)"
                            class="group relative inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-mono bg-gray-50 hover:bg-selection-bg dark:bg-gray-700/50 text-gray-700 hover:text-action-primary dark:text-gray-300 border border-gray-200 hover:border-action-primary/25 dark:border-gray-600 transition-all cursor-pointer"
                        >
                            <span>{{ variable }}</span>
                            <svg
                                class="w-3 h-3 opacity-50 group-hover:opacity-100"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </button>
                    </div>
                    <p v-else class="text-xs text-gray-500">
                        {{ $t('system.communication.no_variables_defined_for_this_template') }}
                    </p>
                </div>
            </div>

            <!-- Form Edit & Preview -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
            >
                <!-- Tabs: Editor vs Live Preview -->
                <div
                    class="flex items-center border-b border-gray-200 dark:border-gray-700 px-6 pt-4 gap-4"
                >
                    <button
                        type="button"
                        @click="viewMode = 'edit'"
                        :class="[
                            'pb-3 text-xs font-bold border-b-2 transition-colors',
                            viewMode === 'edit'
                                ? 'border-action-primary text-action-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        {{ $t('system.communication.template_editor') }}
                    </button>
                    <button
                        v-if="template.type === 'email'"
                        type="button"
                        @click="viewMode = 'preview'"
                        :class="[
                            'pb-3 text-xs font-bold border-b-2 transition-colors',
                            viewMode === 'preview'
                                ? 'border-action-primary text-action-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        {{ $t('system.communication.live_preview') }}
                    </button>
                </div>

                <div class="p-6">
                    <div v-show="viewMode === 'edit'" class="space-y-6">
                        <!-- Subject (if email) -->
                        <div v-if="template.type === 'email'">
                            <AppInput
                                v-model="form.subject"
                                type="text"
                                :label="$t('system.communication.subject')"
                                :error="form.errors.subject"
                            />
                        </div>

                        <!-- Content Textarea -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label
                                    class="block text-xs font-bold text-gray-700 dark:text-gray-300"
                                >
                                    {{ $t('system.communication.content') }}
                                </label>
                                <span class="text-[11px] text-gray-400 font-mono">
                                    {{
                                        template.type === 'email' ? 'HTML Supported' : 'Plain Text'
                                    }}
                                </span>
                            </div>
                            <AppTextarea
                                v-model="form.content"
                                :rows="16"
                                :error="form.errors.content"
                                class="font-mono"
                                dir="ltr"
                            />
                        </div>
                    </div>

                    <!-- Live HTML Preview -->
                    <div v-show="viewMode === 'preview'" class="space-y-3">
                        <div
                            class="p-3 bg-gray-100 dark:bg-gray-900 rounded-xl text-xs text-gray-600 dark:text-gray-300 font-mono"
                        >
                            <strong>
                                {{ $t('system.communication.subject_2') }}
                            </strong>
                            {{ form.subject }}
                        </div>
                        <div
                            class="p-6 rounded-xl border border-gray-200 bg-white text-gray-900 shadow-inner overflow-auto max-h-[600px]"
                            v-safe-html="form.content"
                        ></div>
                    </div>
                </div>

                <!-- Footer Save Bar -->
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3"
                >
                    <AppButton
                        variant="action-xl"
                        @click="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ $t('common.save_changes') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';

const props = defineProps({
    template: Object,
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');
const { success: toastSuccess } = useToast();
const viewMode = ref('edit');

const form = useForm({
    subject: props.template.subject || '',
    content: props.template.content || '',
    is_active: Boolean(props.template.is_active),
});

const submit = () => {
    form.put(route('system.communication.templates.update', props.template.id));
};

const copyVariable = (variable) => {
    navigator.clipboard.writeText(variable);
    toastSuccess(
        isAr.value ? t('system.communication.copied', { variable: variable }) : `Copied ${variable}`
    );
};
</script>
