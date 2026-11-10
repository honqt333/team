<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import FormField from '@/Components/Common/FormField.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppButton from '@/Components/App/AppButton.vue';
const props = defineProps({ entries: { type: Array, default: () => [] }, meta: Object });
const { t } = useI18n();
const level = ref(props.meta?.level || '');
const tail = ref(props.meta?.tail || 200);
const levels = computed(() => [
    { value: '', label: t('system.error_log.all_levels') },
    ...['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'].map(
        (value) => ({ value, label: t(`system.error_log.levels.${value}`) })
    ),
]);
const columns = computed(() =>
    ['timestamp', 'level', 'message'].map((key) => ({ key, label: t(`system.error_log.${key}`) }))
);
function refresh() {
    router.get(
        route('system.error-log.index'),
        { level: level.value || undefined, tail: tail.value },
        { preserveScroll: true }
    );
}
</script>
<template>
    <SystemLayout :title="t('system.error_log.title')">
        <div class="space-y-5">
            <PageHeader
                :title="t('system.error_log.title')"
                :subtitle="t('system.error_log.description')"
            >
                <template #actions>
                    <AppButton @click="refresh">{{ t('system.error_log.refresh') }}</AppButton>
                </template>
            </PageHeader>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField :label="t('system.error_log.level')">
                    <SearchableSelect v-model="level" :options="levels" />
                </FormField>
                <FormField :label="t('system.error_log.limit')">
                    <SearchableSelect
                        v-model="tail"
                        :options="
                            [100, 200, 500, 1000, 2000].map((value) => ({
                                value,
                                label: String(value),
                            }))
                        "
                    />
                </FormField>
            </div>
            <p v-if="meta?.truncated" class="text-sm text-amber-600">
                {{ t('system.error_log.truncated') }}
            </p>
            <DataTable
                :columns="columns"
                :data="entries"
                :empty-title="t('system.error_log.empty')"
                :show-pagination="false"
            >
                <template #cell-message="{ item }">
                    <pre class="whitespace-pre-wrap break-all text-xs">{{ item.message }}</pre>
                </template>
            </DataTable>
        </div>
    </SystemLayout>
</template>
