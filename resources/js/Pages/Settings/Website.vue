<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="t('nav.website')"
                :subtitle="t('tenant_site.admin.subtitle')"
                gradientFrom="from-pink-500"
                gradientTo="to-rose-600"
                glowFrom="from-pink-500"
                badgeBg="bg-pink-50/50 dark:bg-pink-900/30"
                badgeText="text-pink-600 dark:text-pink-400"
                badgeBorder="border-pink-100/50 dark:border-pink-800/30"
                badgeDot="bg-pink-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                        />
                    </svg>
                </template>
                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Status indicator badge -->
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                            :class="{
                                'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800':
                                    form.status === 'published',
                                'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800':
                                    form.status === 'draft',
                                'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800':
                                    form.status === 'unpublished',
                            }"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="{
                                    'bg-emerald-500 animate-pulse': form.status === 'published',
                                    'bg-amber-500': form.status === 'draft',
                                    'bg-rose-500': form.status === 'unpublished',
                                }"
                            ></span>
                            {{ t(`tenant_site.admin.status.${form.status}`) }}
                        </span>

                        <!-- Live site preview external link -->
                        <a
                            v-if="publicUrl"
                            :href="publicUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors shadow-sm"
                        >
                            <svg
                                class="w-4 h-4 text-pink-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                />
                            </svg>
                            {{ t('tenant_site.admin.device_preview.visit_live') }}
                        </a>

                        <!-- Save button -->
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-white font-medium text-sm shadow-md transition-all disabled:opacity-60"
                            :style="{ backgroundColor: form.primary_color || '#2563eb' }"
                        >
                            <svg
                                v-if="form.processing"
                                class="w-4 h-4 animate-spin"
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
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
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
                            {{ t('tenant_site.admin.save') }}
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Navigation Tabs -->
            <div
                class="bg-white dark:bg-gray-800 p-2 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-x-auto"
            >
                <nav class="flex gap-2 min-w-max">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        @click="activeTab = tab.id"
                        class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl font-medium text-sm transition-all"
                        :class="
                            activeTab === tab.id
                                ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-md shadow-pink-500/20'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50'
                        "
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        <span>{{ tab.label }}</span>
                        <span
                            v-if="tab.errorCount > 0"
                            class="text-[10px] px-1.5 py-0.5 rounded-full font-bold bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"
                        >
                            {{ tab.errorCount }}
                        </span>
                        <span
                            v-else-if="tab.badge"
                            class="text-[10px] px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider"
                            :class="
                                activeTab === tab.id
                                    ? 'bg-white/20 text-white'
                                    : 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300'
                            "
                        >
                            {{ tab.badge }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content Forms -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 1: BRANDING & IDENTITY -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'branding'" class="space-y-6">
                    <!-- General Details -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.sections.identity') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.site_name') }}
                                </label>
                                <input
                                    v-model="form.site_name"
                                    type="text"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-colors"
                                    :placeholder="w.display_name"
                                />
                                <p v-if="errors.site_name" class="text-xs text-rose-500 mt-1">
                                    {{ errors.site_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.tagline') }}
                                </label>
                                <input
                                    v-model="form.tagline"
                                    type="text"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-colors"
                                    placeholder="أفضل ورشة لصيانة السيارات والميكانيكا بأحدث التقنيات"
                                />
                                <p v-if="errors.tagline" class="text-xs text-rose-500 mt-1">
                                    {{ errors.tagline }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Media: Logo & Cover -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.sections.branding') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Logo Upload -->
                            <div
                                class="p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/50 space-y-3"
                            >
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.logo') }}
                                </label>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="h-20 w-20 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner"
                                    >
                                        <img
                                            v-if="logoPreview"
                                            :src="logoPreview"
                                            class="h-full w-full object-contain p-2"
                                            alt="Logo preview"
                                        />
                                        <svg
                                            v-else
                                            class="w-8 h-8 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="space-y-2 flex-1">
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            @change="onLogoChange"
                                            class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-pink-50 file:text-pink-700 dark:file:bg-pink-900/40 dark:file:text-pink-300 cursor-pointer"
                                        />
                                        <button
                                            v-if="logoPreview"
                                            type="button"
                                            @click="removeImage('logo')"
                                            class="text-xs text-rose-500 hover:text-rose-700 font-medium inline-flex items-center gap-1"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5"
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
                                            {{ t('tenant_site.admin.remove_image') }}
                                        </button>
                                    </div>
                                </div>
                                <p v-if="errors.logo" class="text-xs text-rose-500">
                                    {{ errors.logo }}
                                </p>
                            </div>

                            <!-- Cover Upload -->
                            <div
                                class="p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/50 space-y-3"
                            >
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.cover') }}
                                </label>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="h-20 w-36 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner"
                                    >
                                        <img
                                            v-if="coverPreview"
                                            :src="coverPreview"
                                            class="h-full w-full object-cover"
                                            alt="Cover preview"
                                        />
                                        <svg
                                            v-else
                                            class="w-8 h-8 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="space-y-2 flex-1">
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            @change="onCoverChange"
                                            class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-pink-50 file:text-pink-700 dark:file:bg-pink-900/40 dark:file:text-pink-300 cursor-pointer"
                                        />
                                        <button
                                            v-if="coverPreview"
                                            type="button"
                                            @click="removeImage('cover')"
                                            class="text-xs text-rose-500 hover:text-rose-700 font-medium inline-flex items-center gap-1"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5"
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
                                            {{ t('tenant_site.admin.remove_image') }}
                                        </button>
                                    </div>
                                </div>
                                <p v-if="errors.cover" class="text-xs text-rose-500">
                                    {{ errors.cover }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Colors & Theme Presets -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.fields.primary_color') }} &
                                    {{ t('tenant_site.admin.fields.secondary_color') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    اختر ألوانك يدويًا أو طبق إحدى لوحات الألوان الذكية المخصصة
                                    لمراكز السيارات
                                </p>
                            </div>
                        </div>

                        <!-- Automotive Color Presets -->
                        <div class="space-y-2">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                لوحات الألوان المقترحة:
                            </span>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <button
                                    v-for="(pal, idx) in colorPalettes"
                                    :key="idx"
                                    type="button"
                                    @click="applyPalette(pal)"
                                    class="p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-pink-500 dark:hover:border-pink-500 transition-all text-start flex items-center gap-2.5 bg-gray-50/50 dark:bg-gray-900/50 hover:shadow-sm"
                                >
                                    <div class="flex items-center -space-x-1 rtl:space-x-reverse">
                                        <span
                                            class="w-4 h-4 rounded-full border border-white shadow-sm"
                                            :style="{ backgroundColor: pal.primary }"
                                        ></span>
                                        <span
                                            class="w-4 h-4 rounded-full border border-white shadow-sm"
                                            :style="{ backgroundColor: pal.secondary }"
                                        ></span>
                                    </div>
                                    <span
                                        class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate"
                                    >
                                        {{ pal.name }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <ColorField
                                v-model="form.primary_color"
                                :label="t('tenant_site.admin.fields.primary_color')"
                                :error="errors.primary_color"
                            />
                            <ColorField
                                v-model="form.secondary_color"
                                :label="t('tenant_site.admin.fields.secondary_color')"
                                :error="errors.secondary_color"
                            />
                        </div>

                        <!-- Theme Key -->
                        <div class="pt-2">
                            <label
                                class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                            >
                                {{ t('tenant_site.admin.fields.theme_key') }}
                            </label>
                            <SearchableSelect
                                v-model="form.theme_key"
                                :options="themeSelectOptions"
                                class="w-full max-w-md"
                            />
                        </div>
                    </section>
                </div>

                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 2: MARKETING BANNERS & ANNOUNCEMENTS -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'banners'" class="space-y-6">
                    <!-- Top Announcement Bar -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.announcement.title') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    شريط إعلانات علوي رفيع يظهر في أعلى الموقع لتنبيه العملاء بأوقات
                                    العمل أو العروض السريعة
                                </p>
                            </div>
                            <AppToggle
                                v-model="form.settings.announcement_bar.enabled"
                                color="danger"
                            />
                        </div>

                        <div v-if="form.settings.announcement_bar.enabled" class="space-y-3 pt-2">
                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.announcement.text') }}
                                </label>
                                <input
                                    v-model="form.settings.announcement_bar.text"
                                    type="text"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary transition-colors"
                                    :placeholder="
                                        t('tenant_site.admin.announcement.text_placeholder')
                                    "
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Marketing Banners Slider -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.banners_manager.title') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ t('tenant_site.admin.banners_manager.subtitle') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="addNewBanner"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 hover:bg-pink-100 dark:hover:bg-pink-900/50 font-semibold text-xs transition-colors"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ t('tenant_site.admin.banners_manager.add_banner') }}
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="!form.banners || form.banners.length === 0"
                            class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl"
                        >
                            <svg
                                class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"
                                />
                            </svg>
                            <p
                                class="text-sm font-medium text-gray-600 dark:text-gray-400 max-w-sm mx-auto"
                            >
                                {{ t('tenant_site.admin.banners_manager.no_banners') }}
                            </p>
                            <button
                                type="button"
                                @click="addNewBanner"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-pink-600 text-white font-medium text-xs hover:bg-pink-700 transition-colors shadow-sm"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ t('tenant_site.admin.banners_manager.add_banner') }}
                            </button>
                        </div>

                        <!-- Banners List -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="(banner, index) in form.banners"
                                :key="banner.id || index"
                                class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm transition-all bg-gray-50/30 dark:bg-gray-900/30"
                            >
                                <!-- Banner Header bar -->
                                <div
                                    class="px-5 py-3.5 bg-gray-100/70 dark:bg-gray-750 flex items-center justify-between border-b border-gray-200 dark:border-gray-700"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="w-6 h-6 rounded-lg bg-pink-500/10 text-pink-600 dark:text-pink-400 font-bold text-xs flex items-center justify-center"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <h3
                                            class="font-bold text-sm text-gray-900 dark:text-white truncate max-w-xs"
                                        >
                                            {{ banner.title || 'بنر ترويجي جديد' }}
                                        </h3>
                                        <span
                                            v-if="banner.badge"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300"
                                        >
                                            {{ banner.badge }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Active toggle -->
                                        <AppToggle
                                            v-model="banner.is_active"
                                            size="sm"
                                            color="success"
                                        />

                                        <!-- Delete button -->
                                        <button
                                            type="button"
                                            @click="removeBanner(index)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors"
                                            :title="
                                                t('tenant_site.admin.banners_manager.delete_banner')
                                            "
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Banner Body form -->
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        'tenant_site.admin.banners_manager.banner_badge'
                                                    )
                                                }}
                                            </label>
                                            <input
                                                v-model="banner.badge"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                                placeholder="عرض خاص لفترة محدودة"
                                            />
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        'tenant_site.admin.banners_manager.banner_title'
                                                    )
                                                }}
                                            </label>
                                            <input
                                                v-model="banner.title"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                                placeholder="خصم 20% على فحص وبرمجة كمبيوتر السيارة"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                t(
                                                    'tenant_site.admin.banners_manager.banner_subtitle'
                                                )
                                            }}
                                        </label>
                                        <textarea
                                            v-model="banner.subtitle"
                                            rows="2"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary resize-none"
                                            placeholder="احصل على تقرير فحص تفصيلي شامل لأكثر من 50 نقطة في المركبة مع ضمان شهر كامل"
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t('tenant_site.admin.banners_manager.cta_text')
                                                }}
                                            </label>
                                            <input
                                                v-model="banner.cta_text"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                                placeholder="احجز الآن"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t('tenant_site.admin.banners_manager.cta_link')
                                                }}
                                            </label>
                                            <input
                                                v-model="banner.cta_link"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                                placeholder="#booking أو رابط واتساب"
                                            />
                                        </div>
                                    </div>

                                    <!-- Banner Image & Background Gradient -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                        <!-- Gradient Picker -->
                                        <div>
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        'tenant_site.admin.banners_manager.bg_gradient'
                                                    )
                                                }}
                                            </label>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <button
                                                    v-for="grad in bannerGradients"
                                                    :key="grad.value"
                                                    type="button"
                                                    @click="banner.bg_gradient = grad.value"
                                                    class="h-8 w-14 rounded-lg bg-gradient-to-r transition-all border-2"
                                                    :class="[
                                                        grad.value,
                                                        banner.bg_gradient === grad.value
                                                            ? 'border-pink-500 scale-105 shadow-sm'
                                                            : 'border-transparent opacity-80 hover:opacity-100',
                                                    ]"
                                                    :title="grad.name"
                                                ></button>
                                            </div>
                                        </div>

                                        <!-- Banner Image Upload -->
                                        <div>
                                            <label
                                                class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        'tenant_site.admin.banners_manager.banner_image'
                                                    )
                                                }}
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <img
                                                    v-if="banner.image_url"
                                                    :src="banner.image_url"
                                                    class="h-12 w-20 object-cover rounded-lg border border-gray-200 dark:border-gray-700"
                                                    alt="Banner thumb"
                                                />
                                                <label
                                                    class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                >
                                                    <svg
                                                        class="w-3.5 h-3.5 text-pink-500"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    {{
                                                        t(
                                                            'tenant_site.admin.banners_manager.upload_banner_image'
                                                        )
                                                    }}
                                                    <input
                                                        type="file"
                                                        accept="image/png,image/jpeg,image/webp"
                                                        class="hidden"
                                                        @change="(e) => uploadBannerImg(e, banner)"
                                                    />
                                                </label>
                                                <button
                                                    v-if="banner.image_url || banner.image_path"
                                                    type="button"
                                                    @click="
                                                        banner.image_url = null;
                                                        banner.image_path = null;
                                                    "
                                                    class="text-xs text-rose-500 hover:text-rose-700"
                                                >
                                                    حذف
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 3: SERVICES & PRICING STRATEGY -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'services'" class="space-y-6">
                    <!-- Pricing Mode Settings -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.services_manager.pricing_mode') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    تحكم في سياسة تسعير موقع الورشة، سواء كنت ترغب في عرض الأسعار
                                    بوضوح، كتابة يبدأ من، أو إخفائها واعتماد التسعير عند الفحص
                                </p>
                            </div>
                        </div>

                        <!-- Pricing Mode Radio Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <SelectableCard
                                type="radio"
                                name="form.settings.pricing_mode"
                                v-model="form.settings.pricing_mode"
                                :value="mode.id"
                                v-for="mode in pricingModes"
                                :key="mode.id"
                                class="relative p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                                :class="
                                    form.settings.pricing_mode === mode.id
                                        ? 'border-pink-500 bg-pink-50/40 dark:bg-pink-900/20 text-pink-950 dark:text-pink-100'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-600'
                                "
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold">{{ mode.title }}</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ mode.desc }}
                                </p>
                                <div
                                    class="mt-3 text-[11px] font-mono px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300"
                                >
                                    مثال: {{ mode.example }}
                                </div>
                            </SelectableCard>
                        </div>
                    </section>

                    <!-- Services Display & Filtering -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.services_manager.title') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    حدد ما إذا كنت تريد عرض كل الخدمات أو اختيار خدمات محددة فقط
                                    للعرض في الموقع
                                </p>
                            </div>

                            <!-- Display Mode Toggle -->
                            <div
                                class="inline-flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1 border border-gray-200 dark:border-gray-700"
                            >
                                <button
                                    type="button"
                                    @click="form.settings.services_mode = 'all'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                                    :class="
                                        form.settings.services_mode === 'all'
                                            ? 'bg-white dark:bg-gray-800 text-pink-600 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    "
                                >
                                    {{ t('tenant_site.admin.services_manager.all_services') }}
                                </button>
                                <button
                                    type="button"
                                    @click="form.settings.services_mode = 'selected'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                                    :class="
                                        form.settings.services_mode === 'selected'
                                            ? 'bg-white dark:bg-gray-800 text-pink-600 shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    "
                                >
                                    {{ t('tenant_site.admin.services_manager.selected_only') }}
                                </button>
                            </div>
                        </div>

                        <!-- Services Filter & Search -->
                        <div class="flex items-center gap-3">
                            <div class="relative flex-1">
                                <svg
                                    class="w-4 h-4 text-gray-400 absolute start-3 top-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                                <input
                                    v-model="serviceSearch"
                                    type="text"
                                    class="w-full ps-9 pe-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    :placeholder="
                                        t('tenant_site.admin.services_manager.search_services')
                                    "
                                />
                            </div>
                            <span class="text-xs text-gray-500 shrink-0">
                                إجمالي الخدمات: {{ tenantServicesList.length }}
                            </span>
                        </div>

                        <!-- Services List Table -->
                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden max-h-96 overflow-y-auto"
                        >
                            <DataTable structured class="w-full text-xs text-start">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 sticky top-0"
                                >
                                    <tr>
                                        <th
                                            class="py-2.5 px-4 text-start font-semibold text-gray-600 dark:text-gray-300 w-12"
                                        >
                                            <span v-if="form.settings.services_mode === 'selected'">
                                                عرض
                                            </span>
                                            <span v-else>#</span>
                                        </th>
                                        <th
                                            class="py-2.5 px-4 text-start font-semibold text-gray-600 dark:text-gray-300"
                                        >
                                            الخدمة
                                        </th>
                                        <th
                                            class="py-2.5 px-4 text-start font-semibold text-gray-600 dark:text-gray-300"
                                        >
                                            السعر الأساسي
                                        </th>
                                        <th
                                            class="py-2.5 px-4 text-center font-semibold text-gray-600 dark:text-gray-300 w-28"
                                        >
                                            خدمة مميزة ⭐
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr
                                        v-for="svc in filteredServices"
                                        :key="svc.id"
                                        class="hover:bg-gray-50/70 dark:hover:bg-gray-900/40 transition-colors"
                                    >
                                        <td class="py-2.5 px-4">
                                            <AppToggle
                                                v-if="form.settings.services_mode === 'selected'"
                                                :checked="isServiceSelected(svc.id)"
                                                @change="toggleServiceSelected(svc.id)"
                                            />
                                            <span
                                                v-else
                                                class="text-gray-400 font-mono text-[11px]"
                                            >
                                                {{ svc.id }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-2.5 px-4 font-medium text-gray-900 dark:text-white"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span>{{ svc.name_ar || svc.name }}</span>
                                                <span
                                                    v-if="svc.name_en"
                                                    class="text-gray-400 text-[10px]"
                                                >
                                                    ({{ svc.name_en }})
                                                </span>
                                            </div>
                                        </td>
                                        <td
                                            class="py-2.5 px-4 font-mono text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                svc.base_price > 0
                                                    ? `${svc.base_price} ر.س`
                                                    : 'غير محدد'
                                            }}
                                        </td>
                                        <td class="py-2.5 px-4 text-center">
                                            <button
                                                type="button"
                                                @click="toggleServiceFeatured(svc.id)"
                                                class="px-2.5 py-1 rounded-full text-[11px] font-medium transition-all"
                                                :class="
                                                    isServiceFeatured(svc.id)
                                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700'
                                                        : 'text-gray-400 hover:text-amber-600 hover:bg-gray-100 dark:hover:bg-gray-800'
                                                "
                                            >
                                                {{ isServiceFeatured(svc.id) ? '⭐ مميزة' : '—' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredServices.length === 0">
                                        <td colspan="4" class="py-8 text-center text-gray-400">
                                            {{
                                                t(
                                                    'tenant_site.admin.services_manager.no_services_found'
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </section>
                </div>

                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 4: BRANCHES & CONTACT -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'contact'" class="space-y-6">
                    <!-- Channels -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.sections.contact') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.contact_phone') }}
                                </label>
                                <input
                                    v-model="form.contact_phone"
                                    type="text"
                                    dir="ltr"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    placeholder="0501234567"
                                />
                                <p v-if="errors.contact_phone" class="text-xs text-rose-500 mt-1">
                                    {{ errors.contact_phone }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.contact_whatsapp') }}
                                </label>
                                <input
                                    v-model="form.contact_whatsapp"
                                    type="text"
                                    dir="ltr"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    placeholder="966501234567"
                                />
                                <p
                                    v-if="errors.contact_whatsapp"
                                    class="text-xs text-rose-500 mt-1"
                                >
                                    {{ errors.contact_whatsapp }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.contact_email') }}
                                </label>
                                <input
                                    v-model="form.contact_email"
                                    type="email"
                                    dir="ltr"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    placeholder="info@workshop.com"
                                />
                                <p v-if="errors.contact_email" class="text-xs text-rose-500 mt-1">
                                    {{ errors.contact_email }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                            >
                                {{ t('tenant_site.admin.fields.address') }}
                            </label>
                            <input
                                v-model="form.address_line"
                                type="text"
                                class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                placeholder="الرياض — صناعية أم الحمام — شارع الورش الرئيسي"
                            />
                            <p v-if="errors.address_line" class="text-xs text-rose-500 mt-1">
                                {{ errors.address_line }}
                            </p>
                        </div>
                    </section>

                    <!-- Social Links -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.fields.social_links') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(label, key) in socialLabels" :key="key">
                                <label
                                    class="block text-xs font-semibold mb-1 text-gray-600 dark:text-gray-300"
                                >
                                    {{ label }}
                                </label>
                                <input
                                    v-model="form.social_links[key]"
                                    type="url"
                                    dir="ltr"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    placeholder="https://..."
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 5: CUSTOM DOMAIN & SEO -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'domain_seo'" class="space-y-6">
                    <!-- Custom Domain -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2
                                        class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                    >
                                        <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                        {{ t('tenant_site.admin.custom_domain.title') }}
                                    </h2>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gradient-to-r from-amber-500 to-rose-600 text-white uppercase tracking-wider"
                                    >
                                        {{ t('tenant_site.admin.custom_domain.badge_pro') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ t('tenant_site.admin.custom_domain.subtitle') }}
                                </p>
                            </div>

                            <!-- Domain Status -->
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400':
                                        form.custom_domain &&
                                        form.custom_domain_status === 'active',
                                    'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400':
                                        form.custom_domain &&
                                        form.custom_domain_status !== 'active',
                                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400':
                                        !form.custom_domain,
                                }"
                            >
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="{
                                        'bg-emerald-500':
                                            form.custom_domain &&
                                            form.custom_domain_status === 'active',
                                        'bg-amber-500':
                                            form.custom_domain &&
                                            form.custom_domain_status !== 'active',
                                        'bg-gray-400': !form.custom_domain,
                                    }"
                                ></span>
                                {{
                                    form.custom_domain
                                        ? form.custom_domain_status === 'active'
                                            ? t('tenant_site.admin.custom_domain.status_active')
                                            : t('tenant_site.admin.custom_domain.status_pending')
                                        : t('tenant_site.admin.custom_domain.status_none')
                                }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.custom_domain.input_label') }}
                                </label>
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-1">
                                        <span
                                            class="absolute start-3 top-2.5 text-gray-400 text-sm font-mono"
                                        >
                                            https://
                                        </span>
                                        <input
                                            v-model="form.custom_domain"
                                            type="text"
                                            dir="ltr"
                                            class="w-full ps-20 pe-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm font-mono focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                            :placeholder="
                                                t(
                                                    'tenant_site.admin.custom_domain.input_placeholder'
                                                )
                                            "
                                        />
                                    </div>
                                </div>
                                <p v-if="errors.custom_domain" class="text-xs text-rose-500 mt-1">
                                    {{ errors.custom_domain }}
                                </p>
                            </div>

                            <!-- DNS Instruction Card -->
                            <div
                                class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 space-y-3"
                            >
                                <h3
                                    class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2"
                                >
                                    <svg
                                        class="w-4 h-4 text-pink-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    {{
                                        t('tenant_site.admin.custom_domain.dns_instructions_title')
                                    }}
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                    {{ t('tenant_site.admin.custom_domain.dns_cname') }}
                                </p>
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs font-mono"
                                >
                                    <div
                                        class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
                                    >
                                        <span class="text-gray-400 block text-[10px]">Type</span>
                                        <span class="font-bold text-pink-600">CNAME</span>
                                    </div>
                                    <div
                                        class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
                                    >
                                        <span class="text-gray-400 block text-[10px]">
                                            Host / Name
                                        </span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">
                                            @ أو www
                                        </span>
                                    </div>
                                    <div
                                        class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
                                    >
                                        <span class="text-gray-400 block text-[10px]">
                                            Points to / Target
                                        </span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">
                                            sites.carag.app
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- SEO & Search Preview -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.sections.seo') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.seo_title') }}
                                </label>
                                <input
                                    v-model="form.seo_title"
                                    type="text"
                                    maxlength="70"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    :placeholder="form.site_name || w.display_name"
                                />
                                <span class="text-[11px] text-gray-400 mt-1 block">
                                    {{ form.seo_title.length }}/70 حرف
                                </span>
                                <p v-if="errors.seo_title" class="text-xs text-rose-500 mt-1">
                                    {{ errors.seo_title }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ t('tenant_site.admin.fields.seo_description') }}
                                </label>
                                <textarea
                                    v-model="form.seo_description"
                                    rows="3"
                                    maxlength="200"
                                    class="w-full px-3.5 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary resize-none"
                                    :placeholder="
                                        form.tagline || 'مركز متخصص في صيانة وفحص وبرمجة السيارات'
                                    "
                                ></textarea>
                                <span class="text-[11px] text-gray-400 block">
                                    {{ form.seo_description.length }}/200 حرف
                                </span>
                                <p v-if="errors.seo_description" class="text-xs text-rose-500 mt-1">
                                    {{ errors.seo_description }}
                                </p>
                            </div>
                        </div>

                        <!-- Google SERP Snippet Preview -->
                        <div
                            class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 space-y-1"
                        >
                            <span
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1"
                            >
                                معاينة نتيجة البحث في Google:
                            </span>
                            <div
                                class="text-xs text-emerald-700 dark:text-emerald-400 font-mono truncate"
                                dir="ltr"
                            >
                                {{
                                    form.custom_domain ||
                                    `https://carag.app/site/${w.tenant_slug || 'workshop'}`
                                }}
                            </div>
                            <div
                                class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
                            >
                                {{ form.seo_title || form.site_name || w.display_name }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{
                                    form.seo_description ||
                                    form.tagline ||
                                    'أفضل ورشة لصيانة السيارات والميكانيكا بأحدث التقنيات وبأعلى معايير الجودة.'
                                }}
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <!-- TAB 6: TRUST BADGES & PORTAL CONTROLS -->
                <!-- ═════════════════════════════════════════════════════════════════════════════ -->
                <div v-show="activeTab === 'features'" class="space-y-6">
                    <!-- Trust Badges -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <div>
                                <h2
                                    class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                    {{ t('tenant_site.admin.features_manager.title') }}
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ t('tenant_site.admin.features_manager.subtitle') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                v-for="(badge, bIdx) in form.settings.features_badges"
                                :key="bIdx"
                                class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-pink-600"
                                >
                                    <span>الميزة #{{ bIdx + 1 }}</span>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ t('tenant_site.admin.features_manager.badge_title') }}
                                    </label>
                                    <input
                                        v-model="badge.title"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ t('tenant_site.admin.features_manager.badge_desc') }}
                                    </label>
                                    <textarea
                                        v-model="badge.desc"
                                        rows="2"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-xs focus:ring-2 focus:ring-focus-ring/20 focus:border-action-primary resize-none"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Interactive Features Toggles -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                بوابات تفاعل العميل على الموقع
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <!-- Work order tracking portal toggle -->
                            <div
                                class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                            >
                                <div>
                                    <span
                                        class="text-sm font-bold text-gray-900 dark:text-white block"
                                    >
                                        {{
                                            t('tenant_site.admin.features_manager.portal_tracking')
                                        }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{
                                            t(
                                                'tenant_site.admin.features_manager.portal_tracking_desc'
                                            )
                                        }}
                                    </span>
                                </div>
                                <AppToggle
                                    v-model="form.settings.enable_portal_lookup"
                                    color="danger"
                                />
                            </div>

                            <!-- Online booking toggle -->
                            <div
                                class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                            >
                                <div>
                                    <span
                                        class="text-sm font-bold text-gray-900 dark:text-white block"
                                    >
                                        {{ t('tenant_site.admin.features_manager.online_booking') }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        إظهار زر حجز الموعد المباشر في هيدر الموقع وكروت الخدمات
                                    </span>
                                </div>
                                <AppToggle
                                    v-model="form.settings.enable_online_booking"
                                    color="danger"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Publication Status Settings -->
                    <section
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <h2
                                class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                {{ t('tenant_site.admin.sections.publish') }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <SelectableCard
                                type="radio"
                                name="form.status"
                                v-model="form.status"
                                :value="opt.value"
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-center justify-between"
                                :class="
                                    form.status === opt.value
                                        ? 'border-pink-500 bg-pink-50/30 dark:bg-pink-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                "
                            >
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ opt.label }}
                                </span>
                            </SelectableCard>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ t('tenant_site.admin.fields.status_help') }}
                        </p>
                    </section>
                </div>

                <!-- Bottom Sticky Save Bar -->
                <div
                    class="sticky bottom-4 z-30 p-4 rounded-2xl bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-gray-200 dark:border-gray-700 shadow-xl flex items-center justify-between gap-4 flex-wrap"
                >
                    <div class="flex items-center gap-3">
                        <span
                            v-if="totalErrorsCount > 0"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50"
                        >
                            <svg
                                class="w-4 h-4 text-red-500 animate-pulse"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            {{ $t('common.form_has_errors', { count: totalErrorsCount }) }}
                        </span>
                        <div
                            v-else
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>
                                {{
                                    t(
                                        'tenant_site.admin.auto_apply_hint',
                                        'جميع التعديلات يتم حفظها وتطبيقها فوراً على الموقع العام'
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="form.reset()"
                            :disabled="form.processing || !form.isDirty"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all disabled:opacity-40"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-white font-semibold text-sm shadow-md transition-all disabled:opacity-60 active:scale-95"
                            :style="{ backgroundColor: form.primary_color || '#2563eb' }"
                        >
                            <svg
                                v-if="form.processing"
                                class="w-4 h-4 animate-spin"
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
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
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
                            {{
                                form.processing ? $t('common.saving') : t('tenant_site.admin.save')
                            }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ColorField from '@/Components/ColorField.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
import { usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, h } from 'vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    website: { type: Object, default: () => ({}) },
    theme_options: { type: Array, default: () => [] },
    tenant_services: { type: Array, default: () => [] },
});

const w = props.website ?? {};
const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => (page.props.locale || 'ar') === 'ar');
const t = tRaw;

// Tab navigation state
const activeTab = ref('branding');

// Icons as render functions to avoid extra files
const IconPaint = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M7 21a4 4 0 01-4-4 5 5 0 015-5h4a5 5 0 015 5 4 4 0 01-4 4H7z',
        }),
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M16 11V7a4 4 0 00-8 0v4',
        }),
    ]);

