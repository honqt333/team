<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';


const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();

const form = useForm({
    name: '',
    name_ar: '',
    name_en: '',
    center_type: '',
    manager_name: '',
    phone: '',
    email: '',
    is_active: true,
});


const submit = () => {
    form.post(route('settings.branches.store'), {
        onSuccess: () => {
            success(t('company_profile.branches.created_successfully'));
            emit('close');
            emit('saved');
            form.reset();
        },
        onError: () => error(t('common.error_occurred')),
        preserveScroll: true
    });
};
</script>

<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ t('company_profile.branches.add') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('company_profile.branches.subtitle') }}</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name General -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ t('company_profile.branches.name') }} <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    required
                    :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                        form.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                    :placeholder="t('company_profile.branches.name')"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>

            <!-- Name AR + Name EN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        الاسم بالعربية <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_ar"
                        type="text"
                        required
                        dir="rtl"
                        :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                            form.errors.name_ar ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                        placeholder="الاسم بالعربية"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_ar }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        الاسم بالإنجليزية <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_en"
                        type="text"
                        required
                        dir="ltr"
                        :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                            form.errors.name_en ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                        placeholder="Branch Name in English"
                    />
                    <p v-if="form.errors.name_en" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_en }}</p>
                </div>
            </div>

            <!-- Center Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ t('company_profile.branches.center_type') }} <span class="text-red-500">*</span>
                </label>
                <select
                    v-model="form.center_type"
                    required
                    :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all',
                        form.errors.center_type ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                >
                    <option value="">{{ t('common.select') }}</option>
                    <option value="main">الفرع الرئيسي / Main Branch</option>
                    <option value="branch">فرع فرعي / Sub Branch</option>
                    <option value="workshop">ورشة / Workshop</option>
                    <option value="warehouse">مستودع / Warehouse</option>
                </select>
                <p v-if="form.errors.center_type" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.center_type }}</p>
            </div>

            <!-- Manager + Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ t('company_profile.branches.manager_name') }}
                    </label>
                    <input
                        v-model="form.manager_name"
                        type="text"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ t('company_profile.branches.phone') }}
                    </label>
                    <input
                        v-model="form.phone"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    />
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ t('company_profile.branches.email') }}
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    dir="ltr"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('common.active') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">تفعيل الفرع لبدء العمل عليه</p>
                </div>
                <button
                    type="button"
                    @click="form.is_active = !form.is_active"
                    :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                        form.is_active ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700']"
                >
                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm',
                        form.is_active ? 'translate-x-6 rtl:-translate-x-6' : 'translate-x-1 rtl:-translate-x-1']" />
                </button>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                {{ t('common.cancel') }}
            </button>
            <button @click="submit" :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                {{ form.processing ? t('common.loading') : t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>
