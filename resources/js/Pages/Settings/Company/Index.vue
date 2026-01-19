<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Back Button & Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Back Button -->
                        <Link href="/app/settings"
                            class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ $t('common.back') }}
                        </Link>

                        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>

                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('company_profile.title')
                            }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('company_profile.subtitle') }}</p>
                        </div>
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8.5 12.5l-2-2 1-1 1 1 3-3 1 1-4 4z" />
                        </svg>
                        {{ $t('company_profile.tenant_level') }}
                    </span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                            activeTab === tab.id
                                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                            'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors'
                        ]">
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Entity Profile Tab -->
                    <div v-if="activeTab === 'profile'" class="space-y-6">
                        <!-- Logo + Company Summary Row -->
                        <div
                            class="flex flex-col md:flex-row items-start gap-6 p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <!-- Logo Section -->
                            <div class="flex items-start gap-4 flex-shrink-0">
                                <div class="relative">
                                    <div v-if="logoPreview || props.profile?.logo_url"
                                        class="w-24 h-24 rounded-xl overflow-hidden bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 shadow-sm">
                                        <img :src="logoPreview || props.profile.logo_url"
                                            class="w-full h-full object-contain"
                                            :alt="$t('company_profile.logo.alt')" />
                                    </div>
                                    <div v-else
                                        class="w-24 h-24 rounded-xl bg-gray-200 dark:bg-gray-700 border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div v-if="uploadingLogo"
                                        class="absolute inset-0 bg-black/50 rounded-xl flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">{{
                                        $t('company_profile.logo.title') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{
                                        $t('company_profile.logo.hint') }}</p>
                                    <div class="flex items-center gap-2">
                                        <label
                                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp"
                                                @change="handleLogoUpload" :disabled="uploadingLogo" />
                                            {{ $t('company_profile.logo.upload') }}
                                        </label>
                                        <button v-if="props.profile?.logo_url || logoPreview" @click="handleLogoDelete"
                                            :disabled="uploadingLogo"
                                            class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
                                            {{ $t('company_profile.logo.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Summary -->
                            <div class="flex-1 w-full md:w-auto">
                                <!-- Company Name -->
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                                    {{ props.profile?.trade_name || props.profile?.legal_name || $t('nav.brand') }}
                                </h3>

                                <!-- Info List -->
                                <div class="space-y-3 text-sm">
                                    <!-- System ID -->
                                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                        <span dir="ltr" class="font-mono">#{{ $page.props.tenant?.id }}</span>
                                    </div>

                                    <!-- CR Number -->
                                    <div v-if="props.profile?.cr_number"
                                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span dir="ltr">{{ props.profile.cr_number }}</span>
                                    </div>

                                    <!-- Owner Name -->
                                    <div v-if="props.profile?.owner_name"
                                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>{{ props.profile.owner_name }}</span>
                                    </div>

                                    <!-- Phone -->
                                    <div v-if="props.contact?.phone"
                                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span dir="ltr">{{ props.contact.phone }}</span>
                                    </div>

                                    <!-- Email -->
                                    <div v-if="props.contact?.email"
                                        class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                        <span dir="ltr">{{ props.contact.email }}</span>
                                    </div>

                                    <!-- Address -->
                                    <div v-if="props.address?.city || props.address?.address_line"
                                        class="flex items-start gap-3 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 flex-shrink-0 text-gray-400 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>
                                            {{ [props.address?.building_number, props.address?.address_line,
                                            props.address?.postal_code, props.address?.city,
                                            props.address?.district].filter(Boolean).join('، ') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('company_profile.profile.legal_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.profile.legal_name" type="text"
                                    :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent', fieldErrors.legal_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.legal_name_en') }} <span
                                        class="text-red-500">*</span></label>
                                <input v-model="form.profile.legal_name_en" type="text" dir="ltr"
                                    :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent', fieldErrors.legal_name_en ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.trade_name') }} <span
                                        class="text-red-500">*</span></label>
                                <input v-model="form.profile.trade_name" type="text"
                                    :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent', fieldErrors.trade_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.owner_name') }} <span
                                        class="text-red-500">*</span></label>
                                <input v-model="form.profile.owner_name" type="text"
                                    :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent', fieldErrors.owner_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.cr_number') }}</label>
                                <input v-model="form.profile.cr_number" type="text" maxlength="20"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.iban') }}</label>
                                <input v-model="form.profile.iban" type="text" maxlength="34" dir="ltr"
                                    placeholder="SA..."
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono" />
                            </div>
                        </div>

                        <!-- Locked Fields -->
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{
                                    $t('company_profile.profile.country') }}</label>
                                <div
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ $t('company_profile.profile.saudi_arabia') }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{
                                    $t('company_profile.profile.currency') }}</label>
                                <div
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    SAR
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveSection('profile')" :disabled="saving"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Contact Tab -->
                    <div v-if="activeTab === 'contact'" class="space-y-6">
                        <!-- Contact Info Header -->
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <h3 class="text-lg font-semibold">{{ $t('company_profile.contact.title') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{
                                    $t('company_profile.contact.phone') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <span class="text-lg">🇸🇦</span>
                                    </div>
                                    <input v-model="form.contact.phone" type="tel" dir="ltr"
                                        placeholder="+966 500 152 900"
                                        class="w-full ps-12 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{
                                    $t('company_profile.contact.email') }}</label>
                                <input v-model="form.contact.email" type="email" dir="ltr"
                                    placeholder="admin@team-group.com"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2 text-gray-900 dark:text-white mb-4">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="text-lg font-semibold">{{ $t('company_profile.profile.address') }}</h3>
                            </div>

                            <!-- Street Name -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{
                                    $t('company_profile.address.street') }}</label>
                                <input v-model="form.address.street" type="text"
                                    :placeholder="$t('company_profile.address.address_placeholder')"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>

                            <!-- Address Line -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{
                                    $t('company_profile.profile.address_line') }}</label>
                                <input v-model="form.address.address_line" type="text"
                                    :placeholder="$t('company_profile.address.address_placeholder')"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <!-- Postal Code -->
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.profile.postal_code') }}</label>
                                    <input v-model="form.address.postal_code" type="text" placeholder="23455"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm" />
                                </div>

                                <!-- Building Number -->
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.profile.building_number') }}</label>
                                    <input v-model="form.address.building_number" type="text" placeholder="8545"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm" />
                                </div>

                                <!-- City -->
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.profile.city') }}</label>
                                    <input v-model="form.address.city" type="text"
                                        :placeholder="$t('company_profile.address.city_placeholder')"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm" />
                                </div>

                                <!-- District -->
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.profile.district') }}</label>
                                    <input v-model="form.address.district" type="text"
                                        :placeholder="$t('company_profile.address.district_placeholder')"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm" />
                                </div>
                            </div>

                            <!-- Country (Locked - Saudi Arabia) -->
                            <div class="mb-4">
                                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">{{
                                    $t('company_profile.profile.country') }}</label>
                                <div
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
                                    <span class="text-lg">🇸🇦</span>
                                    <span>{{ $t('company_profile.profile.saudi_arabia') }}</span>
                                    <svg class="w-4 h-4 ms-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Map Container -->
                            <div ref="mapContainer"
                                class="w-full h-[280px] rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 overflow-hidden">
                                <div v-if="!mapReady"
                                    class="h-full flex flex-col items-center justify-center text-center p-4">
                                    <svg class="w-8 h-8 text-gray-400 mb-2 animate-pulse" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                                {{ $t('company_profile.map.click_hint') }}
                            </p>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveContactAndAddress()" :disabled="saving"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                <span v-if="saving" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    {{ $t('common.loading') }}
                                </span>
                                <span v-else>{{ $t('company_profile.contact.save_button') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- VAT Settings Tab -->
                    <div v-if="activeTab === 'vat'" class="space-y-6">
                        <div
                            class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-700 dark:text-blue-300">{{ $t('company_profile.vat.notice') }}
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{
                                    $t('company_profile.vat.vat_enabled') }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{
                                    $t('company_profile.vat.vat_enabled_hint') }}</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.vat.vat_enabled" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600">
                                </div>
                            </label>
                        </div>

                        <div v-if="form.vat.vat_enabled" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('company_profile.profile.vat_number') }}</label>
                                <input v-model="form.vat.vat_number" type="text" maxlength="15"
                                    @input="form.vat.vat_number = form.vat.vat_number.replace(/[^0-9]/g, '')"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('company_profile.vat.vat_rate') }}</label>
                                    <div class="relative">
                                        <input v-model.number="form.vat.vat_rate" type="number" min="0" max="100"
                                            step="0.01"
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('company_profile.vat.pricing_mode') }}</label>
                                    <select v-model="form.vat.pricing_mode"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="exclusive">{{ $t('company_profile.vat.exclusive') }}</option>
                                        <option value="inclusive">{{ $t('company_profile.vat.inclusive') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>



                        <div class="flex justify-end pt-4">
                            <button @click="saveSection('vat')" :disabled="saving"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- ZATCA Tab -->
                    <div v-if="activeTab === 'zatca'" class="space-y-6">
                        <!-- Phase 1 -->
                        <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{
                                $t('company_profile.zatca.phase1_title') }}</h3>

                            <div v-if="!props.vat?.vat_enabled"
                                class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 mb-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-amber-800 dark:text-amber-200">{{
                                            $t('company_profile.zatca.complete_vat_first') }}</p>
                                        <button @click="activeTab = 'vat'"
                                            class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 underline mt-1">{{
                                                $t('company_profile.zatca.open_vat') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-700"
                                :class="{ 'opacity-50 pointer-events-none': !props.vat?.vat_enabled }">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{
                                        $t('company_profile.zatca.qr_enabled') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{
                                        $t('company_profile.zatca.qr_enabled_hint') }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.zatca.qr_enabled"
                                        :disabled="!props.vat?.vat_enabled" class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600">
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Phase 2 Coming Soon -->
                        <div
                            class="p-6 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                            <div class="text-center">
                                <div
                                    class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{
                                    $t('company_profile.zatca.phase2_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{
                                    $t('company_profile.zatca.phase2_description') }}</p>
                                <button disabled
                                    class="px-6 py-2.5 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-medium rounded-lg cursor-not-allowed">
                                    {{ $t('company_profile.zatca.setup_integration') }}
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveSection('zatca')" :disabled="saving || !props.vat?.vat_enabled"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Numbering Tab -->
                    <div v-if="activeTab === 'numbering'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                $t('company_profile.numbering.format') }}</label>
                            <input v-model="form.numbering.invoice_number_format" type="text"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono" />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{
                                $t('company_profile.numbering.tokens_hint') }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">{{ $t('company_profile.numbering.example') }}:</span>
                                <code
                                    class="mx-2 px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded text-gray-800 dark:text-gray-200">{{ formatExample }}</code>
                            </p>
                        </div>

                        <div
                            class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-700 dark:text-blue-300">{{
                                $t('company_profile.numbering.notice') }}</p>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveSection('numbering')" :disabled="saving"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Admin User Tab -->
                    <div v-if="activeTab === 'admin_user'" class="space-y-6">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <h3 class="text-lg font-semibold">{{ $t('company_profile.admin_user.title') }}</h3>
                        </div>

                        <!-- Admin Name (Display Only) -->
                        <div
                            class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold text-lg">
                                    {{ (props.admin_user?.name || 'U').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ props.admin_user?.name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{
                                        $t('company_profile.admin_user.admin_label') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Email Update -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{
                                $t('company_profile.admin_user.change_email') }}</h4>
                            <div>
                                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                    $t('company_profile.admin_user.email') }}</label>
                                <input v-model="form.admin_user.email" type="email" dir="ltr"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{
                                $t('company_profile.admin_user.change_password') }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.admin_user.current_password') }}</label>
                                    <input v-model="form.admin_user.current_password" type="password"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.admin_user.new_password') }}</label>
                                    <input v-model="form.admin_user.new_password" type="password"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{
                                        $t('company_profile.admin_user.confirm_password') }}</label>
                                    <input v-model="form.admin_user.new_password_confirmation" type="password"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{
                                $t('company_profile.admin_user.password_hint') }}</p>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveAdminUser()" :disabled="saving"
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Subscription Tab (Coming Soon) -->
                    <div v-if="activeTab === 'subscription'" class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{
                            $t('company_profile.tabs.subscription') }}</h3>
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- Subscription History Tab (Coming Soon) -->
                    <div v-if="activeTab === 'subscription_history'" class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{
                            $t('company_profile.tabs.subscription_history') }}</h3>
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- Revenue & Expenses Tab (Coming Soon) -->
                    <div v-if="activeTab === 'revenue_expenses'" class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{
                            $t('company_profile.tabs.revenue_expenses') }}</h3>
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from '@/Composables/useToast';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default marker icon issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const { t, locale } = useI18n();
const { success, error } = useToast();

