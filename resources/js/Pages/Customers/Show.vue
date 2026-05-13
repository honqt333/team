<template>
    <AppLayout>
        <div class="space-y-8">
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2.5rem] blur opacity-[0.06] group-hover:opacity-[0.10] transition duration-700"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-[2.25rem] shadow-2xl shadow-indigo-500/10 border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="absolute top-0 inset-x-0 p-4 flex items-center justify-between z-20" :dir="isRtl ? 'rtl' : 'ltr'">
                        <Link :href="route('customers.index')"
                            :title="$t('common.back')"
                            class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all duration-300 text-indigo-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M14 5l7 7-7 7M21 12H3" />
                            </svg>
                        </Link>

                        <div class="flex items-center gap-2 p-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                            <button v-if="can('crm.customers.update') || isAnyAdmin()" @click="showEditModal = true"
                                :title="$t('common.edit')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-indigo-100/70 dark:border-indigo-800/30 bg-indigo-50/70 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 hover:bg-white dark:hover:bg-indigo-900/30 hover:shadow-md transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.edit') }}</span>
                            </button>

                            <button v-if="canMerge && (can('crm.customers.update') || isAnyAdmin())" @click="showMergeModal = true"
                                :title="$t('customers.merge.button')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-amber-100/70 dark:border-amber-800/30 bg-amber-50/70 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 hover:bg-white dark:hover:bg-amber-900/30 hover:shadow-md transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('customers.merge.button') }}</span>
                            </button>

                            <button v-if="can('crm.customers.delete') || isAnyAdmin()" @click="confirmDelete" :disabled="!canDelete"
                                :title="canDelete ? $t('common.delete') : $t('customers.cannot_delete_has_data')"
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border transition-all',
                                    canDelete
                                        ? 'border-red-100/70 dark:border-red-800/30 bg-red-50/70 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-white dark:hover:bg-red-900/30 hover:shadow-md'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-900/30 text-gray-400 cursor-not-allowed opacity-70'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.delete') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="absolute top-0 right-0 p-12 opacity-[0.03] dark:opacity-[0.05] pointer-events-none select-none">
                        <svg class="w-80 h-80 text-indigo-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>

                    <div class="relative z-10 p-6 pt-16">
                        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <div class="flex items-center gap-6 flex-1">
                                <div class="relative">
                                    <div class="w-24 h-24 rounded-[2.25rem] bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/35 flex items-center justify-center text-white ring-4 ring-indigo-50 dark:ring-indigo-900/20 transition-transform group-hover:scale-[1.03] duration-500">
                                        <span class="text-5xl font-black">
                                            {{ customer.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </span>
                                    </div>
                                    <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-white dark:bg-gray-700 shadow-lg border-4 border-indigo-50 dark:border-indigo-900/20 flex items-center justify-center">
                                        <svg v-if="customer.type === 'company'" class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <svg v-else class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-1">
                                        <h1 class="text-4xl font-black text-gray-900 dark:text-white leading-tight tracking-tight">
                                            {{ customer.name }}
                                        </h1>
                                        <div class="px-3 py-1.5 rounded-xl text-[10px] font-black tracking-[0.2em] uppercase border shadow-sm"
                                            :class="customer.type === 'company'
                                                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-800/30'
                                                : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30'">
                                            {{ $t(`customers.type.${customer.type}`) }}
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-indigo-500/80 dark:text-indigo-400/80 mb-4">{{ $t('customers.subtitle') }}</p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm font-bold text-gray-500 dark:text-gray-400">
                                        <span v-if="(customer.type === 'company' || customer.type === 'government') && customer.contact_name"
                                            class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border border-indigo-100/50">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ customer.contact_name }}
                                        </span>
                                        <a :href="`tel:${customer.phone}`"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-gray-50/50 dark:bg-gray-700/30 hover:bg-white dark:hover:bg-gray-700 hover:text-indigo-600 hover:shadow-xl hover:shadow-indigo-500/5 transition-all border border-transparent hover:border-indigo-100" dir="ltr">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            </div>
                                            {{ customer.phone }}
                                        </a>
                                        <a v-if="headerEmail" :href="`mailto:${headerEmail}`"
                                            :title="$t('common.email')"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-purple-50/50 dark:bg-purple-900/20 hover:bg-white dark:hover:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:shadow-xl hover:shadow-purple-500/5 transition-all border border-transparent hover:border-purple-100"
                                            dir="ltr">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="max-w-[220px] truncate">{{ headerEmail }}</span>
                                        </a>
                                        <a v-if="customer.whatsapp" :href="`https://wa.me/${getWhatsAppNumber(customer)}`"
                                            target="_blank"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 hover:bg-white dark:hover:bg-emerald-800 hover:shadow-xl hover:shadow-emerald-500/5 transition-all border border-transparent hover:border-emerald-100" dir="ltr">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                                </svg>
                                            </div>
                                            {{ customer.whatsapp }}
                                        </a>
                                        <div v-if="headerAddress"
                                            :title="$t('customers.form.address') + ': ' + headerAddress"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-amber-50/50 dark:bg-amber-900/20 hover:bg-white dark:hover:bg-amber-900/30 hover:shadow-xl hover:shadow-amber-500/5 transition-all border border-transparent hover:border-amber-100">
                                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                                <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <span class="max-w-[320px] truncate">{{ headerAddress }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-teal-50/50 dark:bg-teal-900/20 rounded-3xl border border-teal-100/50 dark:border-teal-800/30 hover:bg-white dark:hover:bg-teal-900/40 transition-all hover:shadow-lg hover:shadow-teal-500/10">
                                    <span class="text-2xl font-black text-teal-600 dark:text-teal-400 group-hover/stat:scale-110 transition-transform">{{ counts.vehicles }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('customers.vehicles') }}</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-amber-50/50 dark:bg-amber-900/20 rounded-3xl border border-amber-100/50 dark:border-amber-800/30 hover:bg-white dark:hover:bg-amber-900/40 transition-all hover:shadow-lg hover:shadow-amber-500/10">
                                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400 group-hover/stat:scale-110 transition-transform">{{ counts.quotes }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('customers.quotes') }}</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-indigo-50/50 dark:bg-indigo-900/20 rounded-3xl border border-indigo-100/50 dark:border-indigo-800/30 hover:bg-white dark:hover:bg-indigo-900/40 transition-all hover:shadow-lg hover:shadow-indigo-500/10">
                                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400 group-hover/stat:scale-110 transition-transform">{{ counts.workOrders }}</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ $t('customers.work_orders') }}</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-rose-50/50 dark:bg-rose-900/20 rounded-3xl border border-rose-100/50 dark:border-rose-800/30 hover:bg-white dark:hover:bg-rose-900/40 transition-all hover:shadow-lg hover:shadow-rose-500/10">
                                    <span class="text-2xl font-black text-rose-600 dark:text-rose-400 group-hover/stat:scale-110 transition-transform">0</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">التقييمات</span>
                                </div>
                                <div class="group/stat flex flex-col items-center justify-center w-20 h-20 lg:w-24 lg:h-24 bg-emerald-50/50 dark:bg-emerald-900/20 rounded-3xl border border-emerald-100/50 dark:border-emerald-800/30 hover:bg-white dark:hover:bg-emerald-900/40 transition-all hover:shadow-lg hover:shadow-emerald-500/10">
                                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 group-hover/stat:scale-110 transition-transform">0</span>
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">كروت العمل</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="space-y-4">
                <!-- Tabs Navigation -->
                <div class="p-1 bg-gray-100/50 dark:bg-gray-900/50 backdrop-blur-xl rounded-3xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner overflow-x-auto no-scrollbar">
                    <div class="flex items-center gap-1 min-w-max">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                            'group relative flex items-center gap-3 px-5 py-2.5 rounded-2xl transition-all duration-500 ease-out overflow-hidden',
                            activeTab === tab.key
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-xl shadow-indigo-500/30 scale-[1.01]'
                                : 'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800 hover:shadow-lg hover:shadow-gray-200/50 dark:hover:shadow-black/20 hover:-translate-y-0.5'
                        ]">
                            <!-- Animated Background Glow for Active -->
                            <div v-if="activeTab === tab.key" class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] animate-[shimmer_2s_infinite]"></div>
                            
                            <div :class="[
                                'w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-500',
                                activeTab === tab.key
                                    ? 'bg-white/20 rotate-[12deg] scale-105 shadow-inner'
                                    : 'bg-white dark:bg-gray-800 shadow-sm group-hover:rotate-[-6deg] group-hover:scale-105'
                            ]">
                                <component :is="tab.icon" :class="[
                                    'w-4.5 h-4.5 transition-all duration-500',
                                    activeTab === tab.key ? 'text-white scale-105' : 'text-indigo-500 dark:text-indigo-400 group-hover:text-purple-600'
                                ]" />
                            </div>

                            <div class="flex flex-col items-start relative z-10">
                                <span :class="[
                                    'text-[9px] font-black uppercase tracking-[0.15em] transition-colors duration-500',
                                    activeTab === tab.key ? 'text-white/70' : 'text-gray-400 dark:text-gray-500'
                                ]">{{ tab.label }}</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-base font-black leading-none">{{ toEnglish(tab.count) }}</span>
                                    <span v-if="activeTab === tab.key" class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Tab Content Container -->
                <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl shadow-indigo-500/5 border border-gray-100/50 dark:border-gray-700/50 p-6 min-h-[400px] relative overflow-hidden">
                    <!-- Background Decoration -->
                    <div class="absolute top-0 right-0 p-16 opacity-[0.01] dark:opacity-[0.02] pointer-events-none select-none -translate-y-8 translate-x-8">
                        <component :is="tabs.find(t => t.key === activeTab)?.icon" class="w-64 h-64" />
                    </div>
                    <!-- ========== VEHICLES TAB ========== -->
                    <div v-if="activeTab === 'vehicles'" class="space-y-6">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative group">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="vehicleSearch" :placeholder="$t('vehicles.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500" />
                                </div>
                                <!-- View Toggle -->
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button @click="vehicleViewMode = 'grid'"
                                        :class="['px-2 py-1 rounded transition-colors', vehicleViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                        </svg>
                                    </button>
                                    <button @click="vehicleViewMode = 'list'"
                                        :class="['px-2 py-1 rounded transition-colors', vehicleViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Add Button -->
                            <button v-if="can('crm.vehicles.create') || isAnyAdmin()" @click="openVehicleModal"
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('vehicles.add') }}
                            </button>
                        </div>

                        <!-- Grid View -->
                        <div v-if="vehicleViewMode === 'grid' && filteredVehicles.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                            <div v-for="vehicle in filteredVehicles" :key="vehicle.id"
                                @click="router.visit(route('vehicles.show', vehicle.id))"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden">
                                
                                <!-- Background Logo Watermark -->
                                <div v-if="vehicle.make?.logo_path"
                                    class="absolute inset-0 flex items-center justify-center opacity-[0.06] dark:opacity-[0.3] pointer-events-none select-none z-0 overflow-hidden">
                                    <img :src="`/storage/${vehicle.make.logo_path}`"
                                        class="w-3/4 h-3/4 object-contain grayscale dark:brightness-150 transform -rotate-12 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-700"
                                        alt="" />
                                </div>

                                <!-- Card Content -->
                                <div class="relative z-10 flex flex-col h-full">
                                    <!-- Header: Plate & Status -->
                                    <div
                                        class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50">
                                        <!-- Plate Number Badge -->
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-0.5 shadow-sm">
                                                <span
                                                    class="text-base font-bold text-gray-900 dark:text-gray-100 tracking-wider font-mono">
                                                    {{ vehicle.plate_number }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Color Dot -->
                                        <span v-if="vehicle.color"
                                            class="w-3 h-3 rounded-full border border-white dark:border-gray-700 shadow-sm"
                                            :style="{ backgroundColor: getColorHex(vehicle.color) }"
                                            :title="vehicle.color"></span>
                                    </div>

                                    <!-- Body: Vehicle Info -->
                                    <div class="p-4 flex-1">
                                        <div class="mb-3">
                                            <div class="flex items-baseline justify-between mb-1 gap-1">
                                                <h3
                                                    class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                                                    {{ getMakeName(vehicle) || $t('common.na') }}
                                                </h3>
                                                <span v-if="vehicle.year"
                                                    class="px-1.5 py-0.5 text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md">
                                                    {{ vehicle.year }}
                                                </span>
                                            </div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">
                                                {{ getModelName(vehicle) }}
                                            </p>
                                        </div>

                                        <!-- Odometer & VIN Footer -->
                                        <div
                                            class="text-[10px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700/50 flex flex-col gap-1.5 mt-auto">
                                            <div v-if="vehicle.odometer" class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                                    vehicle.odometer?.toLocaleString() }}</span>
                                                <span>{{ $t('common.km') }}</span>
                                            </div>
                                            <div v-if="vehicle.vin" class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                <span class="font-mono text-[9px] truncate max-w-[80px]"
                                                    :title="vehicle.vin">{{ vehicle.vin }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else-if="vehicleViewMode === 'list' && filteredVehicles.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('vehicles.form.plate') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('vehicles.form.make') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('vehicles.form.model') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('vehicles.form.year') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="vehicle in filteredVehicles" :key="vehicle.id"
                                        @click="router.visit(route('vehicles.show', vehicle.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td
                                            class="px-4 py-3 font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                            <span dir="ltr" class="inline-block">{{ vehicle.plate_number }}</span></td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{
                                            getMakeName(vehicle) }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{
                                            getModelName(vehicle) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{
                                            vehicle.year }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_vehicles') }}</p>
                            <button v-if="can('crm.vehicles.create') || isAnyAdmin()" @click="openVehicleModal"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('vehicles.add') }}
                            </button>
                        </div>
                    </div>

                    <!-- ========== WORK ORDERS TAB ========== -->
                    <div v-if="activeTab === 'workOrders'" class="space-y-4">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="workOrderSearch" :placeholder="$t('work_orders.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button @click="workOrderViewMode = 'grid'"
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                        </svg>
                                    </button>
                                    <button @click="workOrderViewMode = 'list'"
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div v-if="workOrderViewMode === 'grid' && filteredWorkOrders.length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <Link v-for="order in filteredWorkOrders" :key="order.id"
                                :href="route('work-orders.show', order.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span
                                        class="font-mono font-bold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{
                                        order.code }}</span>
                                    <span :class="getStatusClass(order.status)"
                                        class="px-2 py-0.5 text-xs font-medium rounded-full">{{
                                            $t(`work_orders.status.${order.status}`) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 mb-3">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span dir="ltr" class="truncate">{{ order.vehicle?.plate_number }}</span>
                                    <span class="text-gray-400 mx-0.5">•</span>
                                    <span class="truncate">{{ getVehicleName(order.vehicle) }}</span>
                                </div>
                                <div
                                    class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <span class="text-xs text-gray-400">{{ formatDate(order.created_at) }}</span>
                                </div>
                            </Link>
                        </div>

                        <!-- List View -->
                        <div v-else-if="workOrderViewMode === 'list' && filteredWorkOrders.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('work_orders.columns.code') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('work_orders.columns.vehicle') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('work_orders.columns.status') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('work_orders.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in filteredWorkOrders" :key="order.id"
                                        @click="router.visit(route('work-orders.show', order.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td
                                            class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ order.code }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span dir="ltr"
                                                    class="text-sm font-bold w-fit text-start inline-block">{{
                                                    order.vehicle?.plate_number }}</span>
                                                <span class="text-xs text-gray-500">{{ getVehicleName(order.vehicle)
                                                    }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span :class="getStatusClass(order.status)"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full">{{
                                                $t(`work_orders.status.${order.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{
                                            formatDate(order.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredWorkOrders.length === 0" class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_work_orders') }}</p>
                        </div>
                    </div>

                    <!-- ========== QUOTES TAB ========== -->
                    <div v-if="activeTab === 'quotes'" class="space-y-4">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="quoteSearch" :placeholder="$t('quotes.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500" />
                                </div>
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button @click="quoteViewMode = 'grid'"
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
                                        </svg>
                                    </button>
                                    <button @click="quoteViewMode = 'list'"
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div v-if="quoteViewMode === 'grid' && filteredQuotes.length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Link v-for="quote in filteredQuotes" :key="quote.id"
                                :href="route('app.quotes.show', quote.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-md transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ quote.code
                                        }}</span>
                                    <span :class="getQuoteStatusClass(quote.status)"
                                        class="px-2 py-0.5 text-xs font-medium rounded-full">{{
                                            $t(`quotes.status.${quote.status}`) }}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400" dir="ltr">{{
                                    quote.vehicle?.plate_number }}</div>
                                <div class="mt-2 text-xs text-gray-400">{{ formatDate(quote.created_at) }}</div>
                            </Link>
                        </div>

                        <!-- List View -->
                        <div v-else-if="quoteViewMode === 'list' && filteredQuotes.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('quotes.columns.code') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('quotes.columns.vehicle') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('quotes.columns.status') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('quotes.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="quote in filteredQuotes" :key="quote.id"
                                        @click="router.visit(route('app.quotes.show', quote.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td
                                            class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ quote.code }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span dir="ltr"
                                                    class="text-sm font-bold w-fit text-start inline-block">{{
                                                    quote.vehicle?.plate_number }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span :class="getQuoteStatusClass(quote.status)"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full">{{
                                                $t(`quotes.status.${quote.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{
                                            formatDate(quote.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredQuotes.length === 0" class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_quotes') }}</p>
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-4">
                        <div class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{
                                $t('customers.invoices') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- ========== PAYMENTS TAB ========== -->
                    <div v-if="activeTab === 'payments'" class="space-y-4">
                        <!-- Search Box -->
                        <div class="flex items-center gap-3">
                            <div class="relative group">
                                <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" v-model="paymentSearch" :placeholder="$t('common.search') + '...'"
                                    class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500" />
                            </div>
                        </div>

                        <!-- Payments Table -->
                        <div v-if="filteredPayments && filteredPayments.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            #</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.reference') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.method') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.type') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.date') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.notes') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('payments.form.amount') }}</th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">
                                            {{ $t('common.updated_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(payment, index) in filteredPayments" :key="payment.id"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{
                                            index + 1 }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <Link :href="route('work-orders.show', payment.work_order_id)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono">
                                                {{ payment.work_order?.code || '-' }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                :class="getPaymentMethodClass(payment.payment_method)">
                                                {{ $t(`payments.methods.${payment.payment_method}`) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                :class="payment.type === 'refund' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'">
                                                {{ $t(`payments.types.${payment.type || 'payment'}`) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            <span dir="ltr" class="inline-block">{{ formatDate(payment.payment_date)
                                                }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{
                                            payment.notes || '-' }}</td>
                                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                            <span dir="ltr" class="inline-block">{{ formatPrice(payment.amount)
                                                }}</span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm whitespace-nowrap">
                                            {{ payment.received_by?.name || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{
                                $t('customers.payments') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('payments.no_payments') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CustomerFormModal :show="showEditModal" :customer="customer" @close="showEditModal = false"
            @saved="handleCustomerSaved" />
        <VehicleFormModal v-if="showVehicleModal" :show="showVehicleModal" :vehicle="selectedVehicle"
            :customers="[customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake"
            :defaultCustomerId="customer.id" @close="showVehicleModal = false" @saved="handleVehicleSaved" />
        <WorkOrderFormModal v-if="showWorkOrderModal" :show="showWorkOrderModal" :customers="[customer]" :makes="makes"
            :colors="colors" :modelsByMake="modelsByMake" :departments="departments" @close="showWorkOrderModal = false"
            @saved="handleWorkOrderSaved" />
        <QuoteFormModal v-if="showQuoteModal" :show="showQuoteModal" :customers="[customer]" :makes="makes"
            :colors="colors" :modelsByMake="modelsByMake" :departments="departments" :services="services"
            @close="showQuoteModal = false" @saved="handleQuoteSaved" />
        <CustomerMergeModal v-if="showMergeModal" :show="showMergeModal" :customer="customer" :counts="counts"
            @close="showMergeModal = false" />
        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { ref, computed, h, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';
import CustomerMergeModal from '@/Components/Customers/CustomerMergeModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    customer: Object,
    counts: Object,
    canDelete: Boolean,
    vehicles: Array,
    workOrders: Array,
    quotes: Array,
    payments: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { confirm } = useConfirm();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

const headerEmail = computed(() => (props.customer?.email || '').trim());
const headerAddress = computed(() => {
    const c = props.customer || {};
    const parts = [
        c.address_line,
        c.building_number,
        c.district,
        c.city,
        c.region,
        c.postal_code,
        c.country,
    ].filter(Boolean);

    return parts.join(isRtl.value ? '، ' : ', ');
});

// Modal states
const showEditModal = ref(false);
const showVehicleModal = ref(false);
const showWorkOrderModal = ref(false);
const showQuoteModal = ref(false);

const showMergeModal = ref(false);
const selectedVehicle = ref(null);

// Tab & view states
const activeTab = ref(localStorage.getItem('customer_active_tab') || 'vehicles');
const vehicleViewMode = ref(localStorage.getItem('customer_vehicle_view_mode') || 'grid');
const workOrderViewMode = ref(localStorage.getItem('customer_work_order_view_mode') || 'grid');
const quoteViewMode = ref(localStorage.getItem('customer_quote_view_mode') || 'grid');

// Search states
const vehicleSearch = ref('');
const workOrderSearch = ref('');
const quoteSearch = ref('');
const paymentSearch = ref('');

// Persistence watchers
watch(activeTab, (val) => localStorage.setItem('customer_active_tab', val));
watch(vehicleViewMode, (val) => localStorage.setItem('customer_vehicle_view_mode', val));
watch(workOrderViewMode, (val) => localStorage.setItem('customer_work_order_view_mode', val));
watch(quoteViewMode, (val) => localStorage.setItem('customer_quote_view_mode', val));

// Icons
const CarIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z' })]);
const ClipboardIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })]);
const DocumentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })]);
const InvoiceIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' })]);
const PaymentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' })]);

const tabs = computed(() => [
    { key: 'vehicles', label: t('customers.vehicles'), count: props.counts.vehicles, icon: CarIcon, activeColor: 'text-teal-600 dark:text-teal-400' },
    { key: 'workOrders', label: t('customers.work_orders'), count: props.counts.workOrders, icon: ClipboardIcon, activeColor: 'text-indigo-600 dark:text-indigo-400' },
    { key: 'quotes', label: t('customers.quotes'), count: props.counts.quotes, icon: DocumentIcon, activeColor: 'text-amber-600 dark:text-amber-400' },
    { key: 'invoices', label: t('customers.invoices'), count: props.counts.invoices || 0, icon: InvoiceIcon, activeColor: 'text-green-600 dark:text-green-400' },
    { key: 'payments', label: t('customers.payments'), count: props.counts.payments || 0, icon: PaymentIcon, activeColor: 'text-purple-600 dark:text-purple-400' },
]);

// Filtered data
const canMerge = computed(() => props.counts.vehicles > 0 || props.counts.quotes > 0 || props.counts.workOrders > 0);
const filteredVehicles = computed(() => {
    if (!props.vehicles) return [];
    if (!vehicleSearch.value) return props.vehicles;
    const q = vehicleSearch.value.toLowerCase();
    return props.vehicles.filter(v => v.plate_number?.toLowerCase().includes(q) || getName(v.make)?.toLowerCase().includes(q));
});

const filteredWorkOrders = computed(() => {
    if (!props.workOrders) return [];
    if (!workOrderSearch.value) return props.workOrders;
    const q = workOrderSearch.value.toLowerCase();
    return props.workOrders.filter(w => w.code?.toLowerCase().includes(q) || w.vehicle?.plate_number?.toLowerCase().includes(q));
});

const filteredQuotes = computed(() => {
    if (!props.quotes) return [];
    if (!quoteSearch.value) return props.quotes;
    const q = quoteSearch.value.toLowerCase();
    return props.quotes.filter(qu => qu.code?.toLowerCase().includes(q) || qu.vehicle?.plate_number?.toLowerCase().includes(q));
});

const filteredPayments = computed(() => {
    if (!props.payments) return [];
    if (!paymentSearch.value) return props.payments;
    const q = paymentSearch.value.toLowerCase();
    return props.payments.filter(p =>
        p.work_order?.code?.toLowerCase().includes(q) ||
        p.notes?.toLowerCase().includes(q) ||
        p.payment_method?.toLowerCase().includes(q) ||
        p.received_by?.name?.toLowerCase().includes(q)
    );
});

// Helpers
function getName(item) {
    if (!item) return '';
    return locale.value === 'ar' ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
}

function getMakeName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.make) || vehicle.make_other || '';
}

function getModelName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.model) || vehicle.model_other || '';
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    return [getMakeName(vehicle), getModelName(vehicle), vehicle.year].filter(Boolean).join(' ');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US');
}

function getWhatsAppNumber(customer) {
    const phone = customer?.whatsapp || customer?.phone;
    return phone ? phone.replace(/[^\d+]/g, '').replace(/^\+/, '') : '';
}

function getStatusClass(status) {
    const classes = { open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    return classes[status] || classes.open;
}

// Map common color names to hex values
function getColorHex(colorName) {
    if (!colorName) return '#gray';
    const colorMap = {
        'أبيض': '#ffffff',
        'أسود': '#1f2937',
        'فضي': '#9ca3af',
        'رمادي': '#6b7280',
        'أحمر': '#ef4444',
        'أزرق': '#3b82f6',
        'أخضر': '#22c55e',
        'ذهبي': '#eab308',
        'بني': '#92400e',
        'برتقالي': '#f97316',
        'بيج': '#d4c4a8',
        'white': '#ffffff',
        'black': '#1f2937',
        'silver': '#9ca3af',
        'gray': '#6b7280',
        'red': '#ef4444',
        'blue': '#3b82f6',
        'green': '#22c55e',
        'gold': '#eab308',
        'brown': '#92400e',
        'orange': '#f97316',
        'beige': '#d4c4a8',
    };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || '#9ca3af';
}

function getQuoteStatusClass(status) {
    const classes = { draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', converted: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' };
    return classes[status] || classes.draft;
}

function getPaymentMethodClass(method) {
    const classes = {
        cash: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        mada: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        visa: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        mastercard: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        transfer: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
        apple_pay: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        stc_pay: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        credit: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        other: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[method] || classes.other;
}

function formatPrice(amount) {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function openVehicleModal() {
    selectedVehicle.value = null;
    showVehicleModal.value = true;
}

function openVehicleEditModal(vehicle) {
    selectedVehicle.value = vehicle;
    showVehicleModal.value = true;
}

function openWorkOrderModal() {
    showWorkOrderModal.value = true;
}

function openQuoteModal() {
    showQuoteModal.value = true;
}

function handleCustomerSaved() {
    showEditModal.value = false;
    router.reload({ preserveScroll: true });
}

function handleVehicleSaved() {
    showVehicleModal.value = false;
    router.reload({ preserveScroll: true });
}

function handleWorkOrderSaved() {
    showWorkOrderModal.value = false;
    router.reload({ preserveScroll: true });
}

function handleQuoteSaved() {
    showQuoteModal.value = false;
    router.reload({ preserveScroll: true });
}

async function confirmDelete() {
    const result = await confirm({
         title: t('common.delete_confirm'),
         message: t('customers.delete_confirm_message'),
         confirmText: t('common.delete'),
         type: 'danger'
     });

    if (result) {
        router.delete(route('customers.destroy', props.customer.id), {
            onSuccess: () => {
                // Success handled by Inertia flash
            }
        });
    }
}
</script>

<style scoped>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
