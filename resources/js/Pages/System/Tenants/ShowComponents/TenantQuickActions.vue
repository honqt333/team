<template>
    <div class="flex flex-wrap gap-2.5 sm:gap-3">
        <!-- Impersonate (super_admin only — server enforces) -->
        <button
            @click="$emit('impersonate')"
            class="px-4 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                />
            </svg>
            {{ $t('system.tenants.actions.impersonate') }}
        </button>

        <!-- Assign / Change Plan -->
        <button
            @click="$emit('assign-plan')"
            class="px-4 py-2 bg-selection-bg hover:bg-selection-bg text-selection-text border border-action-primary/25 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                />
            </svg>
            {{ $t('system.tenants.assign_plan') }}
        </button>

        <!-- Suspend (super_admin only) -->
        <button
            v-if="tenant.status !== 'suspended'"
            @click="$emit('suspend')"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            {{ $t('system.tenants.actions.suspend') }}
        </button>

        <!-- Activate (super_admin only — hidden if Lift Auto Suspend is already visible) -->
        <button
            v-if="
                tenant.status !== 'active' &&
                !(
                    tenant.auto_suspended_at &&
                    tenant.suspension_reason === 'auto_suspend_dunning_tier_4'
                )
            "
            @click="$emit('activate')"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            {{ $t('system.tenants.actions.activate') }}
        </button>

        <!-- Phase 7.6: dunning opt-out override. We expose this
             as a quick action because the toggle is a privacy-
             relevant change (collection) and admins need a
             one-click way to flip it. The actual dunning
             settings (channels, tiers, language) live on the
             dedicated /system/tenants/{id}/dunning page. -->
        <button
            v-if="tenant.dunning_opted_out"
            @click="$emit('clear-dunning-opt-out')"
            class="px-4 py-2 border border-amber-300 bg-white hover:bg-amber-50 text-amber-700 rounded-lg text-sm font-medium transition-colors"
        >
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                {{ $t('system.tenants.actions.enable_dunning') }}
            </span>
        </button>

        <!-- Extend trial (super_admin only — available for trial or expired tenants) -->
        <button
            v-if="['trial', 'expired'].includes(tenant.status)"
            @click="$emit('extend-trial')"
            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
        >
            {{ $t('system.tenants.actions.extend_trial') }}
        </button>

        <!-- Phase 7.7: lift auto-suspension. The dunning system
             can auto-suspend a tenant when tier_4 reminders
             go unpaid past the grace window. Admins need a
             one-click way to clear that without re-routing to
             the manual suspend/activate flow. We only show
             this when the suspension was applied by the
             dunning system (suspension_reason matches the
             auto-suspend sentinel). -->
        <button
            v-if="
                tenant.auto_suspended_at &&
                tenant.suspension_reason === 'auto_suspend_dunning_tier_4'
            "
            @click="$emit('lift-auto-suspend')"
            class="px-4 py-2 border border-orange-300 bg-white hover:bg-orange-50 text-orange-700 rounded-lg text-sm font-medium transition-colors"
        >
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                    />
                </svg>
                {{ $t('system.tenants.actions.lift_auto_suspend') }}
            </span>
        </button>

        <!-- Edit 2FA (super_admin only) -->
        <button
            @click="$emit('security')"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors"
        >
            {{ $t('system.tenants.actions.security_settings') }}
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
            {{ $t('system.tenants.actions.backup_now') }}
        </button>

        <!-- Delete (super_admin only) -->
        <button
            @click="$emit('delete')"
            class="px-4 py-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors"
        >
            {{ $t('system.tenants.actions.delete_permanent') }}
        </button>

        <!-- Open tenant billing dashboard in a new tab (super_admin only) -->
        <a
            v-if="tenant.id"
            :href="`/app/settings/billing?tenant_id=${tenant.id}`"
            target="_blank"
            class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                />
            </svg>
            {{ $t('system.tenants.actions.view_billing') }}
        </a>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

useI18n();

defineProps({
    tenant: { type: Object, required: true },
});

defineEmits([
    'impersonate',
    'assign-plan',
    'suspend',
    'activate',
    'extend-trial',
    'security',
    'backup',
    'delete',
    'clear-dunning-opt-out',
    'lift-auto-suspend',
]);
</script>