const props = defineProps({
    profile: Object,
    contact: Object,
    address: Object,
    vat: Object,
    zatca: Object,
    numbering: Object,
    admin_user: Object,
    branches: Array,
});

const activeTab = ref('profile');
const saving = ref(false);
const uploadingLogo = ref(false);
const logoPreview = ref(null);

// Map state
const mapContainer = ref(null);
const mapReady = ref(false);
const geocodingLoading = ref(false);
let map = null;
let marker = null;
const defaultLat = 21.4858; // Jeddah
const defaultLng = 39.1925;

const tabs = computed(() => [
    { id: 'profile', label: t('company_profile.tabs.profile') },
    { id: 'contact', label: t('company_profile.tabs.contact') },
    { id: 'vat', label: t('company_profile.tabs.vat') },
    { id: 'zatca', label: t('company_profile.tabs.zatca') },
    { id: 'numbering', label: t('company_profile.tabs.numbering') },
    { id: 'admin_user', label: t('company_profile.tabs.admin_user') },
    { id: 'subscription', label: t('company_profile.tabs.subscription') },
    { id: 'subscription_history', label: t('company_profile.tabs.subscription_history') },
    { id: 'revenue_expenses', label: t('company_profile.tabs.revenue_expenses') },
]);

// Field errors
const fieldErrors = ref({});