const IconSpeaker = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 8a3 3 0 010 6M21 6a6 6 0 010 10',
        }),
    ]);

const IconWrench = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        }),
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        }),
    ]);

const IconBuilding = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        }),
    ]);

const IconGlobe = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        }),
    ]);

const IconStar = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            strokeLinecap: 'round',
            strokeLinejoin: 'round',
            strokeWidth: '2',
            d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        }),
    ]);

const { scrollToError } = useFormScrollToError();

const websiteTabMap = {
    site_name: 'branding',
    headline: 'branding',
    subheadline: 'branding',
    about_text: 'branding',
    primary_color: 'branding',
    theme: 'branding',
    banners: 'banners',
    selected_service_ids: 'services',
    featured_service_ids: 'services',
    phone: 'contact',
    whatsapp: 'contact',
    email: 'contact',
    address: 'contact',
    google_maps_url: 'contact',
    instagram_url: 'contact',
    twitter_url: 'contact',
    facebook_url: 'contact',
    tiktok_url: 'contact',
    working_hours: 'contact',
    custom_domain: 'domain_seo',
    meta_title: 'domain_seo',
    meta_description: 'domain_seo',
    meta_keywords: 'domain_seo',
};

const tabErrorCount = (tabId) => {
    return Object.keys(form.errors).filter((field) => websiteTabMap[field] === tabId).length;
};

