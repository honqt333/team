<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="flex h-screen overflow-hidden">
            <!-- ============================================================ -->
            <!-- Desktop Sidebar                                                -->
            <!-- ============================================================ -->
            <aside
                :class="[
                    'hidden md:flex md:w-64 md:shrink-0 md:flex-col bg-white dark:bg-gray-800',
                    isRtl
                        ? 'border-l border-gray-200 dark:border-gray-700'
                        : 'border-r border-gray-200 dark:border-gray-700',
                ]"
            >
                <!-- Brand -->
                <div
                    class="h-14 flex items-center gap-3 px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <div
                        class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center flex-shrink-0"
                    >
                        <svg
                            class="w-5 h-5 text-white"
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
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ $t('common.app_name') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('system.brand.panel_name') }}
                        </p>
                    </div>
                </div>

                <!-- Navigation (shared with mobile drawer) -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <template v-for="(group, gIdx) in systemNavGroups" :key="group.label">
                        <!-- Group heading -->
                        <p
                            v-if="gIdx > 0"
                            class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6"
                        >
                            {{ $t(`system.nav_groups.${group.label}`) }}
                        </p>

                        <!-- Items -->
                        <a
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                isActive(item.href) &&
                                page.url === item.href &&
                                !item.href.endsWith('/admin-users')
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <!-- WhatsApp uses a colored path; everything else uses the standard path -->
                            <svg
                                v-if="item.icon === 'whatsapp'"
                                class="w-5 h-5 flex-shrink-0 text-emerald-500"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>

                            <span class="flex-1">
                                {{ $t(`system.nav_items.${item.label}`) }}
                            </span>

                            <!-- Badge for unread contact messages -->
                            <span
                                v-if="item.showBadge && $page.props.auth[item.showBadge] > 0"
                                class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full"
                            >
                                {{ $page.props.auth[item.showBadge] }}
                            </span>
                        </a>
                    </template>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <a
                        href="/app"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            :class="isRtl ? '' : 'rotate-180'"
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
                        {{ $t('system.nav_items.back_to_app') }}
                    </a>
                </nav>
            </aside>

            <!-- ============================================================ -->
            <!-- Main Content                                                   -->
            <!-- ============================================================ -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Top bar -->
                <header
                    class="h-14 flex items-center justify-between px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <!-- Mobile hamburger -->
                    <button
                        @click="mobileMenuOpen = true"
                        class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
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

                    <div class="hidden md:block text-sm text-gray-500 dark:text-gray-400">
                        {{ formattedDate }}
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Theme Toggle -->
                        <button
                            @click="toggleThemeHandler"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            :title="isDark ? $t('system.theme.light') : $t('system.theme.dark')"
                        >
                            <svg
                                v-if="isDark"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14 7 7 0 000-14z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5"
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

                        <!-- User Dropdown -->
                        <div class="relative">
                            <button
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white text-sm font-bold"
                                >
                                    {{
                                        $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() ||
                                        'A'
                                    }}
                                </div>
                            </button>

                            <Transition name="dropdown">
                                <div
                                    v-if="userMenuOpen"
                                    :class="[
                                        'absolute top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50',
                                        isRtl ? 'left-0' : 'right-0',
                                    ]"
                                >
                                    <div
                                        class="px-4 py-2 border-b border-gray-200 dark:border-gray-700"
                                    >
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                        >
                                            {{ $page.props.auth?.user?.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $page.props.auth?.user?.email }}
                                        </p>
                                    </div>

                                    <a
                                        href="/system/profile"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        @click="userMenuOpen = false"
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        {{ $t('system.user_menu.profile') }}
                                    </a>

                                    <div
                                        class="border-t border-gray-200 dark:border-gray-700 my-1"
                                    ></div>

                                    <button
                                        type="button"
                                        @click.prevent="handleLogout"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
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
                                        {{ $t('system.user_menu.logout') }}
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

        <!-- ============================================================ -->
        <!-- Mobile Drawer (same nav as desktop)                            -->
        <!-- ============================================================ -->
        <Transition name="fade">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 bg-black/50 z-40 md:hidden"
                @click="mobileMenuOpen = false"
            ></div>
        </Transition>

        <Transition :name="isRtl ? 'slide-right' : 'slide-left'">
            <aside
                v-if="mobileMenuOpen"
                :class="[
                    'fixed inset-y-0 w-64 bg-white dark:bg-gray-800 z-50 flex flex-col md:hidden shadow-xl',
                    isRtl ? 'right-0' : 'left-0',
                ]"
            >
                <div
                    class="h-14 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center flex-shrink-0"
                        >
                            <svg
                                class="w-5 h-5 text-white"
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
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $t('common.app_name') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('system.brand.panel_name') }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="mobileMenuOpen = false"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 flex-shrink-0"
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

                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <template v-for="(group, gIdx) in systemNavGroups" :key="`m-${group.label}`">
                        <p
                            v-if="gIdx > 0"
                            class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6"
                        >
                            {{ $t(`system.nav_groups.${group.label}`) }}
                        </p>

                        <a
                            v-for="item in group.items"
                            :key="`m-${item.href}`"
                            :href="item.href"
                            @click="mobileMenuOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                isActive(item.href)
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                v-if="item.icon === 'whatsapp'"
                                class="w-5 h-5 flex-shrink-0 text-emerald-500"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>

                            <span class="flex-1">
                                {{ $t(`system.nav_items.${item.label}`) }}
                            </span>

                            <span
                                v-if="item.showBadge && $page.props.auth[item.showBadge] > 0"
                                class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full"
                            >
                                {{ $page.props.auth[item.showBadge] }}
                            </span>
                        </a>
                    </template>

                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <a
                        href="/app"
                        @click="mobileMenuOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            :class="isRtl ? '' : 'rotate-180'"
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
                        {{ $t('system.nav_items.back_to_app') }}
                    </a>
                </nav>
            </aside>
        </Transition>

        <ConfirmModal />
        <Toast />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useTheme } from '@/Composables/useTheme';
import { useToast } from '@/Composables/useToast';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Toast from '@/Components/Toast.vue';
import { systemNavGroups } from '@/Layouts/systemNav.js';

const page = usePage();
const { locale } = useI18n();
const {
    success: toastSuccess,
    error: toastError,
    warning: toastWarning,
    info: toastInfo,
} = useToast();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash) {
            if (flash.success) toastSuccess(flash.success, { fromFlash: true });
            if (flash.error) toastError(flash.error, { fromFlash: true });
            if (flash.warning) toastWarning(flash.warning, { fromFlash: true });
            if (flash.info) toastInfo(flash.info, { fromFlash: true });
        }
    },
    { deep: true, immediate: true }
);

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const { isDark, toggle: toggleThemeHandler } = useTheme();
const isRtl = computed(() => locale.value === 'ar');

const formattedDate = ref('');

// Update the topbar date once on mount, then every minute. This runs
// only in the browser (no SSR concern) and matches the original
// implementation's behaviour.
let intervalId;
onMounted(() => {
    const update = () => {
        formattedDate.value = new Date().toLocaleDateString('ar-SA-u-nu-latn', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };
    update();
    intervalId = setInterval(update, 60000);
});
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});

const handleLogout = () => {
    router.post(route('logout'));
};

const isActive = (href) => {
    const currentPath = page.url;
    if (href === '/system') {
        return currentPath === '/system' || currentPath === '/system/';
    }
    return currentPath.startsWith(href);
};
</script>

<style scoped>
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
