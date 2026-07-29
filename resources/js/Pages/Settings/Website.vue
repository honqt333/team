<template>
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                :title="t('nav.website')"
                :subtitle="t('tenant_site.admin.subtitle')"
                gradientFrom="from-pink-500"
                gradientTo="to-rose-600"
                glowFrom="from-pink-500"
                badgeBg="bg-pink-50/50 dark:bg-pink-900/30"
                badgeText="text-pink-600 dark:text-pink-400"
                badgeBorder="border-pink-100/50 dark:border-pink-800/30"
                badgeDot="bg-pink-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                        />
                    </svg>
                </template>
                <template #actions>
                    <a
                        v-if="publicUrl"
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-pink-200 dark:border-pink-700 text-sm font-medium hover:bg-pink-50 dark:hover:bg-pink-900/30"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                        {{ t('tenant_site.admin.preview') }}
                    </a>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Identity -->
                <section
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                >
                    <h2 class="text-lg font-bold">
                        {{ t('tenant_site.admin.sections.identity') }}
                    </h2>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.site_name') }}
                        </label>
                        <input
                            v-model="form.site_name"
                            type="text"
                            maxlength="255"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        <p v-if="errors.site_name" class="text-rose-500 text-sm mt-1">
                            {{ errors.site_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.tagline') }}
                        </label>
                        <input
                            v-model="form.tagline"
                            type="text"
                            maxlength="500"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        <p v-if="errors.tagline" class="text-rose-500 text-sm mt-1">
                            {{ errors.tagline }}
                        </p>
                    </div>
                </section>

                <!-- Logo + Cover -->
                <section
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                >
                    <h2 class="text-lg font-bold">
                        {{ t('tenant_site.admin.sections.branding') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('tenant_site.admin.fields.logo') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-20 h-20 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-900"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        class="w-full h-full object-contain"
                                    />
                                    <svg
                                        v-else
                                        class="w-6 h-6 text-slate-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"
                                        />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                        @change="onLogoChange"
                                        class="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-rose-50 file:text-rose-700 dark:file:bg-rose-900/30 dark:file:text-rose-300"
                                    />
                                    <button
                                        v-if="logoPreview"
                                        type="button"
                                        @click="removeImage('logo')"
                                        class="text-xs text-rose-500 hover:underline self-start"
                                    >
                                        {{ t('tenant_site.admin.remove_image') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('tenant_site.admin.fields.cover') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-20 h-20 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-900"
                                >
                                    <img
                                        v-if="coverPreview"
                                        :src="coverPreview"
                                        class="w-full h-full object-cover"
                                    />
                                    <svg
                                        v-else
                                        class="w-6 h-6 text-slate-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-3H5a2 2 0 00-2 2z"
                                        />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <input
                                        ref="coverInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        @change="onCoverChange"
                                        class="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-rose-50 file:text-rose-700 dark:file:bg-rose-900/30 dark:file:text-rose-300"
                                    />
                                    <button
                                        v-if="coverPreview"
                                        type="button"
                                        @click="removeImage('cover')"
                                        class="text-xs text-rose-500 hover:underline self-start"
                                    >
                                        {{ t('tenant_site.admin.remove_image') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <ColorField
                            v-model="form.primary_color"
                            :label="t('tenant_site.admin.fields.primary_color')"
                            :error="errors.primary_color"
                        />
                        <ColorField
                            v-model="form.secondary_color"
                            :label="t('tenant_site.admin.fields.secondary_color')"
                            :error="errors.secondary_color"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.theme_key') }}
                        </label>
                        <select
                            v-model="form.theme_key"
                            class="w-full md:w-1/2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <option v-for="opt in themeOptions" :key="opt.value" :value="opt.value">
                                {{ isRtl ? opt.label_ar : opt.label_en }}
                            </option>
                        </select>
                        <p v-if="errors.theme_key" class="text-rose-500 text-sm mt-1">
                            {{ errors.theme_key }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ t('tenant_site.admin.fields.theme_key_help') }}
                        </p>
                    </div>
                </section>

                <!-- Contact -->
                <section
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                >
                    <h2 class="text-lg font-bold">{{ t('tenant_site.admin.sections.contact') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('tenant_site.admin.fields.contact_phone') }}
                            </label>
                            <input
                                v-model="form.contact_phone"
                                type="tel"
                                maxlength="30"
                                dir="ltr"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <p v-if="errors.contact_phone" class="text-rose-500 text-sm mt-1">
                                {{ errors.contact_phone }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('tenant_site.admin.fields.contact_whatsapp') }}
                            </label>
                            <input
                                v-model="form.contact_whatsapp"
                                type="tel"
                                maxlength="30"
                                dir="ltr"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <p v-if="errors.contact_whatsapp" class="text-rose-500 text-sm mt-1">
                                {{ errors.contact_whatsapp }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.contact_email') }}
                        </label>
                        <input
                            v-model="form.contact_email"
                            type="email"
                            maxlength="255"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        <p v-if="errors.contact_email" class="text-rose-500 text-sm mt-1">
                            {{ errors.contact_email }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.address') }}
                        </label>
                        <input
                            v-model="form.address_line"
                            type="text"
                            maxlength="500"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        <p v-if="errors.address_line" class="text-rose-500 text-sm mt-1">
                            {{ errors.address_line }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium">
                            {{ t('tenant_site.admin.fields.social_links') }}
                        </label>
                        <div
                            v-for="(label, key) in socialLabels"
                            :key="key"
                            class="flex items-center gap-2"
                        >
                            <span class="w-24 text-xs text-slate-500">{{ label }}</span>
                            <input
                                v-model="form.social_links[key]"
                                type="url"
                                :placeholder="`https://...`"
                                maxlength="500"
                                dir="ltr"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"
                            />
                        </div>
                    </div>
                </section>

                <!-- SEO -->
                <section
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                >
                    <h2 class="text-lg font-bold">{{ t('tenant_site.admin.sections.seo') }}</h2>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.seo_title') }}
                        </label>
                        <input
                            v-model="form.seo_title"
                            type="text"
                            maxlength="70"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        <p v-if="errors.seo_title" class="text-rose-500 text-sm mt-1">
                            {{ errors.seo_title }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.seo_description') }}
                        </label>
                        <textarea
                            v-model="form.seo_description"
                            rows="2"
                            maxlength="200"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        ></textarea>
                        <p v-if="errors.seo_description" class="text-rose-500 text-sm mt-1">
                            {{ errors.seo_description }}
                        </p>
                    </div>
                </section>

                <!-- Publication -->
                <section
                    class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4"
                >
                    <h2 class="text-lg font-bold">{{ t('tenant_site.admin.sections.publish') }}</h2>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('tenant_site.admin.fields.status') }}
                        </label>
                        <select
                            v-model="form.status"
                            class="w-full md:w-1/2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <option value="draft">{{ t('tenant_site.admin.status.draft') }}</option>
                            <option value="published">
                                {{ t('tenant_site.admin.status.published') }}
                            </option>
                            <option value="unpublished">
                                {{ t('tenant_site.admin.status.unpublished') }}
                            </option>
                        </select>
                        <p v-if="errors.status" class="text-rose-500 text-sm mt-1">
                            {{ errors.status }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ t('tenant_site.admin.fields.status_help') }}
                        </p>
                    </div>
                </section>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold disabled:opacity-60"
                        :style="{ backgroundColor: form.primary_color }"
                    >
                        <span v-if="form.processing">…</span>
                        {{ t('tenant_site.admin.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ColorField from '@/Components/ColorField.vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    website: { type: Object, default: () => ({}) },
    theme_options: { type: Array, default: () => [] },
});

// Defensive normalization: the page can be reached via the
// tenant settings hub (which renders the same component for
// a placeholder card) or directly through the edit route.
// When the hub renders it, props.website is undefined. The
// `withDefaults` above sets it to {}, but we also coerce
// here so destructuring never crashes. Every reads below
// uses `w.X` where `w = props.website ?? {}`.
const w = props.website ?? {};

const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => (page.props.locale || 'ar') === 'ar');
const t = tRaw;

const themeOptions = computed(() => props.theme_options);
const socialLabels = {
    facebook: 'Facebook',
    twitter: 'X',
    instagram: 'Instagram',
    linkedin: 'LinkedIn',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};

const form = useForm({
    site_name: w.site_name || '',
    tagline: w.tagline || '',
    contact_phone: w.contact_phone || '',
    contact_whatsapp: w.contact_whatsapp || '',
    contact_email: w.contact_email || '',
    address_line: w.address_line || '',
    social_links: {
        facebook: w.social_links?.facebook || '',
        twitter: w.social_links?.twitter || '',
        instagram: w.social_links?.instagram || '',
        linkedin: w.social_links?.linkedin || '',
        tiktok: w.social_links?.tiktok || '',
        youtube: w.social_links?.youtube || '',
    },
    primary_color: w.primary_color || '#4f46e5',
    secondary_color: w.secondary_color || '#0ea5e9',
    status: w.status || 'draft',
    seo_title: w.seo_title || '',
    seo_description: w.seo_description || '',
    theme_key: w.theme_key || 'default',
    logo: null,
    cover: null,
});

const errors = form.errors;

const logoPreview = ref(w.logo_url || null);
const coverPreview = ref(w.cover_url || null);

function onLogoChange(e) {
    const file = e.target.files?.[0];
    if (!file) {
        return;
    }
    form.logo = file;
    const reader = new FileReader();
    reader.onload = (ev) => (logoPreview.value = ev.target.result);
    reader.readAsDataURL(file);
}

function onCoverChange(e) {
    const file = e.target.files?.[0];
    if (!file) {
        return;
    }
    form.cover = file;
    const reader = new FileReader();
    reader.onload = (ev) => (coverPreview.value = ev.target.result);
    reader.readAsDataURL(file);
}

async function removeImage(key) {
    if (!confirm(t('tenant_site.admin.confirm_remove_image'))) {
        return;
    }
    try {
        const res = await fetch(route('settings.website.image.delete'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ key }),
        });
        if (res.ok) {
            if (key === 'logo') {
                logoPreview.value = null;
                form.logo = null;
            } else {
                coverPreview.value = null;
                form.cover = null;
            }
        }
    } catch (e) {
        // ignore for now
    }
}

function submit() {
    // Laravel parses multipart/form-data reliably on POST but
    // not always on PUT (the multipart parser in PHP reads from
    // php://input, which behaves differently across SAPI layers
    // for PUT requests). To get the file uploads into the
    // request, we send as POST and use the Inertia `transform`
    // hook to inject Laravel's method-spoofing field. The route
    // stays mounted as PUT — Laravel reads `_method=put` and
    // dispatches to the right handler.
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('settings.website.update'), {
        preserveScroll: true,
    });
}

const publicUrl = computed(() => {
    // We do not know the tenant slug here; the admin user
    // can always navigate to the public URL through the
    // page header link. We expose the form's current state
    // so the preview opens after the next save.
    return w.tenant_slug ? `/${w.tenant_slug}` : null;
});
</script>
