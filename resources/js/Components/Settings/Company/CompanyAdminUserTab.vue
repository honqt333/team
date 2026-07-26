<template>
    <div class="space-y-6">
        <!-- Admin Info Header -->
        <div
            class="flex items-center justify-between p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
        >
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-lg"
                >
                    {{ adminUser?.name?.charAt(0) || 'A' }}
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">
                        {{ adminUser?.name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ adminUser?.email }}
                    </p>
                </div>
            </div>
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
            >
                {{ $t('company_profile.admin_user.owner_badge') }}
            </span>
        </div>

        <!-- Admin Email Update Form -->
        <form @submit.prevent="$emit('submit-email')" class="space-y-4 max-w-md">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('company_profile.admin_user.email_label') }}
                </label>
                <input
                    v-model="emailForm.email"
                    type="email"
                    required
                    dir="ltr"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
            </div>
            <button
                type="submit"
                :disabled="updatingEmail"
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
                {{
                    updatingEmail
                        ? $t('common.loading')
                        : $t('company_profile.admin_user.update_email_button')
                }}
            </button>
        </form>
    </div>
</template>

<script setup>
defineProps({
    adminUser: Object,
    emailForm: Object,
    updatingEmail: Boolean,
});

defineEmits(['submit-email']);
</script>
