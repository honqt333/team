<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Back & Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/system/tenants" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400">{{ tenant.legal_name }}</p>
                    </div>
                </div>
                <span :class="getStatusClass(tenant.status)" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    {{ getStatusLabel(tenant.status) }}
                </span>
            </div>
            
            <!-- Usage Analytics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.customers }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">عميل</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.vehicles }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">مركبة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.work_orders }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">كرت</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.invoices }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">فاتورة</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.users }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">مستخدم</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.centers }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">مركز</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-10 h-10 mx-auto bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.storage_mb || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">MB التخزين</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3">
                <button 
                    @click="impersonateTenant"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    الدخول للوحة
                </button>
                <button 
                    v-if="tenant.status !== 'suspended'"
                    @click="showSuspendModal = true"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تعليق الحساب
                </button>
                <button 
                    v-if="tenant.status === 'suspended'"
                    @click="activateTenant"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تفعيل الحساب
                </button>
                <button 
                    v-if="tenant.status === 'trial'"
                    @click="showExtendModal = true"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تمديد التجربة
                </button>
                <button 
                    @click="showDeleteModal = true"
                    class="px-4 py-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors"
                >
                    🗑️ حذف نهائي
                </button>
            </div>
            
            <!-- Info Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Company Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">معلومات الشركة</h2>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم التجاري</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.trade_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم القانوني</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.legal_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">المالك</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.owner_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">السجل التجاري</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.cr_number || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الرقم الضريبي</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.vat_number || '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <!-- Recent Work Orders -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">آخر الكروت</h2>
                        <div v-if="recentWorkOrders?.length" class="space-y-3">
                            <div v-for="wo in recentWorkOrders" :key="wo.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">#{{ wo.code }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ wo.vehicle?.customer?.name || '-' }}</p>
                                </div>
                                <div class="text-end">
                                    <span :class="getWorkOrderStatusClass(wo.status)" class="px-2 py-1 rounded-full text-xs">
                                        {{ getWorkOrderStatusLabel(wo.status) }}
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(wo.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-center py-4 text-gray-500 dark:text-gray-400">لا توجد كروت</p>
                    </div>
                    
                    <!-- Centers -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">المراكز ({{ tenant.centers?.length || 0 }})</h2>
                        <div class="space-y-3">
                            <div v-for="center in tenant.centers" :key="center.id" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ center.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ center.phone || center.email }}</p>
                                    </div>
                                    <span v-if="center.is_main" class="px-2 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-xs">
                                        رئيسي
                                    </span>
                                </div>
                            </div>
                            <div v-if="!tenant.centers?.length" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                لا توجد مراكز
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الحالة</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الحالة الحالية</dt>
                                <dd class="mt-1">
                                    <span :class="getStatusClass(tenant.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(tenant.status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التسجيل</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ formatDate(tenant.created_at) }}</dd>
                            </div>
                            <div v-if="tenant.trial_ends_at">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">انتهاء التجربة</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ formatDate(tenant.trial_ends_at) }}</dd>
                            </div>
                            <div v-if="tenant.suspended_at">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التعليق</dt>
                                <dd class="text-red-600 dark:text-red-400 font-medium">{{ formatDate(tenant.suspended_at) }}</dd>
                            </div>
                            <div v-if="tenant.suspension_reason">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">سبب التعليق</dt>
                                <dd class="text-red-600 dark:text-red-400">{{ tenant.suspension_reason }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <!-- Subscription -->
                    <div v-if="tenant.subscriptions?.length" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الاشتراك</h2>
                        <div v-for="sub in tenant.subscriptions" :key="sub.id" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">الباقة</span>
                                <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ sub.plan?.name_ar }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">الدورة</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ sub.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 dark:text-gray-300">ينتهي</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(sub.ends_at) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التواصل</h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الهاتف</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.phone || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">البريد</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.email || '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <!-- Security Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">الأمان</h2>
                            <button @click="showSecurityModal = true" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">تعديل</button>
                        </div>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">المصادقة الثنائية</dt>
                                <dd class="mt-1">
                                    <span :class="tenant.two_factor_enabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ tenant.two_factor_enabled ? 'مفعلة' : 'معطلة' }}
                                    </span>
                                </dd>
                            </div>
                            <div v-if="tenant.two_factor_enabled">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">مستوى الإلزام</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white font-medium">
                                    {{ getEnforcementLabel(tenant.two_factor_enforcement) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Settings Modal -->
        <div v-if="showSecurityModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showSecurityModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إعدادات الأمان</h3>
                    <form @submit.prevent="updateSecurity">
                        <div class="mb-4">
                            <label class="flex items-center gap-2 cursor-pointer mb-4">
                                <input type="checkbox" v-model="securityForm.two_factor_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">تفعيل المصادقة الثنائية (2FA) للمستأجر</span>
                            </label>
                            
                            <div v-if="securityForm.two_factor_enabled">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">مستوى الإلزام</label>
                                <SearchableSelect
                                    v-model="securityForm.two_factor_enforcement"
                                    :options="twoFactorOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="اختر مستوى الإلزام"
                                />
                                <p class="text-xs text-gray-500 mt-2">
                                    يحدد هذا الإعداد ما إذا كان يجب على مستخدمي المستأجر تفعيل المصادقة الثنائية.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button type="button" @click="showSecurityModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                            <button type="submit" :disabled="securityForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Suspend Modal -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- ... existing modal content ... -->
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showSuspendModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تعليق الحساب</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">سبب التعليق</label>
                        <textarea v-model="suspendReason" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showSuspendModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button @click="suspendTenant" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">تعليق</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Extend Trial Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- ... existing modal content ... -->
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showExtendModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تمديد الفترة التجريبية</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">عدد الأيام</label>
                        <input v-model.number="extendDays" type="number" min="1" max="90" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showExtendModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button @click="extendTrial" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">تمديد</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- ... existing modal content ... -->
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">⚠️ حذف نهائي</h3>
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-300">
                            سيتم حذف المستأجر <strong>{{ tenant.name }}</strong> وجميع بياناته نهائياً:
                        </p>
                        <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                            <li>{{ analytics.users }} مستخدم</li>
                            <li>{{ analytics.centers }} مركز</li>
                            <li>{{ analytics.customers }} عميل</li>
                            <li>{{ analytics.work_orders }} كرت</li>
                        </ul>
                        <p class="mt-2 text-sm text-red-700 dark:text-red-300 font-bold">
                            هذا الإجراء لا يمكن التراجع عنه!
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            اكتب "<span class="font-bold text-red-600">{{ tenant.name }}</span>" للتأكيد
                        </label>
                        <input v-model="deleteConfirmation" type="text" class="w-full px-4 py-2.5 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showDeleteModal = false; deleteConfirmation = ''" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button 
                            @click="deleteTenant" 
                            :disabled="deleteConfirmation !== tenant.name"
                            :class="deleteConfirmation === tenant.name ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                            class="px-4 py-2 text-white rounded-lg"
                        >
                            حذف نهائي
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    tenant: Object,
    analytics: Object,
    recentWorkOrders: Array,
});

