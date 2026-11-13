<template>
    <div class="space-y-6">
        <!-- Platform Brand Identity (Logo & Favicon) -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100 space-y-6">
                <AppFileUpload
                    variant="image"
                    accept="image/png,image/jpeg,image/webp"
                    :max-size-mb="2"
                    :loading="uploadingLogo"
                    :label="$t('system.settings.brand_logo_title')"
                    :hint="$t('system.settings.brand_logo_hint')"
                    :existing-url="brandLogoPreview || ''"
                    fallback-url="/images/brand/logo-128.png"
                    :default-badge="$t('system.settings.brand_logo_default_badge')"
                    :upload-button-text="$t('system.settings.brand_logo_upload')"
                    :reset-button-text="$t('system.settings.brand_logo_reset')"
                    @select="handleLogoSelected"
                    @remove-existing="resetBrandLogo"
                />

                <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                    <AppFileUpload
                        variant="image"
                        accept="image/png,image/jpeg,image/webp,image/x-icon,.ico"
                        :max-size-mb="1"
                        :loading="uploadingFavicon"
                        :compact-preview="true"
                        :label="$t('system.settings.brand_favicon_title')"
                        :hint="$t('system.settings.brand_favicon_hint')"
                        :existing-url="brandFaviconPreview || ''"
                        fallback-url="/images/brand/favicon-32.png"
                        :default-badge="$t('system.settings.brand_logo_default_badge')"
                        :upload-button-text="$t('system.settings.brand_favicon_upload')"
                        :reset-button-text="$t('system.settings.brand_favicon_reset')"
                        @select="handleFaviconSelected"
                        @remove-existing="resetBrandFavicon"
                    />
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">
                    {{ $t('system.tenants.actions.security_settings') }}
                </h3>

                <div
                    class="flex items-center justify-between py-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">
                            {{ $t('system.settings.enable_sms_2fa') }}
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('system.settings.enable_sms_2fa_hint') }}
                            <br />
                            <span class="text-xs text-amber-500">
                                {{ $t('system.settings.enable_sms_2fa_warning') }}
                            </span>
                        </p>
                    </div>

                    <AppToggle
                        :model-value="form.security_2fa_sms_enabled"
                        @change="updateSetting('security.2fa_sms_enabled', $event)"
                    />
                </div>
            </div>
        </div>

        <!-- Subscription Grace Period Settings -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="max-w-xl">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('system.settings.grace_period_days') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('system.settings.grace_period_days_hint') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="w-24">
                            <AppInput
                                v-model.number="form.subscription_grace_period_days"
                                type="number"
                                min="0"
                                max="365"
                                class="text-center font-mono"
                            />
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('common.days') }}
                        </span>
                        <button
                            type="button"
                            :disabled="savingKey === 'subscription.grace_period_days'"
                            @click="
                                updateSetting(
                                    'subscription.grace_period_days',
                                    form.subscription_grace_period_days
                                )
                            "
                            class="px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold transition-colors disabled:opacity-50 cursor-pointer"
                        >
                            {{ $t('common.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import AppInput from '@/Components/App/AppInput.vue';

const { t } = useI18n();
const { success, error } = useToast();

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const savingKey = ref(null);
const uploadingLogo = ref(false);
const brandLogoPreview = ref(props.settings?.brand_logo_url || null);
const uploadingFavicon = ref(false);
const brandFaviconPreview = ref(props.settings?.brand_favicon_url || null);

const syncBrowserFavicon = (url) => {
    const href = url || '/images/brand/favicon-32.png';
    document.querySelectorAll('link[rel="icon"], link[rel="apple-touch-icon"]').forEach((el) => {
        el.setAttribute('href', href);
    });
};

const form = useForm({
    security_2fa_sms_enabled: props.settings?.security_2fa_sms_enabled || false,
    subscription_grace_period_days: props.settings?.subscription_grace_period_days ?? 7,
});

watch(
    () => props.settings,
    (next) => {
        if (!next) return;
        form.security_2fa_sms_enabled = Boolean(next.security_2fa_sms_enabled);
        form.subscription_grace_period_days = next.subscription_grace_period_days ?? 7;
        brandLogoPreview.value = next.brand_logo_url || null;
        brandFaviconPreview.value = next.brand_favicon_url || null;
        syncBrowserFavicon(brandFaviconPreview.value);
    },
    { deep: true }
);

const handleLogoSelected = (file) => {
    if (!file) return;

    uploadingLogo.value = true;
    router.post(
        route('system.settings.general.upload-logo'),
        { logo: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                success(t('common.saved'));
            },
            onError: (errors) => {
                error(errors.logo || t('common.error_occurred'));
            },
            onFinish: () => {
                uploadingLogo.value = false;
            },
        }
    );
};

const resetBrandLogo = () => {
    uploadingLogo.value = true;
    router.delete(route('system.settings.general.remove-logo'), {
        preserveScroll: true,
        onSuccess: () => {
            brandLogoPreview.value = null;
            success(t('common.saved'));
        },
        onError: () => {
            error(t('common.error_occurred'));
        },
        onFinish: () => {
            uploadingLogo.value = false;
        },
    });
};

const handleFaviconSelected = (file) => {
    if (!file) return;

    uploadingFavicon.value = true;
    router.post(
        route('system.settings.general.upload-favicon'),
        { favicon: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                success(t('common.saved'));
            },
            onError: (errors) => {
                error(errors.favicon || t('common.error_occurred'));
            },
            onFinish: () => {
                uploadingFavicon.value = false;
            },
        }
    );
};

const resetBrandFavicon = () => {
    uploadingFavicon.value = true;
    router.delete(route('system.settings.general.remove-favicon'), {
        preserveScroll: true,
        onSuccess: () => {
            brandFaviconPreview.value = null;
            syncBrowserFavicon(null);
            success(t('common.saved'));
        },
        onError: () => {
            error(t('common.error_occurred'));
        },
        onFinish: () => {
            uploadingFavicon.value = false;
        },
    });
};

const updateSetting = (key, value) => {
    savingKey.value = key;
    form.processing = true;
    router.put(
        route('system.settings.general.update'),
        {
            key: key,
            value: value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('system.settings.saved'));
                form.processing = false;
                savingKey.value = null;
            },
            onError: () => {
                error(t('common.error_occurred'));
                form.processing = false;
                savingKey.value = null;
            },
        }
    );
};
</script>
