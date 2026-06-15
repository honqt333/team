<template>
    <div class="space-y-4">
        <!-- Toolbar: Title & Add Button next to it -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">🔧</span>
                    {{ $t('work_orders.show.tabs.services') }}
                </h3>

                <!-- Add Department Dropdown -->
                <div v-if="!isReadOnly" class="relative">
                    <button @click="showDeptMenu = !showDeptMenu"
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('quotes.show.add_department') }}
                    </button>
                    <div v-if="showDeptMenu"
                        class="absolute z-50 start-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 max-h-60 overflow-y-auto">
                        <button v-for="dept in availableDepartments" :key="dept.id"
                            @click="onAddDepartment(dept.id)"
                            class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            {{ getName(dept) }}
                        </button>
                        <p v-if="availableDepartments.length === 0" class="px-4 py-2 text-sm text-gray-400">
                            {{ $t('quotes.show.all_departments_added') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments list -->
        <div v-if="displayDepartments.length > 0" class="space-y-3">
            <div v-for="dept in displayDepartments" :key="dept.id"
                class="border border-gray-200 dark:border-gray-700 rounded-xl">
                <!-- Department header -->
                <div
                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-900/50 dark:to-transparent rounded-t-xl">
                    <div class="flex items-center gap-3 flex-1">
                        <div
                            class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <span class="text-indigo-600 dark:text-indigo-400">🔧</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                        <span
                            class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                            {{ getItemsForDept(dept.id).length }} {{ $t('quotes.show.services_count') }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Print Button (if department has services) -->
                        <button v-if="getItemsForDept(dept.id).length > 0"
                            @click.stop="printDepartment(dept.id)"
                            type="button"
                            class="w-7 h-7 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-400 text-gray-400 flex items-center justify-center transition-colors"
                            :title="$t('common.print')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>

                        <button v-if="!isReadOnly && (!dept.is_virtual || dept.id === PACKAGES_DEPT_KEY) && getItemsForDept(dept.id).length === 0"
                            @click.stop="emit('remove-department', dept.id)"
                            class="w-7 h-7 rounded-lg hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-gray-400 flex items-center justify-center transition-colors"
                            :title="$t('common.delete')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Services list (always visible — no accordion collapse) -->
                <div class="p-4 space-y-2 bg-gray-50/50 dark:bg-gray-900/30 rounded-b-xl">
                    <div class="flex flex-col gap-3">
                        <div v-for="(item, index) in getItemsForDept(dept.id)" :key="item.id"
                            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/70 border-s-4 p-4 transition-shadow transition-colors duration-200 group relative"
                            :class="{
                                'border-s-gray-300 dark:border-s-gray-600': item.status === 'pending',
                                'border-s-blue-500': item.status === 'in_progress',
                                'border-s-teal-500': item.status === 'ready_for_qc',
                                'border-s-emerald-500': item.status === 'completed',
                                'border-s-amber-500': item.status === 'on_hold',
                                'border-s-rose-500': item.status === 'cancelled',
                                'hover:shadow-lg hover:shadow-indigo-500/5 hover:border-gray-200 dark:hover:border-gray-600': activeDropdownItemId !== item.id
                            }">
                            <div class="flex items-start justify-between gap-4">
                                <!-- Content (right side) -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-gray-400 font-semibold font-mono text-sm mt-0.5 select-none">{{ index + 1 }}.</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                                <button v-if="!isReadOnly"
                                                    @click.stop="emit('edit-item', item)" type="button"
                                                    class="font-bold text-gray-900 dark:text-white text-base hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-start leading-snug">
                                                    {{ item.service ? getName(item.service) : item.title }}
                                                </button>
                                                <span v-else
                                                    class="font-bold text-gray-900 dark:text-white text-base text-start leading-snug">
                                                    {{ item.service ? getName(item.service) : item.title }}
                                                </span>
                                            </div>

                                            <div v-if="item.service && item.title && item.title !== getName(item.service)"
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 bg-gray-50/50 dark:bg-gray-900/40 p-2.5 rounded-lg border border-gray-100/50 dark:border-gray-850 leading-relaxed">
                                                {{ item.title }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100/75 dark:border-gray-800/80 my-3"></div>

                                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <!-- Labor price -->
                                        <div class="inline-flex items-center gap-1.5 bg-indigo-50/80 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100/80 dark:border-indigo-900/40 px-3 py-1 rounded-full text-xs font-semibold"
                                            :title="$t('work_orders.item.service_cost')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span>{{ $t('work_orders.item.service_cost') }}:</span>
                                            <span class="font-bold font-mono text-gray-900 dark:text-gray-100">{{ formatPrice(item.line_total || item.total) }}</span>
                                        </div>

                                        <!-- Parts cost (if any) -->
                                        <div v-if="item.parts_total > 0"
                                            class="inline-flex items-center gap-1.5 bg-amber-50/80 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 border border-amber-100/80 dark:border-amber-900/40 px-3 py-1 rounded-full text-xs font-semibold"
                                            :title="$t('work_orders.item.parts_cost')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 00-2 2zM9 9h6v6H9V9z"/>
                                            </svg>
                                            <span>{{ $t('work_orders.item.parts_cost') }}:</span>
                                            <span class="font-bold font-mono text-gray-900 dark:text-gray-100">{{ formatPrice(item.parts_total) }}</span>
                                        </div>

                                        <!-- Technician (or "unassigned" prompt) -->
                                        <button v-if="item.technicians && item.technicians.length"
                                            @click.stop="!isReadOnly && emit('assign-technician', item.id)"
                                            type="button"
                                            :disabled="isReadOnly"
                                            class="inline-flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60 px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="!isReadOnly ? 'hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition-colors' : ''">
                                            <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>{{ item.technicians[0].name }}</span>
                                        </button>
                                        <button v-else-if="!isReadOnly"
                                            @click.stop="emit('assign-technician', item.id)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 bg-rose-50/50 dark:bg-rose-950/10 text-rose-600 dark:text-rose-400 border border-dashed border-rose-200 dark:border-rose-900/40 px-3 py-1 rounded-full text-xs font-semibold animate-pulse hover:bg-rose-100/60 dark:hover:bg-rose-950/30 transition-all cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>{{ $t('work_orders.item.assign_technician') }}</span>
                                        </button>
                                        <div v-else
                                            class="inline-flex items-center gap-1.5 bg-rose-50/50 dark:bg-rose-950/10 text-rose-600 dark:text-rose-400 border border-dashed border-rose-200 dark:border-rose-900/40 px-3 py-1 rounded-full text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>{{ $t('work_orders.item.assign_technician') }}</span>
                                        </div>

                                        <!-- Warranty -->
                                        <div v-if="item.warranty_expires_at"
                                            class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border border-emerald-100/80 dark:border-emerald-900/40 px-3 py-1 rounded-full text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <span>{{ $t('services_management.warranty') }}: {{ formatDate(item.warranty_expires_at) }}</span>
                                        </div>

                                        <!-- Date created -->
                                        <div class="inline-flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700/60 px-3 py-1 rounded-full text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>{{ formatDate(item.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Per-item actions & status -->
                                <div class="flex items-center gap-1.5 border-s border-gray-100 dark:border-gray-700/70 ps-3 shrink-0 mt-0.5">
                                    <!-- Interactive Status Dropdown -->
                                    <div v-if="!isReadOnly" class="relative inline-block shrink-0">
                                        <button
                                            @click.stop="activeDropdownItemId = activeDropdownItemId === item.id ? null : item.id"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold select-none border transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                            :class="{
                                                'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800/40 dark:text-gray-400 dark:border-gray-600/50': item.status === 'pending',
                                                'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50': item.status === 'in_progress',
                                                'bg-teal-50 text-teal-600 border-teal-100 dark:bg-teal-950/30 dark:text-teal-400 dark:border-teal-900/50': item.status === 'ready_for_qc',
                                                'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50': item.status === 'completed',
                                                'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50': item.status === 'on_hold',
                                                'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50': item.status === 'cancelled'
                                            }"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                :class="{
                                                    'bg-gray-400 dark:bg-gray-500': item.status === 'pending',
                                                    'bg-blue-500': item.status === 'in_progress',
                                                    'bg-teal-500': item.status === 'ready_for_qc',
                                                    'bg-emerald-500': item.status === 'completed',
                                                    'bg-amber-500': item.status === 'on_hold',
                                                    'bg-rose-500': item.status === 'cancelled'
                                                }"></span>
                                            <span>{{ $t(`work_orders.item.status_${item.status}`) }}</span>
                                            <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-500 ms-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div v-if="activeDropdownItemId === item.id"
                                            class="absolute z-30 start-0 mt-1.5 w-40 bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 rounded-xl shadow-xl py-1 overflow-hidden"
                                        >
                                            <!-- Invisible overlay to close dropdown -->
                                            <div class="fixed inset-0 z-[-1]" @click.stop="activeDropdownItemId = null"></div>

                                            <button v-for="status in availableStatuses" :key="status"
                                                @click.stop="changeItemStatus(item, status)"
                                                class="w-full px-4 py-2 text-start text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2"
                                                :class="item.status === status ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/20' : 'text-gray-700 dark:text-gray-300'"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                    :class="{
                                                        'bg-gray-400 dark:bg-gray-500': status === 'pending',
                                                        'bg-blue-500': status === 'in_progress',
                                                        'bg-teal-500': status === 'ready_for_qc',
                                                        'bg-emerald-500': status === 'completed',
                                                        'bg-amber-500': status === 'on_hold',
                                                        'bg-rose-500': status === 'cancelled'
                                                    }"></span>
                                                {{ $t(`work_orders.item.status_${status}`) }}
                                            </button>
                                        </div>
                                    </div>
                                    <span v-else
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold select-none border"
                                        :class="{
                                            'bg-gray-50 text-gray-500 border-gray-200/60 dark:bg-gray-700/30 dark:text-gray-400 dark:border-gray-600/50': item.status === 'pending',
                                            'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50': item.status === 'in_progress',
                                            'bg-teal-50 text-teal-600 border-teal-100 dark:bg-teal-950/30 dark:text-teal-400 dark:border-teal-900/50': item.status === 'ready_for_qc',
                                            'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50': item.status === 'completed',
                                            'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50': item.status === 'on_hold',
                                            'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50': item.status === 'cancelled'
                                        }"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                            :class="{
                                                'bg-gray-400 dark:bg-gray-500': item.status === 'pending',
                                                'bg-blue-500': item.status === 'in_progress',
                                                'bg-teal-500': item.status === 'ready_for_qc',
                                                'bg-emerald-500': item.status === 'completed',
                                                'bg-amber-500': item.status === 'on_hold',
                                                'bg-rose-500': item.status === 'cancelled'
                                            }"></span>
                                        <span>{{ $t(`work_orders.item.status_${item.status}`) }}</span>
                                    </span>

                                    <!-- Edit / Delete buttons -->
                                    <template v-if="!isReadOnly">
                                        <button @click.stop="emit('edit-item', item)"
                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            :title="$t('common.edit')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button v-if="!(item.technicians && item.technicians.length > 0) && !(item.parts && item.parts.length > 0) && !(item.parts_total > 0) && !(workOrder.payments && workOrder.payments.length > 0)"
                                            @click.stop="emit('delete-item', item)"
                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            :title="$t('common.delete')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="getItemsForDept(dept.id).length === 0"
                        class="text-center text-gray-400 dark:text-gray-500 py-4 text-sm">
                        {{ $t('quotes.show.no_services') }}
                    </p>

                    <button v-if="!isReadOnly" @click="emit('add-service', dept.id)"
                        class="w-full flex items-center justify-center gap-2 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-dashed border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('quotes.show.add_service') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state (no departments yet) -->
        <div v-else class="text-center py-12">
            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl">🔧</span>
            </div>
            <div v-if="departments.length === 0" class="space-y-1">
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('work_orders.show.no_departments_in_center') }}
                </p>
                <Link :href="route('services.index')"
                    class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                    {{ $t('work_orders.show.click_here_to_add') }}
                </Link>
            </div>
            <p v-else class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.show.no_services') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormatters } from '@/Composables/useFormatters';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    workOrder: { type: Object, required: true },
    /** Map of departmentId -> WorkOrderItem[]. Pre-grouped by the controller. */
    itemsByDepartment: { type: Object, default: () => ({}) },
    /** Departments to display, after merging items + linked departments. */
    displayDepartments: { type: Array, required: true },
    /** Departments that can still be added (filtered by Show.vue). */
    availableDepartments: { type: Array, required: true },
    /** All known departments (used to decide the empty-state message). */
    departments: { type: Array, default: () => [] },
    isReadOnly: { type: Boolean, default: false },
});

