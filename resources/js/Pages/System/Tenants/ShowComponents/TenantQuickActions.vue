<template>
    <div class="flex flex-wrap gap-3">
        <!-- Impersonate (super_admin only — server enforces) -->
        <button
            @click="$emit('impersonate')"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                />
            </svg>
            الدخول للوحة
        </button>

        <!-- Suspend (super_admin only) -->
        <button
            v-if="tenant.status !== 'suspended'"
            @click="$emit('suspend')"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            تعليق الحساب
        </button>

        <!-- Activate (super_admin only) -->
        <button
            v-if="tenant.status !== 'active'"
            @click="$emit('activate')"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            تفعيل الحساب
        </button>

        <!-- Extend trial (super_admin only, trial tenants only) -->
        <button
            v-if="tenant.status === 'trial'"
            @click="$emit('extend-trial')"
            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            تمديد التجربة
        </button>

        <!-- Edit 2FA (super_admin only) -->
        <button
            @click="$emit('security')"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors"
        >
            إعدادات الأمان
        </button>

        <!-- Backup (super_admin only) -->
        <button
            @click="$emit('backup')"
            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                />
            </svg>
            نسخة احتياطية الآن
        </button>

        <!-- Delete (super_admin only) -->
        <button
            @click="$emit('delete')"
            class="px-4 py-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors"
        >
            🗑️ حذف نهائي
        </button>
    </div>
</template>

<script setup>
defineProps({
    tenant: { type: Object, required: true },
});

defineEmits(['impersonate', 'suspend', 'activate', 'extend-trial', 'security', 'backup', 'delete']);
</script>
