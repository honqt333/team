<template>
    <AppLayout>
        <Head :title="$t('settings.integrations.title')" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('settings.integrations.title')"
                :subtitle="$t('settings.integrations.hub_subtitle')"
                gradientFrom="from-action-primary"
                gradientTo="to-action-primary-hover"
                glowFrom="from-action-primary"
                badgeBg="bg-selection-bg"
                badgeText="text-action-primary"
                badgeBorder="border-action-primary/25"
                badgeDot="bg-action-primary"
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
                            d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <!-- 1. المساعدة والشروحات -->
                <Link
                    href="/app/help"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/25 group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800"
                            >
                                {{ $t('settings.integrations.badges.available') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors"
                        >
                            {{ $t('settings.integrations.help_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.help_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-sky-600 dark:text-sky-400"
                    >
                        <span>{{ $t('settings.integrations.open_help') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 2. البلاغات والمقترحات -->
                <Link
                    href="/app/feedback"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-action-primary hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center shadow-lg shadow-focus-ring-soft group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
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
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-selection-bg text-selection-text border border-action-primary/25"
                            >
                                {{ $t('settings.integrations.badges.direct_support') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-action-primary transition-colors"
                        >
                            {{ $t('settings.integrations.feedback_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.feedback_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-action-primary"
                    >
                        <span>{{ $t('settings.integrations.submit_feedback') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 3. الفوترة الإلكترونية (زاتكا) -->
                <Link
                    href="/app/settings/company?tab=zatca"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                            >
                                {{ $t('settings.integrations.badges.approved') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                        >
                            {{ $t('settings.integrations.zatca_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.zatca_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        <span>{{ $t('settings.integrations.manage_zatca') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 4. موقع المنشأة الإلكتروني -->
                <Link
                    href="/app/settings/website"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-action-primary hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center shadow-lg shadow-focus-ring-soft group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-selection-bg text-selection-text border border-action-primary/25"
                            >
                                {{ $t('settings.integrations.badges.active') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-action-primary transition-colors"
                        >
                            {{ $t('settings.integrations.website_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.website_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-action-primary"
                    >
                        <span>{{ $t('settings.integrations.customize_website') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 5. إعدادات وقوالب الطباعة -->
                <Link
                    :href="route('settings.print')"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.active') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors"
                        >
                            {{ $t('settings.integrations.print_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.print_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-amber-600 dark:text-amber-400"
                    >
                        <span>{{ $t('settings.integrations.manage_print') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 6. إعدادات الإشعارات والتنبيهات -->
                <Link
                    :href="route('settings.notifications.index')"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-transform duration-300"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
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
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.active') }}
                            </span>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors"
                        >
                            {{ $t('settings.integrations.notifications_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.notifications_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-amber-600 dark:text-amber-400"
                    >
                        <span>{{ $t('settings.integrations.manage_notifications') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- 7. إشعارات الواتساب والرسائل (قريباً) -->
                <div
                    class="relative bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700/80 flex flex-col justify-between opacity-85"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-md shadow-green-500/20"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.coming_soon') }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('settings.integrations.whatsapp_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.whatsapp_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-gray-400"
                    >
                        <span>{{ $t('settings.integrations.in_development') }}</span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    </div>
                </div>

                <!-- 7. الدفع الإلكتروني والتقسيط (قريباً) -->
                <div
                    class="relative bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700/80 flex flex-col justify-between opacity-85"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center shadow-md shadow-rose-500/20"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.coming_soon') }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('settings.integrations.payments_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.payments_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-gray-400"
                    >
                        <span>{{ $t('settings.integrations.in_development') }}</span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    </div>
                </div>

                <!-- 8. خرائط جوجل والمواقع (قريباً) -->
                <div
                    class="relative bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700/80 flex flex-col justify-between opacity-85"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-md shadow-amber-500/20"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.coming_soon') }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('settings.integrations.maps_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.maps_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-gray-400"
                    >
                        <span>{{ $t('settings.integrations.in_development') }}</span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    </div>
                </div>

                <!-- 9. الربط المحاسبي (قريباً) -->
                <div
                    class="relative bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-gray-200/80 dark:border-gray-700/80 flex flex-col justify-between opacity-85"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-600 to-blue-700 flex items-center justify-center shadow-md shadow-cyan-500/20"
                            >
                                <svg
                                    class="w-7 h-7 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                            >
                                {{ $t('settings.integrations.badges.coming_soon') }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('settings.integrations.erp_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 line-clamp-3">
                            {{ $t('settings.integrations.erp_desc') }}
                        </p>
                    </div>

                    <div
                        class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs font-semibold text-gray-400"
                    >
                        <span>{{ $t('settings.integrations.in_development') }}</span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
</script>