const totalErrorsCount = computed(() => Object.keys(form.errors).length);

const tabs = computed(() => [
    {
        id: 'branding',
        label: t('tenant_site.admin.tabs.branding'),
        icon: IconPaint,
        errorCount: tabErrorCount('branding'),
    },
    {
        id: 'banners',
        label: t('tenant_site.admin.tabs.banners'),
        icon: IconSpeaker,
        badge: form.banners?.length ? String(form.banners.length) : null,
        errorCount: tabErrorCount('banners'),
    },
    {
        id: 'services',
        label: t('tenant_site.admin.tabs.services'),
        icon: IconWrench,
        errorCount: tabErrorCount('services'),
    },
    {
        id: 'contact',
        label: t('tenant_site.admin.tabs.contact'),
        icon: IconBuilding,
        errorCount: tabErrorCount('contact'),
    },
    {
        id: 'domain_seo',
        label: t('tenant_site.admin.tabs.domain_seo'),
        icon: IconGlobe,
        badge: form.custom_domain ? 'مربوط' : null,
        errorCount: tabErrorCount('domain_seo'),
    },
    {
        id: 'features',
        label: t('tenant_site.admin.tabs.features'),
        icon: IconStar,
        errorCount: tabErrorCount('features'),
    },
]);

const themeOptions = computed(() => props.theme_options);
const themeSelectOptions = computed(() =>
    themeOptions.value.map((opt) => ({
        value: opt.value,
        label: isRtl.value ? opt.label_ar : opt.label_en,
    }))
);

