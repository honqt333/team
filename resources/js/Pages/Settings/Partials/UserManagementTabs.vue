<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { can } = usePermission();

defineProps({
    activeTab: {
        type: String,
        required: true,
        validator: (value) => ['users', 'roles'].includes(value),
    },
});
</script>

<template>
    <div class="mb-6 overflow-x-auto no-scrollbar">
        <nav
            class="inline-flex p-1.5 bg-surface-muted/90 rounded-2xl border border-border max-w-full overflow-x-auto no-scrollbar gap-1"
            aria-label="Tabs"
        >
            <Link
                v-if="can('users.view')"
                :href="route('settings.users.index')"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm whitespace-nowrap transition-all duration-200 focus:outline-none',
                    activeTab === 'users'
                        ? 'bg-surface text-action-primary shadow-xs font-semibold'
                        : 'font-medium text-text-secondary hover:text-text-primary hover:bg-surface/40',
                ]"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                </svg>
                {{ t('users.title') }}
            </Link>

            <Link
                v-if="can('users.view')"
                :href="route('settings.roles.index')"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm whitespace-nowrap transition-all duration-200 focus:outline-none',
                    activeTab === 'roles'
                        ? 'bg-surface text-action-primary shadow-xs font-semibold'
                        : 'font-medium text-text-secondary hover:text-text-primary hover:bg-surface/40',
                ]"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                    />
                </svg>
                {{ t('roles.title') }}
            </Link>
        </nav>
    </div>
</template>
