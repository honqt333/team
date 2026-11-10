<template>
    <AppLayout>
        <Head :title="$t('settings.notifications.title')" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('settings.notifications.title')"
                :subtitle="$t('settings.notifications.subtitle')"
                gradientFrom="from-amber-600"
                gradientTo="to-orange-700"
                glowFrom="from-amber-500"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                        <button
                            type="button"
                            :disabled="testing"
                            @click="sendTestNotification"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/80 border border-gray-200 dark:border-gray-700 rounded-xl transition-all shadow-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4 text-amber-500 animate-spin"
                                v-if="testing"
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
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 text-amber-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <span>
                                {{
                                    testing
                                        ? $t('settings.notifications.actions.sending_test')
                                        : $t('settings.notifications.actions.send_test')
                                }}
                            </span>
                        </button>

                        <button
                            type="button"
                            :disabled="form.processing"
                            @click="submit"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 rounded-xl transition-all shadow-md shadow-amber-500/25 disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4 text-white animate-spin"
                                v-if="form.processing"
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
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span>
                                {{
                                    form.processing
                                        ? $t('settings.notifications.actions.saving')
                                        : $t('settings.notifications.actions.save')
                                }}
                            </span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Navigation Tabs -->
            <div
                class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3"
            >
                <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" />
            </div>

            <!-- Tab 1: Channels -->
            <div v-if="activeTab === 'channels'" class="space-y-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-6"
                >
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $t('settings.notifications.channels.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('settings.notifications.channels.description') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- In-App Notifications -->
                        <div
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-start justify-between gap-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400"
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
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $t('settings.notifications.channels.in_app') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                    {{ $t('settings.notifications.channels.in_app_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.in_app" color="primary" />
                        </div>

                        <!-- Email Notifications -->
                        <div
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-start justify-between gap-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="p-2 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400"
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
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $t('settings.notifications.channels.email') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                    {{ $t('settings.notifications.channels.email_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.email" color="blue" />
                        </div>

                        <!-- SMS Notifications -->
                        <div
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-start justify-between gap-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
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
                                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $t('settings.notifications.channels.sms') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                    {{ $t('settings.notifications.channels.sms_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.sms" color="success" />
                        </div>

                        <!-- WhatsApp Notifications -->
                        <div
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-start justify-between gap-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="p-2 rounded-lg bg-green-500/10 text-green-600 dark:text-green-400"
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
                                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ $t('settings.notifications.channels.whatsapp') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                    {{ $t('settings.notifications.channels.whatsapp_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.whatsapp" color="success" />
                        </div>
                    </div>

                    <!-- Channel Specific Configuration -->
                    <div
                        class="border-t border-gray-200 dark:border-gray-700 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('settings.notifications.channels.email_recipient') }}
                            </label>
                            <input
                                v-model="form.channel_configs.email_recipient"
                                type="email"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                                :placeholder="
                                    $t('settings.notifications.channels.email_recipient_hint')
                                "
                            />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                {{ $t('settings.notifications.channels.email_recipient_hint') }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('settings.notifications.channels.whatsapp_phone') }}
                            </label>
                            <input
                                v-model="form.channel_configs.whatsapp_phone"
                                type="text"
                                dir="ltr"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                                :placeholder="
                                    $t('settings.notifications.channels.whatsapp_phone_hint')
                                "
                            />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                {{ $t('settings.notifications.channels.whatsapp_phone_hint') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Events Matrix -->
            <div v-if="activeTab === 'events'" class="space-y-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div class="p-5 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $t('settings.notifications.events.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('settings.notifications.events.description') }}
                        </p>
                    </div>

                    <!-- Desktop Matrix Table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table
                            class="w-full text-start divide-y divide-gray-200 dark:divide-gray-700"
                        >
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="py-3.5 px-6 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        {{ $t('settings.notifications.events.table_event') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3.5 px-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        {{ $t('settings.notifications.events.table_in_app') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3.5 px-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        {{ $t('settings.notifications.events.table_email') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3.5 px-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        {{ $t('settings.notifications.events.table_sms') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3.5 px-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"
                                    >
                                        {{ $t('settings.notifications.events.table_whatsapp') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-800"
                            >
                                <tr
                                    v-for="eventKey in eventKeys"
                                    :key="eventKey"
                                    class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    <td
                                        class="py-3.5 px-6 text-sm font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        {{ $t(`settings.notifications.events.${eventKey}`) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-model="form.events[eventKey].in_app"
                                                size="sm"
                                                color="primary"
                                                :disabled="!form.channels.in_app"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-model="form.events[eventKey].email"
                                                size="sm"
                                                color="blue"
                                                :disabled="!form.channels.email"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-model="form.events[eventKey].sms"
                                                size="sm"
                                                color="success"
                                                :disabled="!form.channels.sms"
                                            />
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-model="form.events[eventKey].whatsapp"
                                                size="sm"
                                                color="success"
                                                :disabled="!form.channels.whatsapp"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Event Cards List -->
                    <div
                        class="block sm:hidden divide-y divide-gray-100 dark:divide-gray-700/60 p-4 space-y-4"
                    >
                        <div
                            v-for="eventKey in eventKeys"
                            :key="eventKey"
                            class="pt-3 first:pt-0 space-y-3"
                        >
                            <span class="text-sm font-semibold text-gray-900 dark:text-white block">
                                {{ $t(`settings.notifications.events.${eventKey}`) }}
                            </span>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div
                                    class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ $t('settings.notifications.events.table_in_app') }}
                                    </span>
                                    <AppToggle
                                        v-model="form.events[eventKey].in_app"
                                        size="sm"
                                        color="primary"
                                        :disabled="!form.channels.in_app"
                                    />
                                </div>
                                <div
                                    class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ $t('settings.notifications.events.table_email') }}
                                    </span>
                                    <AppToggle
                                        v-model="form.events[eventKey].email"
                                        size="sm"
                                        color="blue"
                                        :disabled="!form.channels.email"
                                    />
                                </div>
                                <div
                                    class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ $t('settings.notifications.events.table_sms') }}
                                    </span>
                                    <AppToggle
                                        v-model="form.events[eventKey].sms"
                                        size="sm"
                                        color="success"
                                        :disabled="!form.channels.sms"
                                    />
                                </div>
                                <div
                                    class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800"
                                >
                                    <span class="text-gray-600 dark:text-gray-400">
                                        {{ $t('settings.notifications.events.table_whatsapp') }}
                                    </span>
                                    <AppToggle
                                        v-model="form.events[eventKey].whatsapp"
                                        size="sm"
                                        color="success"
                                        :disabled="!form.channels.whatsapp"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Templates -->
            <div v-if="activeTab === 'templates'" class="space-y-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-6"
                >
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $t('settings.notifications.templates.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('settings.notifications.templates.description') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Work Order Created Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $t('settings.notifications.templates.work_order_created') }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {customer_name}, {work_order_number}, {vehicle}, {company_name}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.work_order_created"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Work Order Completed Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{
                                        $t('settings.notifications.templates.work_order_completed')
                                    }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {customer_name}, {work_order_number}, {vehicle}, {company_name}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.work_order_completed"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Quote Shared Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $t('settings.notifications.templates.quote_shared') }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {customer_name}, {quote_number}, {total}, {company_name}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.quote_shared"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Invoice Issued Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $t('settings.notifications.templates.invoice_issued') }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {customer_name}, {invoice_number}, {total}, {company_name}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.invoice_issued"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Appointment Booked Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $t('settings.notifications.templates.appointment_booked') }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {customer_name}, {reference_number}, {date}, {company_name}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.appointment_booked"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Low Stock Alert Template -->
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $t('settings.notifications.templates.low_stock_alert') }}
                                </label>
                                <span
                                    class="text-[11px] text-amber-600 dark:text-amber-400 font-mono"
                                >
                                    {item_name}, {quantity}
                                </span>
                            </div>
                            <textarea
                                v-model="form.templates.low_stock_alert"
                                rows="3"
                                class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Footer for Mobile -->
            <div
                class="sm:hidden fixed bottom-0 inset-x-0 p-3 bg-white/95 dark:bg-gray-800/95 backdrop-blur border-t border-gray-200 dark:border-gray-700 flex gap-2 z-30"
            >
                <button
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl shadow-md disabled:opacity-50"
                >
                    <svg
                        class="w-4 h-4 text-white animate-spin"
                        v-if="form.processing"
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
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span>
                        {{
                            form.processing
                                ? $t('settings.notifications.actions.saving')
                                : $t('settings.notifications.actions.save')
                        }}
                    </span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';

const { t } = useI18n();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    templates: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('channels');
const testing = ref(false);

const tabs = [
    { id: 'channels', label: t('settings.notifications.tabs.channels') },
    { id: 'events', label: t('settings.notifications.tabs.events') },
    { id: 'templates', label: t('settings.notifications.tabs.templates') },
];

const eventKeys = [
    'appointment_booked',
    'appointment_reminder',
    'appointment_cancelled',
    'work_order_created',
    'work_order_status_changed',
    'work_order_completed',
    'quote_shared',
    'quote_approved',
    'invoice_issued',
    'payment_received',
    'low_stock_alert',
];

const form = useForm({
    channels: {
        in_app: props.settings?.channels?.in_app ?? true,
        email: props.settings?.channels?.email ?? false,
        sms: props.settings?.channels?.sms ?? false,
        whatsapp: props.settings?.channels?.whatsapp ?? false,
    },
    channel_configs: {
        email_recipient: props.settings?.channel_configs?.email_recipient ?? '',
        whatsapp_phone: props.settings?.channel_configs?.whatsapp_phone ?? '',
    },
    events: JSON.parse(JSON.stringify(props.settings?.events ?? {})),
    templates: {
        work_order_created: props.templates?.work_order_created ?? '',
        work_order_completed: props.templates?.work_order_completed ?? '',
        quote_shared: props.templates?.quote_shared ?? '',
        invoice_issued: props.templates?.invoice_issued ?? '',
        appointment_booked: props.templates?.appointment_booked ?? '',
        appointment_reminder: props.templates?.appointment_reminder ?? '',
        low_stock_alert: props.templates?.low_stock_alert ?? '',
    },
});

const submit = () => {
    form.put(route('settings.notifications.update'), {
        preserveScroll: true,
    });
};

const sendTestNotification = () => {
    testing.value = true;
    router.post(
        route('settings.notifications.test'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testing.value = false;
            },
        }
    );
};
</script>