const statusOptions = computed(() => [
    { value: 'draft', label: t('tenant_site.admin.status.draft') },
    { value: 'published', label: t('tenant_site.admin.status.published') },
    { value: 'unpublished', label: t('tenant_site.admin.status.unpublished') },
]);

const socialLabels = {
    facebook: 'Facebook',
    twitter: 'X (Twitter)',
    instagram: 'Instagram',
    linkedin: 'LinkedIn',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};

// Automotive Color Presets
const colorPalettes = [
    { name: 'كحلي احترافي (Navy Pro)', primary: '#1e3a8a', secondary: '#3b82f6' },
    { name: 'أحمر سباقات (Racing Red)', primary: '#dc2626', secondary: '#f97316' },
    { name: 'رمادي كربوني (Dark Carbon)', primary: '#0f172a', secondary: '#475569' },
    { name: 'أخضر زمردي (Emerald Auto)', primary: '#065f46', secondary: '#10b981' },
];

function applyPalette(pal) {
    form.primary_color = pal.primary;
    form.secondary_color = pal.secondary;
}

// Banner background gradient choices
const bannerGradients = [
    { name: 'بنفسجي ملكي', value: 'from-action-primary to-blue-700' },
    { name: 'أحمر ناري', value: 'from-rose-600 to-amber-600' },
    { name: 'زمردي عميق', value: 'from-emerald-600 to-teal-800' },
    { name: 'كربوني داكن', value: 'from-slate-900 to-slate-700' },
    { name: 'وردي فخم', value: 'from-pink-600 to-rose-700' },
];