const form = ref({
    profile: {
        legal_name: props.profile?.legal_name ?? '',
        legal_name_en: props.profile?.legal_name_en ?? '',
        trade_name: props.profile?.trade_name ?? '',
        owner_name: props.profile?.owner_name ?? '',

        cr_number: props.profile?.cr_number ?? '',
        iban: props.profile?.iban ?? '',
    },
    contact: {
        phone: props.contact?.phone ?? '',
        email: props.contact?.email ?? '',
    },
    address: {
        address_line: props.address?.address_line ?? '',
        street: props.address?.street ?? '',
        city: props.address?.city ?? '',
        district: props.address?.district ?? '',
        building_number: props.address?.building_number ?? '',
        postal_code: props.address?.postal_code ?? '',
        latitude: props.address?.latitude ?? null,
        longitude: props.address?.longitude ?? null,
    },
    vat: {
        vat_enabled: props.vat?.vat_enabled ?? false,
        vat_rate: props.vat?.vat_rate ?? 15,
        pricing_mode: props.vat?.pricing_mode ?? 'exclusive',
        rounding_mode: props.vat?.rounding_mode ?? 'half_up',
        vat_number: props.profile?.vat_number ?? '',
    },
    zatca: {
        qr_enabled: props.zatca?.qr_enabled ?? false,
    },
    numbering: {
        invoice_number_format: props.numbering?.invoice_number_format ?? 'INV-{CENTER}-{YYYY}-{SEQ}',
    },
    admin_user: {
        email: props.admin_user?.email ?? '',
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    },
});

