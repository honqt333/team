<template>
    <div
        class="min-h-screen bg-gray-50 dark:bg-gray-900 text-slate-800 dark:text-slate-100 font-sans"
    >
        <!-- Global Toast Notifications -->
        <Toast />

        <div class="flex h-screen overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside
                :class="[
                    'hidden md:flex md:w-64 md:shrink-0 md:flex-col bg-white dark:bg-gray-800/90 border-slate-200/80 dark:border-gray-700/80',
                    isRtl ? 'border-l' : 'border-r',
                ]"
            >
                <!-- Brand / Portal Header -->
                <div
                    class="h-16 flex items-center gap-3 px-4 border-b border-slate-200/80 dark:border-gray-700/80 flex-shrink-0"
                >
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-xs flex-shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ $t('employee_portal.title') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ page.props.auth?.user?.name }}
                        </p>
                    </div>
                </div>

                <!-- Navigation List (Desktop) -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <Link
                        v-for="item in NAV_ITEMS"
                        :key="item.id"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            isActive(item.href)
                                ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-semibold shadow-2xs'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/80 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white',
                        ]"
                    >
                        <span class="flex-shrink-0" v-html="item.icon" />
                        <span class="truncate">{{ $t(item.label) }}</span>
                    </Link>

                    <!-- Back to Main Dashboard (for managers / users with elevated permissions) -->
                    <template v-if="hasOtherPermissions">
                        <div class="my-3 border-t border-gray-200 dark:border-gray-700/80"></div>
                        <Link
                            href="/dashboard"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50/80 dark:hover:bg-indigo-950/40"
                        >
                            <svg
                                class="w-5 h-5 flex-shrink-0 rtl:rotate-180"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"
                                />
                            </svg>
                            <span class="truncate">
                                {{ $t('employee_portal.back_to_dashboard') }}
                            </span>
                        </Link>
                    </template>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Top Bar -->
                <header
                    class="h-16 flex items-center justify-between px-4 sm:px-6 bg-white dark:bg-gray-800/90 border-b border-slate-200/80 dark:border-gray-700/80 flex-shrink-0 shadow-2xs"
                >
                    <!-- Mobile Hamburger -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = true"
                        class="md:hidden p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-hidden"
                        :aria-label="$t('common.menu')"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <div
                        class="hidden md:flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"
                    >
                        <span>{{ page.props.app_name || $t('common.app_name') }}</span>
                        <span>•</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">
                            {{ $t('employee_portal.title') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Language Toggle -->
                        <button
                            type="button"
                            @click="toggleLocale"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-slate-200/70 dark:border-gray-700/60"
                        >
                            {{
                                currentLocale === 'ar' ? $t('common.lang.en') : $t('common.lang.ar')
                            }}
                        </button>

                        <!-- Theme Toggle -->
                        <button
                            type="button"
                            @click="toggleThemeHandler"
                            class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-slate-200/70 dark:border-gray-700/60"
                            :aria-label="$t('common.toggle_theme')"
                        >
                            <svg
                                v-if="isDark"
                                class="w-4 h-4 text-amber-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 text-slate-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                />
                            </svg>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 p-1 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-hidden"
                            >
                                <img
                                    v-if="
                                        page.props.auth?.user?.photo_url &&
                                        !page.props.auth.user.photo_url.includes('ui-avatars.com')
                                    "
                                    :src="page.props.auth.user.photo_url"
                                    class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700"
                                    alt="User Avatar"
                                />
                                <div
                                    v-else
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-2xs"
                                >
                                    {{ userInitial }}
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <Transition name="dropdown">
                                <div
                                    v-if="userMenuOpen"
                                    :class="[
                                        'absolute top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200/80 dark:border-gray-700 py-1.5 z-50',
                                        isRtl ? 'left-0' : 'right-0',
                                    ]"
                                >
                                    <div
                                        class="px-4 py-2 border-b border-gray-100 dark:border-gray-700/60"
                                    >
                                        <p
                                            class="text-xs font-semibold text-gray-900 dark:text-white truncate"
                                        >
                                            {{ page.props.auth?.user?.name }}
                                        </p>
                                        <p
                                            class="text-[11px] text-gray-500 dark:text-gray-400 truncate"
                                        >
                                            {{ page.props.auth?.user?.email }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
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
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                            />
                                        </svg>
                                        <span>{{ $t('nav.logout') }}</span>
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </header>

                <!-- Page Content Slot -->
                <main class="flex-1 px-4 py-6 overflow-auto">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Mobile Drawer Backdrop -->
        <Transition name="fade">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 bg-black/50 backdrop-blur-xs z-40 md:hidden"
                @click="mobileMenuOpen = false"
            ></div>
        </Transition>

        <!-- Mobile Drawer Panel -->
        <Transition :name="isRtl ? 'slide-right' : 'slide-left'">
            <aside
                v-if="mobileMenuOpen"
                :class="[
                    'fixed inset-y-0 w-64 bg-white dark:bg-gray-800 z-50 flex flex-col md:hidden shadow-2xl',
                    isRtl ? 'right-0' : 'left-0',
                ]"
            >
                <div
                    class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-xs font-bold"
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $t('employee_portal.title') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="mobileMenuOpen = false"
                        class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 flex-shrink-0"
                        :aria-label="$t('common.close')"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Navigation List (Mobile Drawer - Identical unified items) -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <Link
                        v-for="item in NAV_ITEMS"
                        :key="'mob-' + item.id"
                        :href="item.href"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            isActive(item.href)
                                ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-semibold shadow-2xs'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100/80 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white',
                        ]"
                    >
                        <span class="flex-shrink-0" v-html="item.icon" />
                        <span class="truncate">{{ $t(item.label) }}</span>
                    </Link>

                    <!-- Back to Main Dashboard (Mobile) -->
                    <template v-if="hasOtherPermissions">
                        <div class="my-3 border-t border-gray-200 dark:border-gray-700/80"></div>
                        <Link
                            href="/dashboard"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50/80 dark:hover:bg-indigo-950/40"
                        >
                            <svg
                                class="w-5 h-5 flex-shrink-0 rtl:rotate-180"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"
                                />
                            </svg>
                            <span class="truncate">
                                {{ $t('employee_portal.back_to_dashboard') }}
                            </span>
                        </Link>
                    </template>
                </nav>
            </aside>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router, Link } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import { setLocale, getCurrentLocale } from '@/i18n/index.js';