// Pricing mode choices
const pricingModes = computed(() => [
    {
        id: 'starting_from',
        title: t('tenant_site.admin.services_manager.pricing_modes.starting_from'),
        desc: 'يظهر للعميل الحد الأدنى للسعر لإعطاء فكرة تقريبية دون إلزام.',
        example: 'يبدأ من 150 ر.س',
    },
    {
        id: 'upon_inspection',
        title: t('tenant_site.admin.services_manager.pricing_modes.upon_inspection'),
        desc: 'الخيار الأنسب للورش والميكانيكا الدقيقة التي تعتمد على المعاينة.',
        example: 'السعر عند الفحص',
    },
    {
        id: 'exact',
        title: t('tenant_site.admin.services_manager.pricing_modes.exact'),
        desc: 'عرض السعر الدقيق والكامل للخدمة المسجلة بالنظام.',
        example: '250 ر.س',
    },
    {
        id: 'hidden',
        title: t('tenant_site.admin.services_manager.pricing_modes.hidden'),
        desc: 'إخفاء عمود الأسعار بالكامل والاكتفاء باسم الخدمة ووصفها.',
        example: '—',
    },
]);

// Services and Search
const tenantServicesList = computed(() => props.tenant_services || []);
const serviceSearch = ref('');
const filteredServices = computed(() => {
    const q = serviceSearch.value.trim().toLowerCase();
    if (!q) return tenantServicesList.value;
    return tenantServicesList.value.filter((s) => {
        const ar = (s.name_ar || '').toLowerCase();
        const en = (s.name_en || '').toLowerCase();
        const gen = (s.name || '').toLowerCase();
        return ar.includes(q) || en.includes(q) || gen.includes(q);
    });
});

