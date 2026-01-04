<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useToast } from '@/Composables/useToast';
import enLang from '@/i18n/lang/en.json';

const props = defineProps({
    show: Boolean,
    role: Object,
    groupedPermissions: Object,
    permissionDescriptions: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();

const form = useForm({
    name: '',
    label_ar: '',
    label_en: '',
    description: '',
    permissions: [],
});

const activeModules = ref([]);

// Auto-generate slug from English label
watch(() => form.label_en, (newVal) => {
    if (!props.role && newVal) {
        form.name = newVal.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    }
});

// Open first module by default
watch(() => props.groupedPermissions, (newVal) => {
    if (newVal && Object.keys(newVal).length > 0 && activeModules.value.length === 0) {
        activeModules.value = [Object.keys(newVal)[0]];
    }
}, { immediate: true });

watch(() => props.role, (newVal) => {
    if (newVal) {
        form.name = newVal.name;
        form.label_ar = newVal.label_ar || '';
        form.label_en = newVal.label_en || '';
        form.description = newVal.description || '';
        
        if (newVal.permissions) {
             form.permissions = newVal.id ? newVal.permissions.map(p => p.name) : [];
        }
    } else {
        form.reset();
        form.permissions = [];
    }
}, { immediate: true });

const title = computed(() => props.role ? t('roles.edit') : t('roles.add'));

const toggleModule = (moduleName) => {
    if (activeModules.value.includes(moduleName)) {
        activeModules.value = activeModules.value.filter(m => m !== moduleName);
    } else {
        activeModules.value.push(moduleName);
    }
};

const toggleAllInModule = (moduleName, permissions) => {
    const allSelected = permissions.every(p => form.permissions.includes(p));
    if (allSelected) {
        form.permissions = form.permissions.filter(p => !permissions.includes(p));
    } else {
        const toAdd = permissions.filter(p => !form.permissions.includes(p));
        form.permissions.push(...toAdd);
    }
};

const isAllSelected = (permissions) => {
    return permissions.every(p => form.permissions.includes(p));
};

const submit = () => {
    if (props.role) {
        form.put(route('settings.roles.update', props.role.id), {
            onSuccess: () => {
                success(t('messages.saved_success'));
                emit('close');
                emit('saved');
            },
            onError: () => error(t('common.error_occurred')),
            preserveScroll: true
        });
    } else {
        form.post(route('settings.roles.store'), {
            onSuccess: () => {
                success(t('messages.saved_success'));
                emit('close');
                emit('saved');
                form.reset();
            },
            onError: () => error(t('common.error_occurred')),
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="4xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                {{ title }}
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Arabic Name -->
                    <div>
                        <InputLabel for="label_ar" :value="t('roles.label_ar')" />
                        <TextInput
                            id="label_ar"
                            v-model="form.label_ar"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            placeholder="مثال: مدير المبيعات"
                            autofocus
                        />
                        <InputError :message="form.errors.label_ar" class="mt-2" />
                    </div>

                    <!-- English Name -->
                    <div>
                        <InputLabel for="label_en" :value="t('roles.label_en')" />
                        <TextInput
                            id="label_en"
                            v-model="form.label_en"
                            type="text"
                            class="mt-1 block w-full"
                            dir="ltr"
                            placeholder="Ex: Sales Manager"
                            required
                        />
                        <InputError :message="form.errors.label_en" class="mt-2" />
                    </div>
                </div>

                <!-- Generated Slug (Hidden or Read-only) -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-medium">{{ t('roles.system_name') }}:</span>
                        <code class="bg-gray-200 dark:bg-gray-800 px-1.5 py-0.5 rounded text-xs">{{ form.name }}</code>
                    </div>
                </div>
                <InputError :message="form.errors.name" class="mt-2" />

                <!-- Description -->
                <div>
                     <InputLabel for="description" :value="t('roles.description')" />
                     <textarea
                        id="description"
                        v-model="form.description"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-md shadow-sm"
                        rows="2"
                    ></textarea>
                     <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <!-- Permissions Tree -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-medium text-gray-900 dark:text-white">
                        {{ t('roles.permissions') }}
                    </div>
                    
                    <div class="max-h-[60vh] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName">
                            <div 
                                class="flex items-center justify-between px-4 py-3 bg-gray-50/50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                @click="toggleModule(moduleName)"
                            >
                                <div class="flex items-center gap-2">
                                    <svg 
                                        class="w-4 h-4 text-gray-500 transition-transform duration-200"
                                        :class="{ 'rotate-180': activeModules.includes(moduleName) }"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ t('permissions_modules.' + moduleName) }}</span>
                                </div>
                                
                                <button 
                                    type="button" 
                                    @click.stop="toggleAllInModule(moduleName, perms)"
                                    class="text-xs text-violet-600 dark:text-violet-400 hover:text-violet-700 font-medium"
                                >
                                    {{ isAllSelected(perms) ? t('roles.deselect_all') : t('roles.select_all') }}
                                </button>
                            </div>

                            <div v-show="activeModules.includes(moduleName)" class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <label 
                                    v-for="permission in perms" 
                                    :key="permission"
                                    class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                                >
                                    <Checkbox 
                                        v-model:checked="form.permissions" 
                                        :value="permission"
                                        class="mt-1"
                                    />
                                    <div>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100 break-all">
                                            {{ permissionDescriptions && permissionDescriptions[permission] ? permissionDescriptions[permission] : t('permissions.' + permission.replace(/\./g, '_')) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ permission }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                        @click="$emit('close')"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-violet-600 border border-transparent rounded-lg hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? t('common.saving') : t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
