<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    page: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const pageProps = usePage().props;

// The direction follows the locale. The previous implementation forced
// RTL with an `|| true`, which made the English branch unreachable and
// rendered every public custom page right-to-left.
const isRtl = computed(() => {
    return document.documentElement.dir === 'rtl';
});

// The platform name comes from the server (HandleInertiaRequests shares
// `app_name` from config('app.name_ar') / config('app.name')), so a
// rebrand does not require touching this component.
const appName = computed(() => {
    return pageProps.app_name || '';
});

const pageTitle = computed(() => {
    return isRtl.value
        ? props.page.title_ar || props.page.title_en
        : props.page.title_en || props.page.title_ar;
});

const pageContent = computed(() => {
    return isRtl.value
        ? props.page.content_ar || props.page.content_en
        : props.page.content_en || props.page.content_ar;
});

const siteTitle = computed(() => {
    // The tenant's own title wins; otherwise fall back to the platform
    // name, which the server supplies dynamically.
    return isRtl.value
        ? props.settings.title_ar || appName.value
        : props.settings.title_en || appName.value;
});
</script>

<template>
    <Head>
        <title>{{ pageTitle }} - {{ siteTitle }}</title>
        <meta name="description" :content="props.settings.description_ar || ''" />
        <link v-if="props.settings.favicon" rel="icon" :href="props.settings.favicon" />
    </Head>

    <div
        class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 flex flex-col font-sans"
        dir="rtl"
    >
        <!-- Announcement Bar -->
        <div
            v-if="props.settings.announcement_enabled && props.settings.announcement_text_ar"
            :style="{
                backgroundColor: props.settings.announcement_bg_color || '#2563eb',
                color: props.settings.announcement_text_color || '#ffffff',
            }"
            class="text-xs sm:text-sm py-2 px-4 text-center font-medium shadow-sm transition-all"
        >
            <a
                v-if="props.settings.announcement_url"
                :href="props.settings.announcement_url"
                class="hover:underline inline-flex items-center gap-1.5"
            >
                <span>{{ props.settings.announcement_text_ar }}</span>
                <span aria-hidden="true">&larr;</span>
            </a>
            <span v-else>{{ props.settings.announcement_text_ar }}</span>
        </div>

        <!-- Navigation Header -->
        <header
            class="sticky top-0 z-40 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-colors"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between"
            >
                <div class="flex items-center gap-8">
                    <!-- Brand -->
                    <Link :href="route('home')" class="flex items-center gap-3 group">
                        <img
                            v-if="props.settings.logo"
                            :src="props.settings.logo"
                            :alt="siteTitle"
                            class="h-10 w-auto object-contain transition-transform group-hover:scale-105"
                        />
                        <span v-else class="text-2xl font-black tracking-tight text-action-primary">
                            {{ siteTitle }}
                        </span>
                    </Link>

                    <!-- Header Menu Links -->
                    <nav class="hidden md:flex items-center gap-6">
                        <Link
                            :href="route('home')"
                            class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-action-primary transition-colors"
                        >
                            الرئيسية
                        </Link>
                        <a
                            v-for="(link, i) in props.settings.header_menu || []"
                            :key="i"
                            :href="link.url"
                            class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-action-primary transition-colors"
                        >
                            {{ link.label_ar || link.label_en }}
                        </a>
                    </nav>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-action-primary hover:bg-action-primary-hover rounded-lg shadow-sm transition-all focus:ring-2 focus:ring-focus-ring focus:outline-none"
                    >
                        {{ props.settings.login_button_text_ar || 'دخول النظام' }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Breadcrumbs -->
            <nav
                class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-6"
                aria-label="Breadcrumb"
            >
                <Link :href="route('home')" class="hover:text-action-primary transition-colors">
                    الرئيسية
                </Link>
                <span>/</span>
                <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ pageTitle }}</span>
            </nav>

            <!-- Page Header Card -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-8 sm:p-12 mb-8"
            >
                <h1
                    class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-6 pb-6 border-b border-gray-100 dark:border-gray-800"
                >
                    {{ pageTitle }}
                </h1>

                <!-- Content Body -->
                <div
                    class="prose dark:prose-invert max-w-none text-base sm:text-lg leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-wrap"
                >
                    {{ pageContent }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-12 transition-colors mt-auto"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="text-center md:text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ props.settings.copyright_text_ar || '© جميع الحقوق محفوظة.' }}
                        </p>
                    </div>

                    <!-- Footer Links -->
                    <div class="flex flex-wrap items-center justify-center gap-6">
                        <Link
                            :href="route('home')"
                            class="text-xs text-gray-500 hover:text-action-primary transition-colors"
                        >
                            الرئيسية
                        </Link>
                        <a
                            v-for="(link, i) in props.settings.footer_menu || []"
                            :key="i"
                            :href="link.url"
                            class="text-xs text-gray-500 hover:text-action-primary transition-colors"
                        >
                            {{ link.label_ar || link.label_en }}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