// Initial settings object
const initialSettings = w.settings || {};

const form = useForm({
    site_name: w.site_name || '',
    tagline: w.tagline || '',
    contact_phone: w.contact_phone || '',
    contact_whatsapp: w.contact_whatsapp || '',
    contact_email: w.contact_email || '',
    address_line: w.address_line || '',
    social_links: {
        facebook: w.social_links?.facebook || '',
        twitter: w.social_links?.twitter || '',
        instagram: w.social_links?.instagram || '',
        linkedin: w.social_links?.linkedin || '',
        tiktok: w.social_links?.tiktok || '',
        youtube: w.social_links?.youtube || '',
    },
    primary_color: w.primary_color || '#2563eb',
    secondary_color: w.secondary_color || '#0ea5e9',
    status: w.status || 'draft',
    seo_title: w.seo_title || '',
    seo_description: w.seo_description || '',
    theme_key: w.theme_key || 'default',
    custom_domain: w.custom_domain || '',
    custom_domain_status: w.custom_domain_status || 'none',
    banners: Array.isArray(w.banners) ? JSON.parse(JSON.stringify(w.banners)) : [],
    settings: {
        pricing_mode: initialSettings.pricing_mode || 'upon_inspection',
        show_prices: initialSettings.show_prices ?? false,
        services_mode: initialSettings.services_mode || 'all',
        selected_service_ids: Array.isArray(initialSettings.selected_service_ids)
            ? [...initialSettings.selected_service_ids]
            : [],
        featured_service_ids: Array.isArray(initialSettings.featured_service_ids)
            ? [...initialSettings.featured_service_ids]
            : [],
        announcement_bar: {
            enabled: Boolean(initialSettings.announcement_bar?.enabled),
            text: initialSettings.announcement_bar?.text || '',
            link: initialSettings.announcement_bar?.link || '',
        },
        features_badges:
            Array.isArray(initialSettings.features_badges) && initialSettings.features_badges.length
                ? JSON.parse(JSON.stringify(initialSettings.features_badges))
                : [
                      {
                          icon: 'shield-check',
                          title: 'ضمان معتمد',
                          desc: 'ضمان شامل على قطع الغيار وأجور اليد لراحة بالك',
                      },
                      {
                          icon: 'wrench',
                          title: 'فنيون متخصصون',
                          desc: 'فريق معتمد بأحدث أجهزة الفحص والبرمجة الإلكترونية',
                      },
                      {
                          icon: 'clock',
                          title: 'دقة في المواعيد',
                          desc: 'التزام تام بأوقات التسليم ومتابعة لحظية لحالة الصيانة',
                      },
                  ],
        enable_portal_lookup: initialSettings.enable_portal_lookup ?? true,
        enable_online_booking: initialSettings.enable_online_booking ?? true,
    },
    logo: null,
    cover: null,
});

