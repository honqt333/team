<template>
    <AppLayout>
        <Head :title="$t('service_bays.title')" />

        <div class="px-4 sm:px-6 py-6 space-y-6">
            <PageHeader
                :title="$t('service_bays.title')"
                :subtitle="$t('service_bays.subtitle')"
                gradientFrom="from-indigo-600"
                gradientTo="to-cyan-600"
                glowFrom="from-indigo-500"
            >
                <template #back>
                    <BackButton href="/app/settings" />
                </template>

                <template #icon>
                    <svg
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- New / Edit form -->
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5"
            >
                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-3">
                    {{ form.id ? $t('service_bays.edit_bay') : $t('service_bays.add_bay') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        v-model="form.name"
                        type="text"
                        :placeholder="$t('service_bays.name_placeholder')"
                        class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                    />
                    <input
                        v-model="form.code"
                        type="text"
                        :placeholder="$t('service_bays.code_placeholder')"
                        class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                    />
                    <SearchableSelect
                        v-model="form.type"
                        :options="typeOptions"
                        option-label="label"
                        option-value="value"
                        :compact="true"
                    />
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input v-model="form.is_active" type="checkbox" class="rounded" />
                        {{ $t('service_bays.active') }}
                    </label>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <button
                        type="button"
                        @click="save"
                        :disabled="busy || !form.name.trim()"
                        class="px-3 py-1.5 rounded-md text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50"
                    >
                        {{ form.id ? $t('common.save') : $t('common.create') }}
                    </button>
                    <button
                        v-if="form.id"
                        type="button"
                        @click="reset"
                        class="px-3 py-1.5 rounded-md text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                </div>
            </div>

            <!-- List -->
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden"
            >
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th
                                class="px-4 py-2 text-start text-xs font-bold text-gray-700 dark:text-gray-200"
                            >
                                {{ $t('service_bays.name') }}
                            </th>
                            <th
                                class="px-4 py-2 text-start text-xs font-bold text-gray-700 dark:text-gray-200"
                            >
                                {{ $t('service_bays.code') }}
                            </th>
                            <th
                                class="px-4 py-2 text-start text-xs font-bold text-gray-700 dark:text-gray-200"
                            >
                                {{ $t('service_bays.type') }}
                            </th>
                            <th
                                class="px-4 py-2 text-start text-xs font-bold text-gray-700 dark:text-gray-200"
                            >
                                {{ $t('service_bays.status') }}
                            </th>
                            <th
                                class="px-4 py-2 text-end text-xs font-bold text-gray-700 dark:text-gray-200"
                            >
                                {{ $t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="bay in bays" :key="bay.id">
                            <td class="px-4 py-2 text-sm">{{ bay.name }}</td>
                            <td
                                class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400"
                            >
                                {{ bay.code || '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                {{ bay.type ? $t(`service_bays.types.${bay.type}`) : '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span
                                    :class="
                                        bay.is_active
                                            ? 'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                >
                                    {{
                                        bay.is_active ? $t('common.active') : $t('common.inactive')
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-end space-x-2">
                                <button
                                    type="button"
                                    @click="edit(bay)"
                                    class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    {{ $t('common.edit') }}
                                </button>
                                <button
                                    type="button"
                                    @click="remove(bay)"
                                    :disabled="busy"
                                    class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline disabled:opacity-50"
                                >
                                    {{ $t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!bays.length">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">
                                {{ $t('service_bays.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    bays: { type: Array, default: () => [] },
});

const { t } = useI18n();
const busy = ref(false);

const types = ['maintenance', 'polish', 'tires', 'electrical', 'wash', 'inspection', 'general'];

const typeOptions = computed(() => [
    { value: '', label: t('service_bays.no_type') },
    ...types.map((key) => ({
        value: key,
        label: t(`service_bays.types.${key}`),
    })),
]);

const form = reactive({
    id: null,
    name: '',
    code: '',
    type: '',
    is_active: true,
});

function reset() {
    form.id = null;
    form.name = '';
    form.code = '';
    form.type = '';
    form.is_active = true;
}

function edit(bay) {
    form.id = bay.id;
    form.name = bay.name;
    form.code = bay.code || '';
    form.type = bay.type || '';
    form.is_active = !!bay.is_active;
}

async function save() {
    if (!form.name.trim()) return;
    busy.value = true;
    try {
        if (form.id) {
            await axios.put(route('service-bays.update', { serviceBay: form.id }), form);
        } else {
            await axios.post(route('service-bays.store'), form);
        }
        reset();
        router.reload();
    } finally {
        busy.value = false;
    }
}

async function remove(bay) {
    if (bay.has_active_appointments) {
        alert(t('service_bays.has_active_appointments'));
        return;
    }
    if (!confirm(t('service_bays.confirm_delete'))) return;
    busy.value = true;
    try {
        await axios.delete(route('service-bays.destroy', { serviceBay: bay.id }));
        reset();
        router.reload();
    } finally {
        busy.value = false;
    }
}
</script>
