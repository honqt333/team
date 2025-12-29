<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Back Button & Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Back Button -->
                        <Link
                            href="/app/settings/branches"
                            class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                        >
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            {{ $t('common.back') }}
                        </Link>
                        
                        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>
                        
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <!-- Commercial Name -->
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ $page.props.tenant?.trade_name || $page.props.tenant?.name }}</p>
                            
                            <!-- Center Name -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-0.5">{{ props.center?.name }}</h1>
                            
                            <!-- Center Type -->
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2">{{ props.profile?.center_type || '#' }}</p>

                            <!-- Manager Name -->
                            <div v-if="props.profile?.manager_name" class="flex items-center gap-2 mb-3 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>{{ props.profile.manager_name }}</span>
                            </div>

                            <!-- Contact Info -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <span v-if="props.contact?.phone" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span dir="ltr">{{ props.contact?.phone }}</span>
                                </span>
                                <span v-if="props.contact?.email" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span>{{ props.contact?.email }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Center ID -->
                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg text-sm font-mono">
                        #{{ props.center?.id }}
                    </span>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                    <nav class="flex px-6">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Profile Tab -->
                    <div v-if="activeTab === 'profile'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('center_settings.profile.name_ar') }} <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.profile.name_ar" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.profile.name_en') }}</label>
                                <input v-model="form.profile.name_en" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.profile.manager_name') }}</label>
                                <input v-model="form.profile.manager_name" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.profile.center_type') }}</label>
                                <input v-model="form.profile.center_type" type="text" :placeholder="$t('center_settings.profile.center_type_placeholder')" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.profile.license_number') }}</label>
                                <input v-model="form.profile.license_number" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('center_settings.profile.vat_number') }}
                                    <span class="text-xs text-gray-400 font-normal">({{ $t('common.read_only') }})</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        :value="props.profile.vat_number" 
                                        disabled
                                        type="text" 
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed" 
                                        dir="ltr"
                                    />
                                    <div class="absolute inset-y-0 rtl:right-auto rtl:left-0 ltr:right-0 flex items-center px-3 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">{{ $t('center_settings.profile.vat_help') }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveSection('profile')" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Contact Tab -->
                    <div v-if="activeTab === 'contact'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.contact.phone') }}</label>
                                <input v-model="form.contact.phone" type="tel" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.contact.email') }}</label>
                                <input v-model="form.contact.email" type="email" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                        </div>

                        <!-- Address Section -->
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-4">{{ $t('center_settings.address.title') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.street') }}</label>
                                <input v-model="form.address.street" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.address_line') }}</label>
                                <input v-model="form.address.address_line" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.city') }}</label>
                                <input v-model="form.address.city" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.district') }}</label>
                                <input v-model="form.address.district" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.building_number') }}</label>
                                <input v-model="form.address.building_number" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('center_settings.address.postal_code') }}</label>
                                <input v-model="form.address.postal_code" type="text" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" />
                            </div>
                        </div>

                        <!-- Map Section -->
                        <div class="pt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $t('company_profile.map.click_hint') }}</p>
                            <div ref="mapContainer" class="w-full h-64 rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden"></div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveContactAndAddress()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Branding Tab -->
                    <div v-if="activeTab === 'branding'" class="space-y-6">
                        <!-- Logo Light Mode -->
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden">
                                    <img v-if="props.branding?.logo_light_url" :src="props.branding.logo_light_url" class="w-full h-full object-contain" />
                                    <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $t('center_settings.branding.logo_light') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $t('center_settings.branding.logo_light_hint') }}</p>
                                    <div class="flex items-center gap-2">
                                        <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="(e) => handleLogoUpload(e, 'light')" />
                                            {{ $t('company_profile.logo.upload') }}
                                        </label>
                                        <button v-if="props.branding?.logo_light_url" @click="handleLogoDelete('light')" class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 text-sm font-medium rounded-lg transition-colors">
                                            {{ $t('company_profile.logo.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Dark Mode -->
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 rounded-lg bg-gray-800 dark:bg-gray-900 border border-gray-600 flex items-center justify-center overflow-hidden">
                                    <img v-if="props.branding?.logo_dark_url" :src="props.branding.logo_dark_url" class="w-full h-full object-contain" />
                                    <svg v-else class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $t('center_settings.branding.logo_dark') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $t('center_settings.branding.logo_dark_hint') }}</p>
                                    <div class="flex items-center gap-2">
                                        <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="(e) => handleLogoUpload(e, 'dark')" />
                                            {{ $t('company_profile.logo.upload') }}
                                        </label>
                                        <button v-if="props.branding?.logo_dark_url" @click="handleLogoDelete('dark')" class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 text-sm font-medium rounded-lg transition-colors">
                                            {{ $t('company_profile.logo.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Invoice -->
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden">
                                    <img v-if="props.branding?.logo_invoice_url" :src="props.branding.logo_invoice_url" class="w-full h-full object-contain" />
                                    <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $t('center_settings.branding.logo_invoice') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $t('center_settings.branding.logo_invoice_hint') }}</p>
                                    <div class="flex items-center gap-2">
                                        <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="(e) => handleLogoUpload(e, 'invoice')" />
                                            {{ $t('company_profile.logo.upload') }}
                                        </label>
                                        <button v-if="props.branding?.logo_invoice_url" @click="handleLogoDelete('invoice')" class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 text-sm font-medium rounded-lg transition-colors">
                                            {{ $t('company_profile.logo.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stamp -->
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden">
                                    <img v-if="props.branding?.stamp_url" :src="props.branding.stamp_url" class="w-full h-full object-contain" />
                                    <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $t('center_settings.branding.stamp') }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $t('center_settings.branding.stamp_hint') }}</p>
                                    <div class="flex items-center gap-2">
                                        <label class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg cursor-pointer transition-colors">
                                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="handleStampUpload" />
                                            {{ $t('company_profile.logo.upload') }}
                                        </label>
                                        <button v-if="props.branding?.stamp_url" @click="handleStampDelete" class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 text-sm font-medium rounded-lg transition-colors">
                                            {{ $t('company_profile.logo.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Working Hours Tab -->
                    <div v-if="activeTab === 'working_hours'" class="space-y-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('center_settings.working_hours.hint') }}</p>
                        
                        <div class="space-y-3">
                            <div v-for="(wh, index) in form.working_hours" :key="wh.day_of_week" class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <div class="w-24 font-medium text-gray-900 dark:text-white">
                                    {{ getDayName(wh.day_of_week) }}
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="wh.is_open" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                                <span class="text-sm text-gray-500 dark:text-gray-400 w-12">{{ wh.is_open ? $t('center_settings.working_hours.open') : $t('center_settings.working_hours.closed') }}</span>
                                
                                <template v-if="wh.is_open">
                                    <input v-model="wh.open_time" type="time" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" />
                                    <span class="text-gray-400">-</span>
                                    <input v-model="wh.close_time" type="time" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" />
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button @click="saveWorkingHours()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50">
                                {{ saving ? $t('common.loading') : $t('common.save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet marker icon issue
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const { t } = useI18n();
const { success, error } = useToast();

const props = defineProps({
    center: Object,
    profile: Object,
    contact: Object,
    address: Object,
    branding: Object,
    working_hours: Array,
});

const activeTab = ref('profile');
const saving = ref(false);

// Map state
const mapContainer = ref(null);
const mapReady = ref(false);
let map = null;
let marker = null;
const defaultLat = 21.4858; // Jeddah
const defaultLng = 39.1925;

const tabs = computed(() => [
    { id: 'profile', label: t('center_settings.tabs.profile') },
    { id: 'contact', label: t('center_settings.tabs.contact') },
    { id: 'branding', label: t('center_settings.tabs.branding') },
    { id: 'working_hours', label: t('center_settings.tabs.working_hours') },
]);

const form = ref({
    profile: {
        name_ar: props.profile?.name_ar ?? '',
        name_en: props.profile?.name_en ?? '',
        manager_name: props.profile?.manager_name ?? '',
        center_type: props.profile?.center_type ?? '',
        license_number: props.profile?.license_number ?? '',
        vat_number: props.profile?.vat_number ?? '',
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
    working_hours: props.working_hours?.map(wh => ({
        day_of_week: wh.day_of_week,
        is_open: wh.is_open,
        open_time: wh.open_time?.slice(0, 5) ?? '08:00',
        close_time: wh.close_time?.slice(0, 5) ?? '17:00',
    })) ?? [],
});

const dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

function getDayName(dayOfWeek) {
    return dayNames[dayOfWeek] ?? '';
}

function saveSection(section) {
    saving.value = true;
    router.put(`/app/settings/centers/${props.center.id}`, {
        section,
        ...form.value[section],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
        },
        onError: (errors) => {
            error(Object.values(errors).flat().join('\n'));
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function saveContactAndAddress() {
    saving.value = true;
    
    // Save contact first
    router.put(`/app/settings/centers/${props.center.id}`, {
        section: 'contact',
        ...form.value.contact,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Then save address
            router.put(`/app/settings/centers/${props.center.id}`, {
                section: 'address',
                ...form.value.address,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    success(t('common.saved_success'));
                },
                onError: (errors) => {
                    error(Object.values(errors).flat().join('\n'));
                },
                onFinish: () => {
                    saving.value = false;
                },
            });
        },
        onError: (errors) => {
            error(Object.values(errors).flat().join('\n'));
            saving.value = false;
        },
    });
}

function saveWorkingHours() {
    saving.value = true;
    router.put(`/app/settings/centers/${props.center.id}`, {
        section: 'working_hours',
        working_hours: form.value.working_hours,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
        },
        onError: (errors) => {
            error(Object.values(errors).flat().join('\n'));
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function handleLogoUpload(event, type) {
    const file = event.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('type', type);
    
    router.post(`/app/settings/centers/${props.center.id}/logo`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('company_profile.logo.uploaded'));
        },
        onError: () => {
            error(t('company_profile.logo.error_upload'));
        },
    });
}

function handleLogoDelete(type) {
    router.delete(`/app/settings/centers/${props.center.id}/logo`, {
        data: { type },
        preserveScroll: true,
        onSuccess: () => {
            success(t('company_profile.logo.deleted'));
        },
        onError: () => {
            error(t('company_profile.logo.error_delete'));
        },
    });
}

function handleStampUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('stamp', file);
    
    router.post(`/app/settings/centers/${props.center.id}/stamp`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('center_settings.branding.stamp_uploaded'));
        },
        onError: () => {
            error(t('center_settings.branding.stamp_error'));
        },
    });
}

function handleStampDelete() {
    router.delete(`/app/settings/centers/${props.center.id}/stamp`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('center_settings.branding.stamp_deleted'));
        },
        onError: () => {
            error(t('center_settings.branding.stamp_error'));
        },
    });
}

// Map functions
function initMap() {
    if (!mapContainer.value || map) return;

    const lat = form.value.address.latitude || defaultLat;
    const lng = form.value.address.longitude || defaultLng;

    map = L.map(mapContainer.value).setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add marker if we have coordinates
    if (form.value.address.latitude && form.value.address.longitude) {
        marker = L.marker([form.value.address.latitude, form.value.address.longitude]).addTo(map);
    }

    // Handle map click
    map.on('click', handleMapClick);
    mapReady.value = true;
}

function handleMapClick(e) {
    const { lat, lng } = e.latlng;
    
    form.value.address.latitude = lat;
    form.value.address.longitude = lng;

    // Update or create marker
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }

    // Reverse geocode to get address
    reverseGeocode(lat, lng);
}

async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=ar`
        );
        const data = await response.json();
        
        if (data.address) {
            form.value.address.city = data.address.city || data.address.town || data.address.village || '';
            form.value.address.district = data.address.suburb || data.address.neighbourhood || '';
            form.value.address.postal_code = data.address.postcode || '';
            // Map street name to street field
            form.value.address.street = data.address.road || data.address.pedestrian || data.address.street || '';
        }
    } catch (err) {
        console.error('Geocoding error:', err);
    }
}

function destroyMap() {
    if (map) {
        map.remove();
        map = null;
        marker = null;
        mapReady.value = false;
    }
}

// Watch for tab change to init map when contact tab is active
watch(activeTab, async (newTab) => {
    if (newTab === 'contact') {
        await nextTick();
        setTimeout(() => {
            initMap();
            if (map) map.invalidateSize();
        }, 100);
    }
});

onMounted(() => {
    if (activeTab.value === 'contact') {
        setTimeout(initMap, 100);
    }
});

onUnmounted(() => {
    destroyMap();
});
</script>