const errors = form.errors;

const logoPreview = ref(w.logo_url || null);
const coverPreview = ref(w.cover_url || null);

function onLogoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    form.logo = file;
    const reader = new FileReader();
    reader.onload = (ev) => (logoPreview.value = ev.target.result);
    reader.readAsDataURL(file);
}

function onCoverChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    form.cover = file;
    const reader = new FileReader();
    reader.onload = (ev) => (coverPreview.value = ev.target.result);
    reader.readAsDataURL(file);
}

async function removeImage(key) {
    if (!confirm(t('tenant_site.admin.confirm_remove_image'))) return;
    try {
        const res = await fetch(route('settings.website.image.delete'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ key }),
        });
        if (res.ok) {
            if (key === 'logo') {
                logoPreview.value = null;
                form.logo = null;
            } else {
                coverPreview.value = null;
                form.cover = null;
            }
        }
    } catch (e) {
        // ignore
    }
}

// Banners management
function addNewBanner() {
    if (!Array.isArray(form.banners)) form.banners = [];
    form.banners.unshift({
        id: `ban_${Date.now()}`,
        badge: 'عرض جديد',
        title: 'عرض صيانة خاص لعملائنا الكرام',
        subtitle: 'احجز موعدك الآن واحصل على فحص مجاني للمكيف ونقاط السلامة',
        cta_text: 'احجز الآن',
        cta_link: '#booking',
        image_path: null,
        image_url: null,
        bg_gradient: 'from-action-primary to-blue-700',
        is_active: true,
        sort_order: form.banners.length + 1,
    });
}

