<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.hr.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-500 to-slate-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.settings.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.settings.subtitle') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === tab.key
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
                            ]"
                        >
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Employee Types Tab -->
                    <div v-show="activeTab === 'employee_types'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.employee_types.title') }}</h3>
                            <button
                                @click="openModal('employee_type')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <SettingsTable
                            :items="employeeTypes"
                            :columns="['name_ar', 'name_en', 'is_active', 'updated_by']"
                            @edit="(item) => openModal('employee_type', item)"
                            @delete="(item) => deleteItem('employee_type', item)"
                        />
                    </div>

                    <!-- Job Titles Tab -->
                    <div v-show="activeTab === 'job_titles'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.job_titles.title') }}</h3>
                            <button
                                @click="openModal('job_title')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <SettingsTable
                            :items="jobTitles"
                            :columns="['name_ar', 'name_en', 'department', 'is_active', 'updated_by']"
                            @edit="(item) => openModal('job_title', item)"
                            @delete="(item) => deleteItem('job_title', item)"
                        />
                    </div>

                    <!-- Allowances Tab -->
                    <div v-show="activeTab === 'allowances'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.allowances.title') }}</h3>
                            <button
                                @click="openModal('allowance')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <SettingsTable
                            :items="allowances"
                            :columns="['name_ar', 'name_en', 'type', 'amount', 'is_active']"
                            @edit="(item) => openModal('allowance', item)"
                            @delete="(item) => deleteItem('allowance', item)"
                        />
                    </div>

                    <!-- Deductions Tab -->
                    <div v-show="activeTab === 'deductions'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.deductions.title') }}</h3>
                            <button
                                @click="openModal('deduction')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <SettingsTable
                            :items="deductions"
                            :columns="['name_ar', 'name_en', 'type', 'amount', 'is_active']"
                            @edit="(item) => openModal('deduction', item)"
                            @delete="(item) => deleteItem('deduction', item)"
                        />
                    </div>

                    <!-- Biometric Devices Tab -->
                    <div v-show="activeTab === 'biometric_devices'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.biometric_devices.title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.settings.biometric_devices.subtitle') }}</p>
                            </div>
                            <button
                                @click="openDeviceModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <!-- Devices List -->
                        <div v-if="biometricDevices.length" class="space-y-3">
                            <div v-for="device in biometricDevices" :key="device.id" 
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <span class="text-xl">📡</span>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ device.name }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ device.center?.name }} • {{ device.device_type || $t('common.unknown') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="[
                                            'px-2 py-0.5 rounded-full text-xs font-medium',
                                            device.is_active 
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-400'
                                        ]">
                                            {{ device.is_active ? $t('common.active') : $t('common.inactive') }}
                                        </span>
                                        <button @click="showDeviceToken(device)" class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg" :title="$t('hr.settings.biometric_devices.show_token')">
                                            🔑
                                        </button>
                                        <button @click="openDeviceModal(device)" class="p-1.5 text-gray-600 hover:bg-gray-200 rounded-lg" :title="$t('common.edit')">
                                            ✏️
                                        </button>
                                        <button @click="deleteDevice(device)" class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg" :title="$t('common.delete')">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                                <div v-if="device.last_sync_at" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('hr.settings.biometric_devices.last_sync') }}: {{ device.last_sync_at }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <span class="text-4xl mb-4 block">📡</span>
                            {{ $t('hr.settings.biometric_devices.no_devices') }}
                        </div>
                    </div>

                    <!-- Shifts Tab -->
                    <div v-show="activeTab === 'shifts'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.settings.shifts.title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.settings.shifts.subtitle') }}</p>
                            </div>
                            <button
                                @click="openShiftModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <!-- Shifts List -->
                        <div v-if="shifts.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="shift in shifts" :key="shift.id" 
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border-2 transition-all"
                                :style="{ borderColor: shift.color }"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: shift.color }"></div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ shift.name_ar }}</h4>
                                    </div>
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-xs font-medium',
                                        shift.is_active 
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-400'
                                    ]">
                                        {{ shift.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2 font-mono text-center">
                                    {{ shift.start_time?.slice(0, 5) }} - {{ shift.end_time?.slice(0, 5) }}
                                </div>
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="shift.is_overnight" class="flex items-center gap-1">
                                        🌙 {{ $t('hr.settings.shifts.overnight') }}
                                    </span>
                                    <span>📅 {{ $t('hr.settings.shifts.break') }}: {{ shift.break_minutes }} {{ $t('common.minutes') }}</span>
                                </div>
                                <div class="flex justify-end gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                    <button @click="openShiftModal(shift)" class="p-1.5 text-gray-600 hover:bg-gray-200 rounded-lg">✏️</button>
                                    <button @click="deleteShift(shift)" class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg">🗑️</button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <span class="text-4xl mb-4 block">⏰</span>
                            {{ $t('hr.settings.shifts.no_shifts') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <HRSettingModal
            :show="showModal"
            :type="modalType"
            :item="editingItem"
            :departments="departments"
            @close="closeModal"
            @saved="closeModal"
        />

        <!-- Biometric Device Modal -->
        <BaseModal :show="showDeviceModal" @close="closeDeviceModal" size="md">
            <template #title>
                {{ editingDevice ? $t('common.edit') : $t('common.add') }} {{ $t('hr.settings.biometric_devices.device') }}
            </template>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.name') }} *
                    </label>
                    <input
                        v-model="deviceForm.name"
                        type="text"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.center') }} *
                    </label>
                    <select
                        v-model="deviceForm.center_id"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        required
                    >
                        <option v-for="center in centers" :key="center.id" :value="center.id">
                            {{ center.name }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.biometric_devices.device_id') }}
                        </label>
                        <input
                            v-model="deviceForm.device_id"
                            type="text"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.biometric_devices.device_type') }}
                        </label>
                        <input
                            v-model="deviceForm.device_type"
                            type="text"
                            placeholder="ZKTeco, Hikvision..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        />
                    </div>
                </div>

                <div v-if="editingDevice" class="flex items-center gap-2">
                    <input type="checkbox" v-model="deviceForm.is_active" id="device_active" class="rounded text-violet-600">
                    <label for="device_active" class="text-sm text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.notes') }}
                    </label>
                    <textarea
                        v-model="deviceForm.notes"
                        rows="2"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button 
                        @click="closeDeviceModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button 
                        @click="saveDevice"
                        class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </div>
        </BaseModal>

        <!-- Shift Modal -->
        <BaseModal :show="showShiftModal" @close="closeShiftModal" size="md">
            <template #title>
                {{ editingShift ? $t('common.edit') : $t('common.add') }} {{ $t('hr.settings.shifts.shift') }}
            </template>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('common.name') }} (العربية) *
                        </label>
                        <input
                            v-model="shiftForm.name_ar"
                            type="text"
                            placeholder="صباحي، مسائي..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('common.name') }} (English)
                        </label>
                        <input
                            v-model="shiftForm.name_en"
                            type="text"
                            placeholder="Morning, Evening..."
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.shifts.start_time') }} *
                        </label>
                        <input
                            v-model="shiftForm.start_time"
                            type="time"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.shifts.end_time') }} *
                        </label>
                        <input
                            v-model="shiftForm.end_time"
                            type="time"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.shifts.break') }}
                        </label>
                        <input
                            v-model.number="shiftForm.break_minutes"
                            type="number"
                            min="0"
                            max="180"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.settings.shifts.color') }}
                        </label>
                        <input
                            v-model="shiftForm.color"
                            type="color"
                            class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-600 cursor-pointer"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="shiftForm.is_overnight" class="rounded text-violet-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">🌙 {{ $t('hr.settings.shifts.overnight') }}</span>
                    </label>
                    <label v-if="editingShift" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="shiftForm.is_active" class="rounded text-violet-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button 
                        @click="closeShiftModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button 
                        @click="saveShift"
                        class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsTable from '@/Components/HR/SettingsTable.vue';
import HRSettingModal from '@/Components/HR/HRSettingModal.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();

const props = defineProps({
    employeeTypes: Array,
    jobTitles: Array,
    allowances: Array,
    deductions: Array,
    departments: Array,
    biometricDevices: Array,
    centers: Array,
    shifts: Array,
});

const activeTab = ref('employee_types');

const tabs = computed(() => [
    { key: 'employee_types', label: t('hr.settings.employee_types.title'), icon: '👤' },
    { key: 'job_titles', label: t('hr.settings.job_titles.title'), icon: '💼' },
    { key: 'allowances', label: t('hr.settings.allowances.title'), icon: '💰' },
    { key: 'deductions', label: t('hr.settings.deductions.title'), icon: '📉' },
    { key: 'shifts', label: t('hr.settings.shifts.title'), icon: '⏰' },
    { key: 'biometric_devices', label: t('hr.settings.biometric_devices.title'), icon: '📡' },
]);

// Modal state
const showModal = ref(false);
const modalType = ref('');
const editingItem = ref(null);

function openModal(type, item = null) {
    modalType.value = type;
    editingItem.value = item;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingItem.value = null;
}

async function deleteItem(type, item) {
    const confirmed = await confirm(t('common.confirm_delete'), t('common.delete_confirm_message'));
    if (!confirmed) return;

    const routes = {
        employee_type: 'app.hr.settings.employee-types.destroy',
        job_title: 'app.hr.settings.job-titles.destroy',
        allowance: 'app.hr.settings.allowances.destroy',
        deduction: 'app.hr.settings.deductions.destroy',
    };

    router.delete(route(routes[type], item.id), {
        onSuccess: () => success(t('common.deleted_success')),
    });
}

// Biometric Device Modal
const showDeviceModal = ref(false);
const editingDevice = ref(null);
const deviceForm = ref({
    name: '',
    center_id: '',
    device_id: '',
    device_type: '',
    is_active: true,
    notes: '',
});

function openDeviceModal(device = null) {
    editingDevice.value = device;
    if (device) {
        deviceForm.value = {
            name: device.name,
            center_id: device.center_id,
            device_id: device.device_id || '',
            device_type: device.device_type || '',
            is_active: device.is_active,
            notes: device.notes || '',
        };
    } else {
        deviceForm.value = {
            name: '',
            center_id: props.centers[0]?.id || '',
            device_id: '',
            device_type: '',
            is_active: true,
            notes: '',
        };
    }
    showDeviceModal.value = true;
}

function closeDeviceModal() {
    showDeviceModal.value = false;
    editingDevice.value = null;
}

function saveDevice() {
    if (editingDevice.value) {
        router.put(route('app.hr.settings.biometric-devices.update', editingDevice.value.id), deviceForm.value, {
            onSuccess: () => {
                closeDeviceModal();
                success(t('common.saved_success'));
            },
        });
    } else {
        router.post(route('app.hr.settings.biometric-devices.store'), deviceForm.value, {
            onSuccess: () => {
                closeDeviceModal();
                success(t('common.saved_success'));
            },
        });
    }
}

async function deleteDevice(device) {
    const confirmed = await confirm(t('common.confirm_delete'), t('common.delete_confirm_message'));
    if (!confirmed) return;

    router.delete(route('app.hr.settings.biometric-devices.destroy', device.id), {
        onSuccess: () => success(t('common.deleted_success')),
    });
}

function showDeviceToken(device) {
    fetch(route('app.hr.settings.biometric-devices.show-token', device.id))
        .then(res => res.json())
        .then(data => {
            navigator.clipboard.writeText(data.token);
            success(t('hr.settings.biometric_devices.token_copied'));
        });
}

// Shift Modal
const showShiftModal = ref(false);
const editingShift = ref(null);
const shiftForm = ref({
    name_ar: '',
    name_en: '',
    start_time: '08:00',
    end_time: '16:00',
    color: '#6366f1',
    is_overnight: false,
    is_active: true,
    break_minutes: 60,
});

function openShiftModal(shift = null) {
    editingShift.value = shift;
    if (shift) {
        shiftForm.value = {
            name_ar: shift.name_ar,
            name_en: shift.name_en || '',
            start_time: shift.start_time?.slice(0, 5) || '08:00',
            end_time: shift.end_time?.slice(0, 5) || '16:00',
            color: shift.color || '#6366f1',
            is_overnight: shift.is_overnight || false,
            is_active: shift.is_active,
            break_minutes: shift.break_minutes || 60,
        };
    } else {
        shiftForm.value = {
            name_ar: '',
            name_en: '',
            start_time: '08:00',
            end_time: '16:00',
            color: '#6366f1',
            is_overnight: false,
            is_active: true,
            break_minutes: 60,
        };
    }
    showShiftModal.value = true;
}

function closeShiftModal() {
    showShiftModal.value = false;
    editingShift.value = null;
}

function saveShift() {
    if (editingShift.value) {
        router.put(route('app.hr.settings.shifts.update', editingShift.value.id), shiftForm.value, {
            onSuccess: () => {
                closeShiftModal();
                success(t('common.saved_success'));
            },
        });
    } else {
        router.post(route('app.hr.settings.shifts.store'), shiftForm.value, {
            onSuccess: () => {
                closeShiftModal();
                success(t('common.saved_success'));
            },
        });
    }
}

async function deleteShift(shift) {
    const confirmed = await confirm(t('common.confirm_delete'), t('common.delete_confirm_message'));
    if (!confirmed) return;

    router.delete(route('app.hr.settings.shifts.destroy', shift.id), {
        onSuccess: () => success(t('common.deleted_success')),
    });
}
</script>
