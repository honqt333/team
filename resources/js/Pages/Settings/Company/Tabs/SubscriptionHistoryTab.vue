<template>
    <div class="space-y-6">
        <div
            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/50 pb-4"
        >
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $t('company_profile.tabs.subscription_history') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $t('company_profile.subscription_history.subtitle') }}
                </p>
            </div>
        </div>

        <!--
            Stub component. The full Subscription History tab lives
            in Index.vue between lines 1907-2040. Splitting it out is
            the first step in the Company/Index.vue refactor — see
            docs/REFACTOR_COMPANY_INDEX.md for the full plan.

            This stub exists so the import path in Index.vue resolves
            cleanly during the migration: when a tab is split, the
            parent drops the inline block and renders
            <SubscriptionHistoryTab /> instead. Lifting the
            section-by-section without a behavior change is the
            safest order.
        -->
        <div
            class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-8 text-center"
        >
            <p class="text-sm text-gray-500">
                {{ $t('company_profile.subscription_history.placeholder') }}
            </p>
        </div>
    </div>
</template>

<script setup>
// All state for the Subscription History tab is owned by the
// parent (Index.vue) right now. When the full split lands, this
// component will receive the subscription history as a prop and
// emit 'renew' / 'cancel' events back. For now, the stub has no
// props or emits — it's purely a layout placeholder.
</script>
