<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <TenantHeader :tenant="tenant" />

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
                @delete="modals.delete = true"
            />

            <!-- Main Info Cards -->
            <TenantInfoCards
                :tenant="tenant"
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
import { reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import TenantHeader from './ShowComponents/TenantHeader.vue';
import TenantAnalyticsCards from './ShowComponents/TenantAnalyticsCards.vue';
import TenantQuickActions from './ShowComponents/TenantQuickActions.vue';
import TenantInfoCards from './ShowComponents/TenantInfoCards.vue';
import TenantModals from './ShowComponents/TenantModals.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    analytics: { type: Object, required: true },
    recentWorkOrders: { type: Array, default: () => [] },
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
        { reason: reason || 'لم يحدد سبب' },
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
</script>