const formatExample = computed(() => {
    let example = form.value.numbering.invoice_number_format;
    const now = new Date();
    example = example.replace('{SEQ}', '000001');
    example = example.replace('{YYYY}', now.getFullYear().toString());
    example = example.replace('{YY}', now.getFullYear().toString().slice(-2));
    example = example.replace('{MM}', String(now.getMonth() + 1).padStart(2, '0'));
    example = example.replace('{DD}', String(now.getDate()).padStart(2, '0'));
    example = example.replace('{CENTER}', '1');
    return example;
});

function saveSection(section) {
    saving.value = true;
    fieldErrors.value = {}; // Reset errors

    router.put('/app/settings/company', { section, data: form.value[section] }, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
            fieldErrors.value = {};
        },
        onError: (errors) => {
            // Set field errors for red border styling
            fieldErrors.value = errors;

            // Build Arabic error message
            const errorMessages = [];
            for (const [key, value] of Object.entries(errors)) {
                const fieldLabel = t(`company_profile.profile.${key}`) || key;
                errorMessages.push(t('common.validation.required', { field: fieldLabel }));
            }
            error(errorMessages.join('، ') || t('common.validation.required_fields'));
        },
        onFinish: () => saving.value = false,
    });
}

function saveContactAndAddress() {
    saving.value = true;

    // Save contact first, then address
    router.put('/app/settings/company', { section: 'contact', data: form.value.contact }, {
        preserveScroll: true,
        onSuccess: () => {
            router.put('/app/settings/company', { section: 'address', data: form.value.address }, {
                preserveScroll: true,
                onSuccess: () => {
                    success(t('common.saved_success'));
                },
                onError: (errors) => {
                    error(Object.values(errors).flat().join(', '));
                },
                onFinish: () => saving.value = false,
            });
        },
        onError: (errors) => {
            error(Object.values(errors).flat().join(', '));
            saving.value = false;
        },
    });
}

function saveAdminUser() {
    saving.value = true;

    const data = {
        email: form.value.admin_user.email,
    };

    // Only include password fields if new_password is filled
    if (form.value.admin_user.new_password) {
        data.current_password = form.value.admin_user.current_password;
        data.new_password = form.value.admin_user.new_password;
        data.new_password_confirmation = form.value.admin_user.new_password_confirmation;
    }

    router.put('/app/settings/company/admin-user', data, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
            // Clear password fields after successful save
            form.value.admin_user.current_password = '';
            form.value.admin_user.new_password = '';
            form.value.admin_user.new_password_confirmation = '';
        },
        onError: (errors) => {
            error(Object.values(errors).flat().join(', '));
        },
        onFinish: () => saving.value = false,
    });
}

