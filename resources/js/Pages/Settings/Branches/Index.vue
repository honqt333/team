<template>
    <AppLayout>
        <Head :title="$t('company_profile.branches.title')" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('company_profile.branches.title')"
                :subtitle="$t('company_profile.branches.subtitle')"
                :total-count="toEnglish(props.branches?.length || 0)"
                :count-label="$t('company_profile.branches.count_label')"
            >
                <template #back>
                    <BackButton href="/app/settings" />
                </template>

                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Glass Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="branches_view_mode"
                            />
                        </div>

                        <!-- Outer-left CTA -->
                        <button
                            v-if="can('settings.centers.manage') || isAnyAdmin()"
                            type="button"
                            @click="showCreateModal = true"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">{{ $t('company_profile.branches.add') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Empty State -->
            <AppEmptyState
                v-if="!props.branches?.length"
                :title="$t('company_profile.branches.empty')"
                :description="$t('company_profile.branches.empty_hint')"
            >
                <template #actions>
                    <button
                        v-if="can('settings.centers.manage') || isAnyAdmin()"
                        type="button"
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('company_profile.branches.add') }}</span>
                    </button>
                </template>
            </AppEmptyState>

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable
                    v-if="viewMode === 'list'"
                    :items="props.branches"
                    :columns="columns"
                    @rowClick="(branch) => router.visit(`/app/settings/centers/${branch.id}`)"
                >
                    <template #cell-name="{ item }">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-surface-secondary border border-border-subtle flex items-center justify-center overflow-hidden shrink-0"
                            >
                                <img
                                    v-if="branchLogo(item)"
                                    :src="branchLogo(item)"
                                    :alt="item.name"
                                    class="w-full h-full object-contain p-1"
                                />
                                <img
                                    v-else-if="$page.props.tenant?.logo_url"
                                    :src="$page.props.tenant.logo_url"
                                    :alt="$page.props.tenant.name"
                                    class="w-full h-full object-contain p-1.5 opacity-80"
                                />
                                <svg
                                    v-else
                                    class="w-5 h-5 text-action-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-content-primary">
                                    {{ item.name }}
                                </div>
                                <div class="text-xs text-content-muted font-mono">
                                    #{{ toEnglish(item.id) }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-center_type="{ item }">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-action-primary/10 text-action-primary"
                        >
                            {{
                                item.center_type
                                    ? $t(
                                          `company_profile.branches.center_type_${item.center_type}`,
                                          item.center_type
                                      )
                                    : '—'
                            }}
                        </span>
                    </template>

                    <template #cell-manager_name="{ item }">
                        <span class="text-sm text-content-primary font-medium">
                            {{ item.manager_name || '—' }}
                        </span>
                    </template>

                    <template #cell-contact="{ item }">
                        <div class="space-y-0.5 text-xs text-content-muted">
                            <div
                                v-if="item.phone"
                                dir="ltr"
                                class="font-medium text-content-secondary"
                            >
                                {{ item.phone }}
                            </div>
                            <div v-if="item.email" class="truncate max-w-[200px]">
                                {{ item.email }}
                            </div>
                            <span v-if="!item.phone && !item.email">—</span>
                        </div>
                    </template>

                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'px-2.5 py-1 rounded-full text-xs font-bold',
                                item.is_active
                                    ? 'bg-success-soft text-success'
                                    : 'bg-surface-tertiary text-content-muted',
                            ]"
                        >
                            {{
                                item.is_active
                                    ? $t('company_profile.branches.active')
                                    : $t('company_profile.branches.inactive')
                            }}
                        </span>
                    </template>

                    <template #cell-actions="{ item }">
                        <Link
                            :href="`/app/settings/centers/${item.id}`"
                            @click.stop
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-action-primary hover:bg-action-primary/10 transition-colors"
                        >
                            <span>{{ $t('company_profile.branches.settings') }}</span>
                        </Link>
                    </template>
                </DataTable>

                <!-- Grid View -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="branch in props.branches"
                        :key="branch.id"
                        :href="`/app/settings/centers/${branch.id}`"
                        class="flex flex-col p-5 rounded-2xl border border-border-subtle bg-surface-primary hover:border-action-primary/50 hover:shadow-md transition-all group shadow-2xs"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <!-- Dynamic Logo Display -->
                            <div
                                class="w-12 h-12 rounded-xl bg-surface-secondary border border-border-subtle flex items-center justify-center overflow-hidden shadow-2xs transition-all"
                            >
                                <img
                                    v-if="branchLogo(branch)"
                                    :src="branchLogo(branch)"
                                    :alt="branch.name"
                                    class="w-full h-full object-contain p-1"
                                />
                                <img
                                    v-else-if="$page.props.tenant?.logo_url"
                                    :src="$page.props.tenant.logo_url"
                                    :alt="$page.props.tenant.name"
                                    class="w-full h-full object-contain p-1.5 opacity-80 group-hover:opacity-100 transition-opacity"
                                />
                                <div
                                    v-else
                                    class="w-full h-full bg-action-primary/10 text-action-primary flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-xs font-bold',
                                    branch.is_active
                                        ? 'bg-success-soft text-success'
                                        : 'bg-surface-tertiary text-content-muted',
                                ]"
                            >
                                {{
                                    branch.is_active
                                        ? $t('company_profile.branches.active')
                                        : $t('company_profile.branches.inactive')
                                }}
                            </span>
                        </div>

                        <!-- Commercial Name (Tenant) -->
                        <p class="text-xs text-content-muted mb-0.5">
                            {{ $page.props.tenant?.trade_name || $page.props.tenant?.name }}
                        </p>

                        <!-- Center Name -->
                        <h3 class="text-lg font-bold text-content-primary mb-0.5">
                            {{ branch.name }}
                        </h3>

                        <!-- Center Type -->
                        <p class="text-sm font-semibold text-action-primary mb-3">
                            {{
                                branch.center_type
                                    ? $t(
                                          `company_profile.branches.center_type_${branch.center_type}`,
                                          branch.center_type
                                      )
                                    : '—'
                            }}
                        </p>

                        <!-- Manager Name -->
                        <div
                            v-if="branch.manager_name"
                            class="flex items-center gap-2 mb-3 text-xs text-content-secondary bg-surface-secondary/70 p-2 rounded-xl border border-border-subtle"
                        >
                            <svg
                                class="w-3.5 h-3.5 text-content-muted shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            <span class="font-medium">{{ branch.manager_name }}</span>
                        </div>

                        <!-- Contact Info -->
                        <div class="space-y-1.5 mb-4">
                            <div
                                v-if="branch.phone"
                                class="flex items-center gap-2 text-sm text-content-secondary"
                            >
                                <svg
                                    class="w-4 h-4 text-content-muted shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                    />
                                </svg>
                                <span dir="ltr">{{ branch.phone }}</span>
                            </div>
                            <div
                                v-if="branch.email"
                                class="flex items-center gap-2 text-sm text-content-secondary"
                            >
                                <svg
                                    class="w-4 h-4 text-content-muted shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                                <span class="truncate">{{ branch.email }}</span>
                            </div>
                        </div>

                        <!-- System ID & Action -->
                        <div
                            class="flex items-center justify-between border-t border-border-subtle pt-3 mt-auto"
                        >
                            <span class="text-xs text-content-muted font-mono">
                                #{{ toEnglish(branch.id) }}
                            </span>
                            <span
                                class="text-sm font-bold text-action-primary group-hover:underline transition-colors"
                            >
                                {{ $t('company_profile.branches.settings') }}
                            </span>
                        </div>
                    </Link>
                </div>
            </template>

            <!-- Add Branch Form Modal -->
            <BranchFormModal :show="showCreateModal" @close="showCreateModal = false" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import BranchFormModal from './BranchFormModal.vue';
import { usePermission } from '@/Composables/usePermission';
import { useTheme } from '@/Composables/useTheme';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { isDark } = useTheme();
const { toEnglish } = useNumberFormat();

const showCreateModal = ref(false);
const viewMode = ref('grid');

const columns = computed(() => [
    { key: 'name', label: t('company_profile.branches.name') },
    { key: 'center_type', label: t('company_profile.branches.center_type') },
    { key: 'manager_name', label: t('company_profile.branches.manager_name') },
    { key: 'contact', label: t('company_profile.contact.title') },
    { key: 'status', label: t('common.status'), align: 'center' },
    { key: 'actions', label: t('common.actions'), align: 'center' },
]);

function branchLogo(branch) {
    if (isDark.value) {
        return branch.logo_dark_url || branch.logo_light_url || null;
    }
    return branch.logo_light_url || null;
}
</script>