import Toast from '@/Components/Toast.vue';
import { useFlashToasts } from '@/Composables/useFlashToasts';

const { locale } = useI18n();
const page = usePage();

useFlashToasts(page, { immediate: true });

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const { isDark, toggle: toggleThemeHandler } = useTheme();
const currentLocale = ref('ar');

const NAV_ITEMS = [
    {
        id: 'dashboard',
        label: 'employee_portal.dashboard',
        href: '/app/my',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    },
    {
        id: 'tasks',
        label: 'employee_portal.my_tasks',
        href: '/app/my/tasks',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    },
    {
        id: 'profile',
        label: 'employee_portal.profile',
        href: '/app/my/profile',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    },
    {
        id: 'attendance',
        label: 'employee_portal.attendance',
        href: '/app/my/attendance',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    },
    {
        id: 'leaves',
        label: 'employee_portal.leaves',
        href: '/app/my/leaves',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
    },
    {
        id: 'payslips',
        label: 'employee_portal.payslips',
        href: '/app/my/payslips',
        icon: '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    },
];

// Computed
const isRtl = computed(() => currentLocale.value === 'ar');
const userInitial = computed(() => {
    return page.props.auth?.user?.name?.[0]?.toUpperCase() || 'U';
});

// Check if user has permissions beyond basic employee portal access
const hasOtherPermissions = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    if (roles.length === 0) return false;
    // Show back-to-dashboard if user has roles beyond pure employee
    return roles.some((role) => role.name !== 'employee');
});

// Reactive active checker matching current Inertia page URL
const isActive = (path) => {
    const rawUrl = page.url || window.location.pathname;
    const currentPath = rawUrl.split('?')[0];
    if (path === '/app/my') {
        return currentPath === '/app/my' || currentPath === '/app/my/';
    }
    return currentPath.startsWith(path);
};

const toggleLocale = () => {
    const newLocale = currentLocale.value === 'ar' ? 'en' : 'ar';
    router.post(
        route('locale.set'),
        { locale: newLocale },
        {
            onSuccess: () => {
                setLocale(newLocale);
                currentLocale.value = newLocale;
                locale.value = newLocale;
                document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
                document.documentElement.lang = newLocale;
            },
        }
    );
};

const logout = () => {
    router.post('/logout');
};

// Close dropdowns on outside click
const handleClickOutside = (e) => {
    if (!e.target.closest('.relative')) {
        userMenuOpen.value = false;
    }
};

onMounted(() => {
    currentLocale.value = getCurrentLocale();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(-100%);
}

.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
}
</style>