const emit = defineEmits([
    'add-department',
    'remove-department',
    'add-service',
    'edit-item',
    'delete-item',
    'print-department',
    'assign-technician',
]);

// Sentinel key for the virtual "service packages" department bucket.
// Kept in sync with Show.vue (PACKAGES_DEPT_KEY).
const PACKAGES_DEPT_KEY = 'packages';

const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();
const { formatDate } = useFormatters();

// Local price formatter — same shape as the rest of the page so service
// items render with the project's currency unit.
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

// Local UI state for the "add department" dropdown.
const showDeptMenu = ref(false);

const activeDropdownItemId = ref(null);
const availableStatuses = ['pending', 'in_progress', 'ready_for_qc', 'on_hold', 'completed', 'cancelled'];
const { success: toastSuccess, error: toastError } = useToast();

function changeItemStatus(item, newStatus) {
    if (item.status === newStatus) {
        activeDropdownItemId.value = null;
        return;
    }

    axios.patch(route('work-orders.items.status', { work_order: props.workOrder.id, item: item.id }), {
        status: newStatus
    }).then(res => {
        toastSuccess(t('messages.item_status_updated'));
        activeDropdownItemId.value = null;
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }).catch(err => {
        const errMsg = err.response?.data?.error || t('common.error');
        toastError(errMsg);
    });
}

function printDepartment(deptId) {
    emit('print-department', deptId);
}

function getItemsForDept(deptId) {
    const items = props.itemsByDepartment[deptId] || [];
    const statusPriority = {
        'pending': 1,
        'in_progress': 2,
        'ready_for_qc': 3,
        'on_hold': 4,
        'completed': 5,
        'cancelled': 6
    };
    return [...items].sort((a, b) => {
        const priorityA = statusPriority[a.status] || 7;
        const priorityB = statusPriority[b.status] || 7;
        return priorityA - priorityB;
    });
}

function onAddDepartment(deptId) {
    emit('add-department', deptId);
    showDeptMenu.value = false;
}
</script>