async function handleLogoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        error(t('company_profile.logo.error_size'));
        return;
    }

    // Validate file type
    const validTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        error(t('company_profile.logo.error_type'));
        return;
    }

    uploadingLogo.value = true;

    const formData = new FormData();
    formData.append('logo', file);

    try {
        const response = await fetch('/app/settings/company/logo', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        });

        const data = await response.json();

        if (data.success) {
            logoPreview.value = data.logo_url;
            success(t('company_profile.logo.uploaded'));
            router.reload({ only: ['profile'] });
        } else {
            error(data.message || t('company_profile.logo.error_upload'));
        }
    } catch (err) {
        error(t('company_profile.logo.error_upload'));
    } finally {
        uploadingLogo.value = false;
        event.target.value = '';
    }
}

async function handleLogoDelete() {
    uploadingLogo.value = true;

    try {
        const response = await fetch('/app/settings/company/logo', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Content-Type': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            logoPreview.value = null;
            success(t('company_profile.logo.deleted'));
            router.reload({ only: ['profile'] });
        } else {
            error(data.message || t('company_profile.logo.error_delete'));
        }
    } catch (err) {
        error(t('company_profile.logo.error_delete'));
    } finally {
        uploadingLogo.value = false;
    }
}

// ===== Map Functions =====
function initMap() {
    if (!mapContainer.value || map) return;

    const startLat = form.value.address.lat || defaultLat;
    const startLng = form.value.address.lng || defaultLng;

    map = L.map(mapContainer.value).setView([startLat, startLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add initial marker if we have coordinates
    if (form.value.address.latitude && form.value.address.longitude) {
        marker = L.marker([form.value.address.latitude, form.value.address.longitude], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    // Handle map clicks
    map.on('click', onMapClick);
    mapReady.value = true;
}

function destroyMap() {
    if (map) {
        map.off();
        map.remove();
        map = null;
        marker = null;
        mapReady.value = false;
    }
}

function onMapClick(e) {
    const { lat, lng } = e.latlng;
    setMarker(lat, lng);
}

function onMarkerDrag(e) {
    const { lat, lng } = e.target.getLatLng();
    form.value.address.latitude = parseFloat(lat.toFixed(7));
    form.value.address.longitude = parseFloat(lng.toFixed(7));
    reverseGeocode(form.value.address.latitude, form.value.address.longitude);
}

function setMarker(lat, lng) {
    form.value.address.latitude = parseFloat(lat.toFixed(7));
    form.value.address.longitude = parseFloat(lng.toFixed(7));

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.panTo([lat, lng]);
    reverseGeocode(form.value.address.latitude, form.value.address.longitude);
}

// Reverse geocoding using OpenStreetMap Nominatim
async function reverseGeocode(lat, lng) {
    if (geocodingLoading.value) return;

    geocodingLoading.value = true;

    try {
        const lang = locale.value === 'ar' ? 'ar' : 'en';
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
            {
                headers: {
                    'User-Agent': 'Carag-App/1.0'
                }
            }
        );

        if (!response.ok) throw new Error('Geocoding failed');

        const data = await response.json();

        if (data && data.address) {
            const addr = data.address;
            form.value.address.city = addr.city || addr.town || addr.village || addr.municipality || '';
            form.value.address.district = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || '';
            form.value.address.postal_code = addr.postcode || '';
            form.value.address.building_number = addr.house_number || '';

            // Map street name to street field
            form.value.address.street = addr.road || addr.pedestrian || addr.street || addr.footway || '';
        }

    } catch (err) {
        console.warn('Reverse geocoding failed:', err);
    } finally {
        geocodingLoading.value = false;
    }
}

// Watch for tab changes to init/destroy map
watch(activeTab, async (newTab) => {
    if (newTab === 'contact') {
        await nextTick();
        initMap();
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 300);
    } else {
        destroyMap();
    }
});

// Init map on mount if contact tab is active
onMounted(() => {
    if (activeTab.value === 'contact') {
        nextTick(() => {
            initMap();
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 300);
        });
    }
});

onUnmounted(() => {
    destroyMap();
});
</script>

<style>
/* Leaflet RTL fixes */
.leaflet-container {
    z-index: 0;
}
</style>
