<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppCheckbox from '@/Components/App/AppCheckbox.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
    role: Object,
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
const { success, error } = useToast();

const form = useForm({
    name: '',
    label_ar: '',
    label_en: '',
    description: '',
    permissions: [],
});

const activeModules = ref([]);
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
                success(t('messages.saved_success', 'تم الحفظ بنجاح'));
                emit('close');
                emit('saved');
            },
            onError: () => error(t('common.error_occurred', 'حدث خطأ ما')),
            preserveScroll: true,
        });
    } else {
        form.post(route('settings.roles.store'), {
            onSuccess: () => {
                success(t('messages.saved_success', 'تم الحفظ بنجاح'));
                emit('close');
                emit('saved');
                form.reset();
            },
            onError: () => error(t('common.error_occurred', 'حدث خطأ ما')),
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
                    class="bg-surface-muted px-4 py-3 border-b border-border flex items-center justify-between"
                >
                    <span class="font-bold text-sm text-text-primary">
                        {{ t('roles.permissions', 'الصلاحيات') }}
                    </span>
                    <span class="text-xs text-text-muted">
                        {{ form.permissions.length }}
                        {{ t('roles.selected_permissions', 'صلاحية محددة') }}
                    </span>
                </div>

                <div
                    class="max-h-[50vh] overflow-y-auto divide-y divide-border bg-surface custom-scrollbar"
                >
                    <div v-for="(perms, moduleName) in groupedPermissions" :key="moduleName">
                        <!-- Module Header (Accordion Toggle) -->
                        <div
                            class="flex items-center justify-between px-4 py-3 bg-surface-muted/40 hover:bg-surface-muted transition-colors cursor-pointer select-none"
                            @click="toggleModule(moduleName)"
                        >
                            <div class="flex items-center gap-2.5">
                                <svg
                                    class="w-4 h-4 text-text-muted transition-transform duration-200"
                                    :class="{ 'rotate-180': activeModules.includes(moduleName) }"
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
                            v-show="activeModules.includes(moduleName)"
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
                                        {{
                                            permissionDescriptions &&
                                            permissionDescriptions[permission]
                                                ? permissionDescriptions[permission]
                                                : t(
                                                      'permissions.' +
                                                          permission.replace(/\./g, '_'),
                                                      permission
                                                  )
                                        }}
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
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-text-secondary bg-surface border border-border rounded-xl hover:bg-surface-muted transition-colors cursor-pointer"
                    @click="emit('close')"
                >
                    {{ t('common.cancel', 'إلغاء') }}
                </button>
                <button
                    type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-action-on bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all duration-200 disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer"
                    :disabled="form.processing"
                >
                    {{
                        form.processing
                            ? t('common.saving', 'جاري الحفظ...')
                            : isEditing
                              ? t('common.update', 'تحديث')
                              : t('common.save', 'حفظ')
                    }}
                </button>
            </div>
        </form>
    </BaseModal>
</template>
