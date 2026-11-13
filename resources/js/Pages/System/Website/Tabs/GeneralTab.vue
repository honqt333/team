<script setup>
import { ref } from 'vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import AppInput from '@/Components/App/AppInput.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({ form: Object, uploadImage: Function });
const { error: toastError } = useToast();

const uploadingLogo = ref(false);
const uploadingFavicon = ref(false);

async function onLogoSelect(file) {
    if (!file) return;
    if (props.uploadImage) {
        uploadingLogo.value = true;
        try {
            const url = await props.uploadImage('website_logo', file);
            props.form.settings.website_logo = url;
        } catch (err) {
            console.error('Logo upload failed', err);
        } finally {
            uploadingLogo.value = false;
        }
    } else {
        props.form.settings.website_logo = file;
    }
}

async function onFaviconSelect(file) {
    if (!file) return;
    if (props.uploadImage) {
        uploadingFavicon.value = true;
        try {
            const url = await props.uploadImage('website_favicon', file);
            props.form.settings.website_favicon = url;
        } catch (err) {
            console.error('Favicon upload failed', err);
        } finally {
            uploadingFavicon.value = false;
        }
    } else {
        props.form.settings.website_favicon = file;
    }
}
</script>

<template>
    <div>
        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-6">
            {{ $t('tenant_site.general.visual_identity') }}
        </h3>
        <div class="grid grid-cols-1 gap-y-8 gap-x-6 sm:grid-cols-6">
            <div
                class="sm:col-span-6 border border-gray-200 dark:border-gray-700 rounded-lg p-5 space-y-3"
            >
                <AppFileUpload
                    variant="image"
                    :label="$t('tenant_site.general.main_logo')"
                    :hint="$t('tenant_site.general.logo_hint')"
                    :existing-url="
                        typeof form.settings.website_logo === 'string'
                            ? form.settings.website_logo
                            : null
                    "
                    :loading="uploadingLogo"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    :max-size-mb="4"
                    :upload-button-text="$t('tenant_site.general.browse_files')"
                    @select="onLogoSelect"
                    @invalid="toastError"
                    @remove-existing="form.settings.website_logo = ''"
                />
                <AppInput
                    v-model="form.settings.website_logo"
                    type="text"
                    :placeholder="$t('system.website.logo_url_placeholder')"
                    dir="ltr"
                />
            </div>

            <div class="sm:col-span-3">
                <label
                    class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                    <span>{{ $t('tenant_site.admin.fields.site_name') }}</span>
                    <span class="badge-lang">AR</span>
                </label>
                <AppInput v-model="form.settings.website_title_ar" type="text" />
            </div>
            <div class="sm:col-span-3">
                <label
                    class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    dir="ltr"
                >
                    <span>{{ $t('system.website.site_title_en') }}</span>
                    <span class="badge-lang">EN</span>
                </label>
                <AppInput
                    v-model="form.settings.website_title_en"
                    type="text"
                    class="text-left"
                    dir="ltr"
                />
            </div>

            <div class="sm:col-span-3">
                <AppFileUpload
                    variant="image"
                    compact-preview
                    :label="$t('tenant_site.general.favicon')"
                    :existing-url="
                        typeof form.settings.website_favicon === 'string'
                            ? form.settings.website_favicon
                            : null
                    "
                    :loading="uploadingFavicon"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    :max-size-mb="2"
                    :upload-button-text="$t('tenant_site.general.update_favicon')"
                    @select="onFaviconSelect"
                    @invalid="toastError"
                    @remove-existing="form.settings.website_favicon = ''"
                />
            </div>

            <div class="sm:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('tenant_site.general.brand_color') }}
                </label>
                <div class="flex gap-2 items-center">
                    <input
                        v-model="form.settings.website_primary_color"
                        type="color"
                        class="h-10 w-12 p-0.5 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer bg-white"
                    />
                    <div class="flex-1">
                        <AppInput
                            v-model="form.settings.website_primary_color"
                            type="text"
                            class="font-mono"
                            dir="ltr"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
