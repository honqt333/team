<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            {{ $t('hr.employees.add') }}
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name AR -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.name') }} (AR) <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_ar"
                        type="text"
                        :class="[
                            'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                            form.errors.name_ar || (form.hasErrors && !form.name_ar) ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                        ]"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.name_ar }}</p>
                </div>

                <!-- Name EN -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.name') }} (EN) <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_en"
                        type="text"
                        :class="[
                            'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                            form.errors.name_en || (form.hasErrors && !form.name_en) ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                        ]"
                    />
                    <p v-if="form.errors.name_en" class="mt-1 text-xs text-red-500">{{ form.errors.name_en }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('common.phone') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        dir="ltr"
                        :class="[
                            'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                            form.errors.phone || (form.hasErrors && !form.phone) ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                        ]"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                </div>

                <!-- Gender -->
                <div>
                    <SearchableSelect
                        v-model="form.gender"
                        :label="$t('hr.employees.gender')"
                        :options="[{id: 'male', name: $t('common.male')}, {id: 'female', name: $t('common.female')}]"
                        option-label="name"
                        option-value="id"
                        :error="form.errors.gender"
                        required
                    />
                </div>

                <!-- Nationality -->
                <div>
                     <SearchableSelect
                        v-model="form.nationality_id"
                        :label="$t('hr.employees.nationality')"
                        :options="nationalities"
                        :option-label="(opt) => locale === 'ar' ? opt.name_ar : opt.name_en || opt.name_ar"
                        option-value="id"
                        :error="form.errors.nationality_id"
                        required
                    />
                </div>

                <!-- Job Title -->
                <div>
                    <SearchableSelect
                        v-model="form.job_title_id"
                        :label="$t('hr.employees.job_title')"
                        :options="jobTitles"
                        :option-label="(opt) => opt.name_ar"
                        option-value="id"
                        :error="form.errors.job_title_id"
                        required
                    />
                </div>

                <!-- Branch -->
                <div class="md:col-span-2">
                     <SearchableSelect
                        v-model="form.center_id"
                        :label="$t('hr.employees.branch')"
                        :options="[{id: null, name: '🏠 ' + $t('common.management')}, ...centers.map(c => ({...c, name: '📍 ' + c.name}))]"
                        option-label="name"
                        option-value="id"
                        :error="form.errors.center_id"
                        required
                    />
                </div>

                <!-- Working Hours -->
                <div class="md:col-span-2 border-t border-gray-100 dark:border-gray-700 pt-4 mt-2">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">{{ $t('hr.employees.working_hours') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('hr.employees.shift_start') }}
                            </label>
                            <input
                                v-model="form.shift_start"
                                type="time"
                                :class="[
                                    'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                                    form.errors.shift_start ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                                ]"
                            />
                            <p v-if="form.errors.shift_start" class="mt-1 text-xs text-red-500">{{ form.errors.shift_start }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('hr.employees.shift_end') }}
                            </label>
                            <input
                                v-model="form.shift_end"
                                type="time"
                                :class="[
                                    'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                                    form.errors.shift_end ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                                ]"
                            />
                            <p v-if="form.errors.shift_end" class="mt-1 text-xs text-red-500">{{ form.errors.shift_end }}</p>
                        </div>
                    </div>

                    <!-- أو اختيار وردية -->
                    <div v-if="shifts?.length" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SearchableSelect
                            v-model="form.default_shift_id"
                            :label="$t('hr.settings.shifts.shift') + ' (اختياري)'"
                            :options="shifts"
                            :option-label="locale === 'ar' ? 'name_ar' : 'name_en'"
                            option-value="id"
                            :error="form.errors.default_shift_id"
                            :placeholder="$t('hr.employees.or_select_shift')"
                        />
                        <p class="mt-1 text-xs text-gray-500">عند اختيار وردية، سيتم استخدامها بدلاً من الأوقات اليدوية</p>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 disabled:opacity-50"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const { t, locale } = useI18n();
const { success } = useToast();

const props = defineProps({
    show: Boolean,
    jobTitles: Array,
    nationalities: Array,
    centers: Array,
    shifts: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name_ar: '',
    name_en: '',
    phone: '',
    gender: null,
    nationality_id: null,
    job_title_id: null,
    center_id: null, // null = Management
    shift_start: null,
    shift_end: null,
    default_shift_id: null,
    hasErrors: false,
});

function submit() {
    form.hasErrors = false;
    form.post(route('app.hr.employees.store'), {
        onSuccess: () => {
            success(t('common.saved_success'));
            form.reset();
            form.hasErrors = false;
            emit('saved');
        },
        onError: () => {
             form.hasErrors = true;
        }
    });
}
</script>
