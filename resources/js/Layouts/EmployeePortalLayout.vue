<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Global Toast Notifications -->
        <Toast />

        <div class="flex h-screen overflow-hidden">
            <!-- Desktop Sidebar -->
            <aside :class="[
                'hidden md:flex md:w-64 md:shrink-0 md:flex-col bg-white dark:bg-gray-800',
                isRtl ? 'border-l border-gray-200 dark:border-gray-700' : 'border-r border-gray-200 dark:border-gray-700'
            ]">
                <!-- Brand -->
                <div
                    class="h-14 flex items-center gap-3 px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div
                        class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white font-bold">
                        👤
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

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <a href="/app/my" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ $t('employee_portal.dashboard') }}
                    </a>

                    <a href="/app/my/profile" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/profile')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ $t('employee_portal.profile') }}
                    </a>

                    <a href="/app/my/attendance" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/attendance')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('employee_portal.attendance') }}
                    </a>

                    <a href="/app/my/leaves" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/leaves')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $t('employee_portal.leaves') }}
                    </a>

                    <a href="/app/my/payslips" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/payslips')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ $t('employee_portal.payslips') }}
                    </a>

                    <!-- Back to Main Dashboard (for users with other permissions) -->
                    <template v-if="hasOtherPermissions">
                        <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>
                        <a href="/dashboard"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                            </svg>
                            {{ $t('employee_portal.back_to_dashboard') }}
                        </a>
                    </template>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Top bar -->
                <header
                    class="h-14 flex items-center justify-between px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <!-- Mobile hamburger -->
                    <button @click="mobileMenuOpen = true"
                        class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="hidden md:block"></div>

                    <div class="flex items-center gap-1">
                        <!-- Language toggle -->
                        <button @click="toggleLocale"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            {{ currentLocale === 'ar' ? $t('common.lang.en') : $t('common.lang.ar') }}
                        </button>

                        <!-- Theme toggle -->
                        <button @click="toggleThemeHandler"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- User Dropdown -->
                        <div class="relative">
                            <button @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white text-sm font-bold">
                                    {{ userInitial }}
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <Transition name="dropdown">
                                <div v-if="userMenuOpen" :class="[
                                    'absolute top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50',
                                    isRtl ? 'left-0' : 'right-0'
                                ]">
                                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                    <button @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        {{ $t('nav.logout') }}
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 overflow-auto">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Mobile Drawer Backdrop -->
        <Transition name="fade">
            <div v-if="mobileMenuOpen" class="fixed inset-0 bg-black/50 z-40 md:hidden" @click="mobileMenuOpen = false">
            </div>
        </Transition>

        <!-- Mobile Drawer Panel -->
        <Transition :name="isRtl ? 'slide-right' : 'slide-left'">
            <aside v-if="mobileMenuOpen" :class="[
                'fixed inset-y-0 w-64 bg-white dark:bg-gray-800 z-50 flex flex-col md:hidden shadow-xl',
                isRtl ? 'right-0' : 'left-0'
            ]">
                <div
                    class="h-14 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white font-bold">
                            👤
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $t('employee_portal.title') }}
                            </p>
                        </div>
                    </div>
                    <button @click="mobileMenuOpen = false"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <a href="/app/my" @click="mobileMenuOpen = false" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ $t('employee_portal.dashboard') }}
                    </a>

                    <a href="/app/my/profile" @click="mobileMenuOpen = false" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/profile')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ $t('employee_portal.profile') }}
                    </a>

                    <a href="/app/my/attendance" @click="mobileMenuOpen = false" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/attendance')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('employee_portal.attendance') }}
                    </a>

                    <a href="/app/my/leaves" @click="mobileMenuOpen = false" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/leaves')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $t('employee_portal.leaves') }}
                    </a>

                    <a href="/app/my/payslips" @click="mobileMenuOpen = false" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        isActive('/app/my/payslips')
                            ? 'bg-purple-50 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ $t('employee_portal.payslips') }}
                    </a>

                    <!-- Back to Main Dashboard (for users with other permissions) - Mobile -->
                    <template v-if="hasOtherPermissions">
                        <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>
                        <a href="/dashboard" @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                            </svg>
                            {{ $t('employee_portal.back_to_dashboard') }}
                        </a>
                    </template>
                </nav>
            </aside>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router } from '@inertiajs/vue3';
import { toggleTheme } from '@/theme.js';
import { setLocale, getCurrentLocale } from '@/i18n/index.js';
import Toast from '@/Components/Toast.vue';

const { locale } = useI18n();
const page = usePage();

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const isDark = ref(false);
const currentLocale = ref('ar');

// Computed
const isRtl = computed(() => currentLocale.value === 'ar');
const userInitial = computed(() => {
    return page.props.auth?.user?.name?.[0]?.toUpperCase() || 'U';
});

// Check if user has permissions beyond basic employee portal access
// Check if user has permissions beyond basic employee portal access
const hasOtherPermissions = computed(() => {
    // Check roles
    const roles = page.props.auth?.user?.roles || [];

    // If user has no roles, they shouldn't see the back link
    if (roles.length === 0) return false;

    // Check if user has any role that is NOT 'employee'
    // This allows admins/managers who are also employees to see the link
    // But hides it for pure employees
    return roles.some(role => role.name !== 'employee');
});

// Methods
const isActive = (path) => {
    const currentPath = window.location.pathname;
    if (path === '/app/my') {
        return currentPath === '/app/my' || currentPath === '/app/my/';
    }
    return currentPath.startsWith(path);
};

const toggleThemeHandler = () => {
    isDark.value = toggleTheme();
};

const toggleLocale = () => {
    const newLocale = currentLocale.value === 'ar' ? 'en' : 'ar';
    setLocale(newLocale);
    currentLocale.value = newLocale;
    locale.value = newLocale;
    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = newLocale;
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
    isDark.value = document.documentElement.classList.contains('dark');
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
