<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppCheckbox from '@/Components/App/AppCheckbox.vue';
import AppButton from '@/Components/App/AppButton.vue';

const props = defineProps({
    show: Boolean,
    role: Object,
    duplicateFrom: {
        type: Object,
        default: null,
    },
    groupedPermissions: {
        type: Object,
        default: () => ({}),
    },
    permissionDescriptions: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();

const form = useForm({
    name: '',
    label_ar: '',
    label_en: '',
    description: '',
    permissions: [],
});

const activeModules = ref([]);
const permissionSearch = ref('');
const isEditing = computed(() => !!props.role);
const title = computed(() =>
    isEditing.value ? t('roles.edit', 'تعديل الدور') : t('roles.add', 'إضافة دور جديد')
);

// Auto-generate slug from English label or Arabic label fallback when creating
watch(
    () => form.label_en,
    (newVal) => {
        if (!isEditing.value && newVal && !form.name) {
            form.name = newVal
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_|_$/g, '');
        }
    }
);

// Watch show to reliably populate or reset form every time modal opens
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            permissionSearch.value = '';
            if (props.role) {
                form.name = props.role.name || '';
                form.label_ar = props.role.label_ar || '';
                form.label_en = props.role.label_en || '';
                form.description = props.role.description || '';

                if (Array.isArray(props.role.permissions)) {
                    form.permissions = props.role.permissions.map((p) =>
                        typeof p === 'string' ? p : p.name
                    );
                } else {
                    form.permissions = [];
                }
            } else if (props.duplicateFrom) {
                const suffix = t('roles.copy_suffix', '(نسخة)');
                form.name = props.duplicateFrom.name ? `${props.duplicateFrom.name}_copy` : '';
                form.label_ar = props.duplicateFrom.label_ar
                    ? `${props.duplicateFrom.label_ar} ${suffix}`
                    : '';
                form.label_en = props.duplicateFrom.label_en
                    ? `${props.duplicateFrom.label_en} (Copy)`
                    : '';
                form.description = props.duplicateFrom.description || '';
                form.permissions = Array.isArray(props.duplicateFrom.permissions)
                    ? props.duplicateFrom.permissions.map((p) =>
                          typeof p === 'string' ? p : p.name
                      )
                    : [];
            } else {
                form.reset();
                form.name = '';
                form.label_ar = '';
                form.label_en = '';
                form.description = '';
                form.permissions = [];
            }

            // Open first module by default
            const moduleKeys = Object.keys(props.groupedPermissions || {});
            if (moduleKeys.length > 0 && activeModules.value.length === 0) {
                activeModules.value = [moduleKeys[0]];
            }
        }
    },
    { immediate: true }
);

const getPermissionLabel = (permission) => {
    if (props.permissionDescriptions && props.permissionDescriptions[permission]) {
        return props.permissionDescriptions[permission];
    }
    return t('permissions.' + permission.replace(/\./g, '_'), permission);
};

const filteredGroupedPermissions = computed(() => {
    const query = permissionSearch.value.trim().toLowerCase();
    const groups = props.groupedPermissions || {};

    if (!query) {
        return groups;
    }

    const result = {};
    for (const [moduleName, perms] of Object.entries(groups)) {
        const moduleLabel = t('permissions_modules.' + moduleName, moduleName).toLowerCase();
        if (moduleLabel.includes(query)) {
            result[moduleName] = perms;
            continue;
        }
        const matched = perms.filter(
            (p) =>
                p.toLowerCase().includes(query) ||
                getPermissionLabel(p).toLowerCase().includes(query)
        );
        if (matched.length > 0) {
            result[moduleName] = matched;
        }
    }
    return result;
});

const allModuleKeys = computed(() => Object.keys(filteredGroupedPermissions.value));
const allVisiblePermissions = computed(() =>
    Object.values(filteredGroupedPermissions.value).flat()
);
const isEveryPermissionSelected = computed(
    () =>
        allVisiblePermissions.value.length > 0 &&
        allVisiblePermissions.value.every((p) => form.permissions.includes(p))
);

const toggleExpandAll = () => {
    if (activeModules.value.length === allModuleKeys.value.length) {
        activeModules.value = [];
    } else {
        activeModules.value = [...allModuleKeys.value];
    }
};

