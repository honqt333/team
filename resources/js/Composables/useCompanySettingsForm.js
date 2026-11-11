import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

/**
 * The company-settings form: identity, VAT configuration, logo and stamp.
 *
 * This logic lived in TWO components with byte-identical scripts:
 *
 *  - `Pages/System/Settings/CompanySettings.vue` (the standalone page)
 *  - `Pages/System/Settings/Tabs/CompanySettingsTab.vue` (the tab)
 *
 * Their templates have since diverged, but the behaviour has not — so the
 * behaviour lives here and each component keeps its own markup.
 *
 * @param {object} props
 * @param {object} props.settings  the current settings payload
 */
export function useCompanySettingsForm(props) {
    const { t } = useI18n();

    const vatMethodOptions = computed(() => [
        { value: false, label: t('system.company_settings.sections.vat.exclusive') },
        { value: true, label: t('system.company_settings.sections.vat.inclusive') },
    ]);

    const form = useForm({
        company_legal_name: props.settings?.company_legal_name || '',
        company_legal_name_en: props.settings?.company_legal_name_en || '',
        company_vat_number: props.settings?.company_vat_number || '',
        company_cr_number: props.settings?.company_cr_number || '',
        company_phone: props.settings?.company_phone || '',
        company_email: props.settings?.company_email || '',
        company_website: props.settings?.company_website || '',
        subscription_vat_enabled: props.settings?.subscription_vat_enabled ?? false,
        subscription_vat_rate: props.settings?.subscription_vat_rate ?? 15,
        subscription_vat_inclusive: props.settings?.subscription_vat_inclusive ?? false,
    });

    // Live VAT preview computed from a 100.00 sample line.
    const vatPreview = computed(() => {
        const sample = 100;
        const rate = (form.subscription_vat_rate || 0) / 100;
        if (form.subscription_vat_inclusive) {
            const net = sample / (1 + rate);
            const vat = sample - net;
            return {
                net: net.toFixed(2),
                vat: vat.toFixed(2),
                total: sample.toFixed(2),
            };
        }
        const vat = sample * rate;
        return {
            net: sample.toFixed(2),
            vat: vat.toFixed(2),
            total: (sample + vat).toFixed(2),
        };
    });

    // Logo state
    const logoInput = ref(null);
    const uploading = ref(false);
    const localPreview = ref(null);

    const logoUrl = computed(() => props.settings?.company_logo_url || null);
    const logoPreview = computed(() => localPreview.value || logoUrl.value);

    // Stamp state — separate from logo so admins can replace either
    // without affecting the other. Mirrors the logo pattern.
    const stampInput = ref(null);
    const uploadingStamp = ref(false);
    const localStampPreview = ref(null);

    const stampUrl = computed(() => props.settings?.company_stamp_url || null);
    const stampPreview = computed(() => localStampPreview.value || stampUrl.value);

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const onLogoSelect = async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        if (localPreview.value && localPreview.value.startsWith('blob:')) {
            URL.revokeObjectURL(localPreview.value);
        }
        localPreview.value = URL.createObjectURL(file);

        uploading.value = true;
        try {
            const fd = new FormData();
            fd.append('logo', file);

            const res = await fetch('/system/settings/company/upload-logo', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: fd,
            });

            if (!res.ok) {
                localPreview.value = logoUrl.value;
                throw new Error('Upload failed');
            }

            window.location.reload();
        } catch (e) {
            alert(e.message || 'Upload failed');
        } finally {
            uploading.value = false;
            if (logoInput.value) logoInput.value.value = '';
        }
    };

    const confirmRemoveLogo = async () => {
        if (!confirm(t('system.company_settings.sections.branding.remove_confirm'))) {
            return;
        }
        uploading.value = true;
        try {
            const res = await fetch('/system/settings/company/logo', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            if (!res.ok) throw new Error('Remove failed');
            localPreview.value = null;
            window.location.reload();
        } catch (e) {
            alert(e.message);
        } finally {
            uploading.value = false;
        }
    };

    const onStampSelect = async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        if (localStampPreview.value && localStampPreview.value.startsWith('blob:')) {
            URL.revokeObjectURL(localStampPreview.value);
        }
        localStampPreview.value = URL.createObjectURL(file);

        uploadingStamp.value = true;
        try {
            const fd = new FormData();
            fd.append('stamp', file);

            const res = await fetch('/system/settings/company/upload-stamp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: fd,
            });

            if (!res.ok) {
                localStampPreview.value = stampUrl.value;
                throw new Error('Upload failed');
            }

            window.location.reload();
        } catch (e) {
            alert(e.message || 'Upload failed');
        } finally {
            uploadingStamp.value = false;
            if (stampInput.value) stampInput.value.value = '';
        }
    };

    const confirmRemoveStamp = async () => {
        if (!confirm(t('system.company_settings.sections.branding.remove_confirm'))) {
            return;
        }
        uploadingStamp.value = true;
        try {
            const res = await fetch('/system/settings/company/stamp', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            if (!res.ok) throw new Error('Remove failed');
            localStampPreview.value = null;
            window.location.reload();
        } catch (e) {
            alert(e.message);
        } finally {
            uploadingStamp.value = false;
        }
    };

    const submit = () => {
        form.put('/system/settings/company');
    };

    return {
        form,
        vatMethodOptions,
        vatPreview,
        logoInput,
        uploading,
        logoUrl,
        logoPreview,
        stampInput,
        uploadingStamp,
        stampUrl,
        stampPreview,
        onLogoSelect,
        confirmRemoveLogo,
        onStampSelect,
        confirmRemoveStamp,
        submit,
    };
}
