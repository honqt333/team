<template>
    <Head :title="$t('vehicles.title')" />

    <StandardReportLayout
        :title="$t('vehicles.title')"
        :total-count="vehicles.length"
        :total-count-label="$t('vehicles.total_count')"
        :custom-company="companyInfo"
        :on-print="handlePrint"
        :on-close="handleBack"
        back-route="vehicles.index"
    >
        <table class="w-full text-xs text-center border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th class="py-2.5 px-2 border border-gray-400 font-black">#</th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('vehicles.form.plate') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('vehicles.form.make') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('vehicles.form.model') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('vehicles.form.year') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('vehicles.form.customer') }}
                    </th>
                    <th class="py-2.5 px-2 border border-gray-400 font-black">
                        {{ $t('customers.form.phone') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="vehicles.length === 0">
                    <td colspan="7" class="py-6 px-4 border border-gray-300 text-gray-500">
                        {{ $t('common.no_data') }}
                    </td>
                </tr>
                <tr
                    v-for="(vehicle, index) in vehicles"
                    :key="vehicle.id"
                    class="border-b border-gray-300"
                    :class="index % 2 === 1 ? 'bg-gray-50' : 'bg-white'"
                >
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-500">
                        {{ index + 1 }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono font-bold">
                        {{ vehicle.plate_number || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-bold">
                        {{
                            isRtl
                                ? vehicle.make?.name_ar || vehicle.make?.name_en || '—'
                                : vehicle.make?.name_en || vehicle.make?.name_ar || '—'
                        }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300">
                        {{
                            isRtl
                                ? vehicle.model?.name_ar || vehicle.model?.name_en || '—'
                                : vehicle.model?.name_en || vehicle.model?.name_ar || '—'
                        }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono">
                        {{ vehicle.year || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-bold text-start">
                        {{ vehicle.customer?.name || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-300 font-mono text-gray-700" dir="ltr">
                        {{ vehicle.customer?.phone || '—' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </StandardReportLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import { printDocument } from '@/Composables/usePrint';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

defineProps({
    vehicles: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            vat_number: '',
            cr_number: '',
            logo_path: '',
            center_name: '',
            phone: '',
        }),
    },
});

function handlePrint() {
    printDocument();
}

function handleBack() {
    if (window.opener) {
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                router.visit(route('vehicles.index'));
            }
        }, 150);
        return;
    }
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.close();
        setTimeout(() => {
            router.visit(route('vehicles.index'));
        }, 150);
    }
}
</script>