function removeBanner(index) {
    form.banners.splice(index, 1);
}

async function uploadBannerImg(e, banner) {
    const file = e.target.files?.[0];
    if (!file) return;

    const fd = new FormData();
    fd.append('image', file);

    try {
        const res = await fetch(route('settings.website.banner.upload'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '',
                Accept: 'application/json',
            },
            body: fd,
        });

        const data = await res.json();
        if (data.success) {
            banner.image_path = data.image_path;
            banner.image_url = data.image_url;
        }
    } catch (err) {
        console.error('Failed to upload banner image', err);
    }
}

// Services selection helpers
function isServiceSelected(id) {
    return form.settings.selected_service_ids.includes(id);
}

function toggleServiceSelected(id) {
    const arr = form.settings.selected_service_ids;
    const idx = arr.indexOf(id);
    if (idx > -1) {
        arr.splice(idx, 1);
    } else {
        arr.push(id);
    }
}

function isServiceFeatured(id) {
    return form.settings.featured_service_ids.includes(id);
}

function toggleServiceFeatured(id) {
    const arr = form.settings.featured_service_ids;
    const idx = arr.indexOf(id);
    if (idx > -1) {
        arr.splice(idx, 1);
    } else {
        arr.push(id);
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('settings.website.update'), {
        preserveScroll: true,
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: websiteTabMap,
                onSwitchTab: (tabId) => {
                    activeTab.value = tabId;
                },
            });
        },
    });
}

const publicUrl = computed(() => {
    return w.tenant_slug ? `/${w.tenant_slug}` : null;
});
</script>