const toggleSelectAllPermissions = () => {
    const visible = allVisiblePermissions.value;
    if (isEveryPermissionSelected.value) {
        form.permissions = form.permissions.filter((p) => !visible.includes(p));
    } else {
        const toAdd = visible.filter((p) => !form.permissions.includes(p));
        form.permissions.push(...toAdd);
    }
};

const toggleModule = (moduleName) => {
    if (activeModules.value.includes(moduleName)) {
        activeModules.value = activeModules.value.filter((m) => m !== moduleName);
    } else {
        activeModules.value.push(moduleName);
    }
};

const toggleAllInModule = (moduleName, permissions) => {
    const allSelected = permissions.every((p) => form.permissions.includes(p));
    if (allSelected) {
        form.permissions = form.permissions.filter((p) => !permissions.includes(p));
    } else {
        const toAdd = permissions.filter((p) => !form.permissions.includes(p));
        form.permissions.push(...toAdd);
    }
};

const isAllSelected = (permissions) => {
    return permissions.every((p) => form.permissions.includes(p));
};

const getModuleSelectedCount = (permissions) => {
    return permissions.filter((p) => form.permissions.includes(p)).length;
};

const submit = () => {
    // If name is empty, auto-fill from label_en or fallback
    if (!form.name && form.label_en) {
        form.name = form.label_en
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
    }

    if (isEditing.value) {
        form.put(route('settings.roles.update', props.role.id), {
            onSuccess: () => {
                emit('close');
                emit('saved');
            },
            preserveScroll: true,
        });
    } else {
        form.post(route('settings.roles.store'), {
            onSuccess: () => {
                emit('close');
                emit('saved');
                form.reset();
            },
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <BaseModal :show="show" :title="title" size="4xl" @close="emit('close')">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Row 1: Arabic & English Names -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                    v-slot="field"
                    :label="t('roles.label_ar', 'الاسم بالعربية')"
                    :error="form.errors.label_ar"
                    required
                    id="label_ar"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.label_ar"
                        type="text"
                        class="w-full"
                        required
                        :placeholder="t('roles.label_ar_placeholder', 'مثال: مدير المبيعات')"
                        autofocus
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="t('roles.label_en', 'الاسم بالإنجليزية')"
                    :error="form.errors.label_en"
                    required
                    id="label_en"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.label_en"
                        type="text"
                        class="w-full"
                        dir="ltr"
                        :placeholder="t('roles.label_en_placeholder', 'Ex: Sales Manager')"
                        required
                    />
                </FormField>
            </div>

            <!-- Row 2: System Slug Name -->
            <FormField
                v-slot="field"
                :label="t('roles.system_name', 'اسم النظام (Slug)')"
                :error="form.errors.name"
                :hint="
                    t(
                        'roles.system_name_hint',
                        'معرّف برمجي فريد بدون مسافات، يُنشأ تلقائياً من الاسم الإنجليزي'
                    )
                "
                required
                id="role_name"
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name"
                    type="text"
                    dir="ltr"
                    class="w-full font-mono text-sm"
                    required
                    :disabled="isEditing && form.name === 'super_admin'"
                    :placeholder="t('roles.system_name_placeholder', 'e.g. sales_manager')"
                />
            </FormField>

            <!-- Row 3: Description -->
            <FormField
                v-slot="field"
                id="description"
                :label="t('roles.description', 'الوصف')"
                :error="form.errors.description"
            >
                <AppTextarea
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :aria-invalid="field.hasError ? 'true' : undefined"
                    v-model="form.description"
                    :rows="2"
                    :placeholder="
                        t('roles.description_placeholder', 'وصف مختصر لمهام هذا الدور...')
                    "
                />
            </FormField>

            <!-- Row 4: Permissions Tree -->
            <div class="border border-border rounded-2xl overflow-hidden bg-surface">
                <div
                    class="bg-surface-muted px-4 py-3 border-b border-border flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm text-text-primary">
                            {{ t('roles.permissions', 'الصلاحيات') }}
                        </span>
                        <span class="text-xs text-text-muted">
                            ({{ form.permissions.length }}
                            {{ t('roles.selected_permissions', 'صلاحية محددة') }})
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative w-full sm:w-56">
                            <AppInput
                                v-model="permissionSearch"
                                type="text"
                                :placeholder="t('roles.search_permissions', 'ابحث في الصلاحيات...')"
                                class="ps-8 py-1.5 text-xs"
                            />
                            <svg
                                class="absolute start-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-text-muted pointer-events-none"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>

                        <button
                            type="button"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-border bg-surface text-text-secondary hover:text-text-primary hover:bg-surface-secondary transition-colors cursor-pointer"
                            @click="toggleExpandAll"
                        >
                            {{
                                activeModules.length === allModuleKeys.length &&
                                allModuleKeys.length > 0
                                    ? t('roles.collapse_all', 'طي الكل')
                                    : t('roles.expand_all', 'توسيع الكل')
                            }}
                        </button>

                        <button
                            type="button"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-selection-ring/40 bg-selection-bg/50 text-action-primary hover:bg-selection-bg transition-colors cursor-pointer"
                            @click="toggleSelectAllPermissions"
                        >
                            {{
                                isEveryPermissionSelected
                                    ? t('roles.deselect_all_permissions', 'إلغاء تحديد الكل')
                                    : t('roles.select_all_permissions', 'تحديد الكل')
                            }}
                        </button>
                    </div>
                </div>

                <div
                    class="max-h-[50vh] overflow-y-auto divide-y divide-border bg-surface custom-scrollbar"
                >
                    <div
                        v-if="allModuleKeys.length === 0"
                        class="p-8 text-center text-sm text-text-muted"
                    >
                        {{ t('roles.no_matching_permissions', 'لا توجد صلاحيات مطابقة لبحثك') }}
                    </div>

                    <div
                        v-for="(perms, moduleName) in filteredGroupedPermissions"
                        :key="moduleName"
                    >
                        <!-- Module Header (Accordion Toggle) -->
                        <div
                            class="flex items-center justify-between px-4 py-3 bg-surface-muted/40 hover:bg-surface-muted transition-colors cursor-pointer select-none"
                            @click="toggleModule(moduleName)"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg
                                    class="w-4 h-4 text-text-muted transition-transform duration-200"
                                    :class="{
                                        'rotate-180':
                                            activeModules.includes(moduleName) ||
                                            permissionSearch.trim().length > 0,
                                    }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                                <span class="font-medium text-sm text-text-primary">
                                    {{ t('permissions_modules.' + moduleName, moduleName) }}
                                </span>
                                <span
                                    v-if="getModuleSelectedCount(perms) > 0"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold bg-selection-bg text-action-primary border border-selection-ring/30"
                                >
                                    {{ getModuleSelectedCount(perms) }} / {{ perms.length }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="text-xs text-action-primary hover:underline font-semibold cursor-pointer"
                                @click.stop="toggleAllInModule(moduleName, perms)"
                            >
                                {{
                                    isAllSelected(perms)
                                        ? t('roles.deselect_all', 'إلغاء تحديد الكل')
                                        : t('roles.select_all', 'تحديد الكل')
                                }}
                            </button>
                        </div>

                        <!-- Module Permissions Grid -->
                        <div
                            v-show="
                                activeModules.includes(moduleName) ||
                                permissionSearch.trim().length > 0
                            "
                            class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 bg-surface"
                        >
                            <label
                                v-for="permission in perms"
                                :key="permission"
                                class="flex items-start gap-3 p-2.5 rounded-xl border border-transparent hover:border-border hover:bg-surface-muted/60 cursor-pointer transition-all select-none"
                                :class="
                                    form.permissions.includes(permission)
                                        ? 'bg-selection-bg/30 border-selection-ring/40'
                                        : ''
                                "
                            >
                                <AppCheckbox
                                    v-model="form.permissions"
                                    :value="permission"
                                    class="mt-0.5 shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <span
                                        class="block text-xs sm:text-sm font-medium text-text-primary leading-snug break-words"
                                    >
                                        {{ getPermissionLabel(permission) }}
                                    </span>
                                    <span
                                        class="text-[11px] text-text-muted font-mono block mt-0.5 break-all"
                                    >
                                        {{ permission }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-border">
                <AppButton variant="secondary" @click="emit('close')">
                    {{ t('common.cancel', 'إلغاء') }}
                </AppButton>
                <AppButton type="submit" :loading="form.processing">
                    {{
                        form.processing
                            ? t('common.saving', 'جاري الحفظ...')
                            : isEditing
                              ? t('common.update', 'تحديث')
                              : t('common.save', 'حفظ')
                    }}
                </AppButton>
            </div>
        </form>
    </BaseModal>
</template>
