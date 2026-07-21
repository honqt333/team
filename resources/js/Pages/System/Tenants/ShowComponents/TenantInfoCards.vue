<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Company Info -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    معلومات الشركة
                </h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم التجاري</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.trade_name || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم القانوني</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.legal_name || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">المالك</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.owner_name || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">السجل التجاري</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">
                            {{ tenant.cr_number || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">الرقم الضريبي</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">
                            {{ tenant.vat_number || '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Recent Work Orders -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">آخر الكروت</h2>
                <div v-if="recentWorkOrders?.length" class="space-y-3">
                    <div
                        v-for="wo in recentWorkOrders"
                        :key="wo.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">#{{ wo.code }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ wo.vehicle?.customer?.name || '-' }}
                            </p>
                        </div>
                        <div class="text-end">
                            <span
                                :class="getWorkOrderStatusClass(wo.status)"
                                class="px-2 py-1 rounded-full text-xs"
                            >
                                {{ getWorkOrderStatusLabel(wo.status) }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ formatDate(wo.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="text-center py-4 text-gray-500 dark:text-gray-400">لا توجد كروت</p>
            </div>

            <!-- Centers -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    المراكز ({{ tenant.centers?.length || 0 }})
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="center in tenant.centers"
                        :key="center.id"
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ center.name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ center.phone || center.email }}
                                </p>
                            </div>
                            <span
                                v-if="center.is_main"
                                class="px-2 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-xs"
                            >
                                رئيسي
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="!tenant.centers?.length"
                        class="text-center py-4 text-gray-500 dark:text-gray-400"
                    >
                        لا توجد مراكز
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الحالة</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">الحالة الحالية</dt>
                        <dd class="mt-1">
                            <span
                                :class="getStatusClass(tenant.status)"
                                class="px-2.5 py-1 rounded-full text-xs font-medium"
                            >
                                {{ getStatusLabel(tenant.status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التسجيل</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ formatDate(tenant.created_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.trial_ends_at">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">انتهاء التجربة</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ formatDate(tenant.trial_ends_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.suspended_at">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التعليق</dt>
                        <dd class="text-red-600 dark:text-red-400 font-medium">
                            {{ formatDate(tenant.suspended_at) }}
                        </dd>
                    </div>
                    <div v-if="tenant.suspension_reason">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">سبب التعليق</dt>
                        <dd class="text-red-600 dark:text-red-400">
                            {{ tenant.suspension_reason }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Subscription -->
            <div
                v-if="tenant.subscriptions?.length"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الاشتراك</h2>
                <div v-for="sub in tenant.subscriptions" :key="sub.id" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">الباقة</span>
                        <span class="font-medium text-indigo-600 dark:text-indigo-400">
                            {{ sub.plan?.name_ar }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">الدورة</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ sub.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">ينتهي</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ formatDate(sub.ends_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التواصل</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">الهاتف</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">
                            {{ tenant.phone || '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">البريد</dt>
                        <dd class="text-gray-900 dark:text-white font-medium">
                            {{ tenant.email || '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Security Settings -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">الأمان</h2>
                    <button
                        @click="$emit('security')"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                    >
                        تعديل
                    </button>
                </div>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">المصادقة الثنائية</dt>
                        <dd class="mt-1">
                            <span
                                :class="
                                    tenant.two_factor_enabled
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                "
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
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
</template>

<script setup>
defineProps({
    tenant: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
});

defineEmits(['security']);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
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
    const labels = { active: 'نشط', trial: 'تجريبي', suspended: 'معلّق' };
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

const getEnforcementLabel = (value) => {
    switch (value) {
        case 'required':
            return 'إلزامي';
        case 'optional':
            return 'اختياري';
        case 'disabled':
            return 'غير ملزم';
        default:
            return value;
    }
};
</script>