const showSuspendModal = ref(false);
const showExtendModal = ref(false);
const showDeleteModal = ref(false);
const showSecurityModal = ref(false);
const suspendReason = ref('');
const extendDays = ref(14);
const deleteConfirmation = ref('');

const twoFactorOptions = [
    { value: 'disabled', label: 'غير ملزم (اختياري)' },
    { value: 'optional', label: 'اختياري (ينصح به)' },
    { value: 'required', label: 'إلزامي (للمستخدمين الجدد)' },
];

const securityForm = useForm({
    two_factor_enabled: Boolean(props.tenant.two_factor_enabled),
    two_factor_enforcement: props.tenant.two_factor_enforcement || 'disabled',
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getEnforcementLabel = (value) => {
    switch (value) {
        case 'required': return 'إلزامي';
        case 'optional': return 'اختياري';
        case 'disabled': return 'غير ملزم';
        default: return value;
    }
};

const updateSecurity = () => {
    securityForm.put(`/system/tenants/${props.tenant.id}/security`, {
        onSuccess: () => {
            showSecurityModal.value = false;
        },
    });
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};


const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        suspended: 'معلّق',
    };
    return labels[status] || status || 'غير محدد';
};

const getWorkOrderStatusClass = (status) => {
    const classes = {
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        invoiced: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getWorkOrderStatusLabel = (status) => {
    const labels = {
        open: 'مفتوح',
        in_progress: 'قيد العمل',
        completed: 'مكتمل',
        invoiced: 'مفوتر',
    };
    return labels[status] || status;
};

const suspendTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/suspend`, {
        reason: suspendReason.value,
    }, {
        onSuccess: () => {
            showSuspendModal.value = false;
            suspendReason.value = '';
        },
    });
};

const activateTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/activate`);
};

const extendTrial = () => {
    router.post(`/system/tenants/${props.tenant.id}/extend-trial`, {
        days: extendDays.value,
    }, {
        onSuccess: () => {
            showExtendModal.value = false;
            extendDays.value = 14;
        },
    });
};

const deleteTenant = () => {
    if (deleteConfirmation.value !== props.tenant.name) return;
    
    router.delete(`/system/tenants/${props.tenant.id}`, {
        data: { confirmation: deleteConfirmation.value },
    });
};

const impersonateTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/impersonate`);
};
</script>
