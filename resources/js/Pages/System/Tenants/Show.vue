<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <TenantHeader :tenant="tenant" />

            <!-- Health Score — surfaces the worst pain points at a glance -->
            <TenantHealthScore
                v-if="healthScore"
                :score="healthScore.score"
                :flags="healthScore.flags"
            />

            <!-- Usage Analytics Cards -->
            <TenantAnalyticsCards :analytics="analytics" />

            <!-- Quick Actions -->
            <TenantQuickActions
                :tenant="tenant"
                @impersonate="impersonateTenant"
                @suspend="modals.suspend = true"
                @activate="activateTenant"
                @extend-trial="modals.extend = true"
                @security="modals.security = true"
                @backup="createBackup"
                @delete="modals.delete = true"
            />

            <!-- Customer 360 Tabs — Overview / Subscription / Invoices /
                 Payments / Receipts / Activity. Replaces the previous
                 flat "Info Cards" section and gives the admin one place
                 to see every touchpoint with this tenant. -->
            <TenantTabs
                :tenant="tenant"
                :bank-transfer-receipts="bankTransferReceipts"
                :activity-log="activityLog"
                :recent-work-orders="recentWorkOrders"
                @security="modals.security = true"
            />

            <!-- Modals -->
            <TenantModals
                :modals="modals"
                :tenant="tenant"
                :analytics="analytics"
                :security-form="securityForm"
                @close="closeModal"
                @save-security="updateSecurity"
                @confirm-suspend="suspendTenant"
                @confirm-extend="extendTrial"
                @confirm-delete="deleteTenant"
            />
        </div>
    </SystemLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import TenantHeader from './ShowComponents/TenantHeader.vue';
import TenantHealthScore from './ShowComponents/TenantHealthScore.vue';
import TenantAnalyticsCards from './ShowComponents/TenantAnalyticsCards.vue';
import TenantQuickActions from './ShowComponents/TenantQuickActions.vue';
import TenantTabs from './ShowComponents/TenantTabs.vue';
import TenantModals from './ShowComponents/TenantModals.vue';

const { t } = useI18n();

const props = defineProps({
    tenant: { type: Object, required: true },
    analytics: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
    bankTransferReceipts: { type: Array, default: () => [] },
    healthScore: { type: Object, default: null },
    activityLog: { type: Array, default: () => [] },
});

const modals = reactive({
    suspend: false,
    extend: false,
    delete: false,
    security: false,
});

const securityForm = useForm({
    two_factor_enabled: Boolean(props.tenant.two_factor_enabled),
    two_factor_enforcement: props.tenant.two_factor_enforcement || 'disabled',
});

const closeModal = (name) => {
    modals[name] = false;
};

const updateSecurity = () => {
    securityForm.put(`/system/tenants/${props.tenant.id}/security`, {
        onSuccess: () => {
            modals.security = false;
        },
    });
};

const suspendTenant = (reason) => {
    router.post(
        `/system/tenants/${props.tenant.id}/suspend`,
        { reason: reason || t('system.tenants.show.modals.suspend_no_reason') },
        {
            onSuccess: () => {
                modals.suspend = false;
            },
        }
    );
};

const activateTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/activate`);
};

const extendTrial = (days) => {
    router.post(
        `/system/tenants/${props.tenant.id}/extend-trial`,
        { days },
        {
            onSuccess: () => {
                modals.extend = false;
            },
        }
    );
};

const deleteTenant = (confirmation) => {
    router.delete(`/system/tenants/${props.tenant.id}`, {
        data: { confirmation },
    });
};

const impersonateTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/impersonate`);
};

const createBackup = () => {
    router.post(`/system/tenants/${props.tenant.id}/backups`, {});
};
</script>
