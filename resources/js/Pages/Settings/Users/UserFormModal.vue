<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    user: Object, // If provided, edit mode
    centers: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null,
    centers: [],
    is_active: true,
});

const isEditing = computed(() => !!props.user);

const roleOptions = computed(() => {
    return props.roles.map((role) => ({
        value: role.id,
        label: locale.value === 'ar' ? role.label_ar || role.name : role.label_en || role.name,
    }));
});

watch(
    [() => props.show, () => props.user],
    ([newShow, newUser]) => {
        if (newShow) {
            form.clearErrors();
            if (newUser) {
                form.name = newUser.name || '';
                form.email = newUser.email || '';
                form.password = '';
                form.password_confirmation = '';
                form.centers = Array.isArray(newUser.centers)
                    ? newUser.centers.map((c) => c.id)
                    : [];
                form.role_id =
                    newUser.roles && newUser.roles.length > 0 ? newUser.roles[0].id : null;
                form.is_active = Boolean(newUser.is_active);
            } else {
                form.reset();
                form.clearErrors();
                form.name = '';
                form.email = '';
                form.password = '';
                form.password_confirmation = '';
                form.centers = props.centers?.length === 1 ? [props.centers[0].id] : [];
                form.role_id = null;
                form.is_active = true;
            }
        }
    },
    { immediate: true }
);

const submit = () => {
    if (isEditing.value) {
        form.put(route('settings.users.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('settings.users.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <BaseModal
        :show="show"
        :title="
            isEditing
                ? t('users.edit_user', 'تعديل بيانات المستخدم')
                : t('users.create_user', 'إضافة مستخدم جديد')
        "
        size="2xl"
        @close="emit('close')"
    >
        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column: User details & credentials -->
                <div class="space-y-4">
                    <!-- Name -->
                    <FormField
                        v-slot="field"
                        :label="t('users.name', 'الاسم')"
                        :error="form.errors.name"
                        required
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            v-model="form.name"
                            type="text"
                            class="w-full"
                            required
                            autofocus
                        />
                    </FormField>

                    <!-- Email -->
                    <FormField
                        v-slot="field"
                        :label="t('users.email', 'البريد الإلكتروني')"
                        :error="form.errors.email"
                        required
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            v-model="form.email"
                            type="email"
                            class="w-full"
                            required
                        />
                    </FormField>

                    <!-- Password Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField
                            v-slot="field"
                            :label="
                                isEditing
                                    ? t('users.new_password', 'كلمة المرور الجديدة (اختياري)')
                                    : t('users.password', 'كلمة المرور')
                            "
                            :error="form.errors.password"
                            :required="!isEditing"
                        >
                            <AppInput
                                :id="field.id"
                                :aria-describedby="field.describedBy"
                                :invalid="field.hasError"
                                v-model="form.password"
                                type="password"
                                class="w-full"
                                :required="!isEditing"
                            />
                        </FormField>

                        <FormField
                            v-slot="field"
                            :label="t('users.confirm_password', 'تأكيد كلمة المرور')"
                            :error="form.errors.password_confirmation"
                            :required="!isEditing || !!form.password"
                        >
                            <AppInput
                                :id="field.id"
                                :aria-describedby="field.describedBy"
                                :invalid="field.hasError"
                                v-model="form.password_confirmation"
                                type="password"
                                class="w-full"
                                :required="!isEditing || !!form.password"
                            />
                        </FormField>
                    </div>

                    <!-- Active Status Toggle -->
                    <div class="p-3.5 rounded-xl border border-border bg-surface-muted/50">
                        <AppToggle
                            v-model="form.is_active"
                            :label="t('users.is_active', 'حساب نشط')"
                            :description="
                                t('users.is_active_hint', 'للسماح للمستخدم بتسجيل الدخول للنظام')
                            "
                            color="success"
                        />
                    </div>
                </div>

                <!-- Right Column: Roles & Allowed Branches -->
                <div
                    class="space-y-4 lg:border-s lg:border-border lg:ps-6 flex flex-col justify-between"
                >
                    <!-- Role -->
                    <FormField
                        :label="t('users.role', 'الدور')"
                        :error="form.errors.role_id"
                        required
                    >
                        <SearchableSelect
                            v-model="form.role_id"
                            :options="roleOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="t('users.select_role', 'اختر الدور')"
                            required
                            class="w-full"
                        />
                    </FormField>

                    <!-- Allowed Branches -->
                    <div class="flex-1 flex flex-col">
                        <FormField
                            :label="t('users.allowed_centers', 'الفروع المتاحة')"
                            :error="form.errors.centers"
                            required
                        >
                            <div
                                class="mt-1 grid grid-cols-1 gap-2.5 max-h-56 overflow-y-auto p-1 custom-scrollbar border border-border rounded-xl"
                            >
                                <SelectableCard
                                    v-for="center in centers"
                                    :key="center.id"
                                    v-model="form.centers"
                                    :value="center.id"
                                    :title="center.name"
                                    type="checkbox"
                                    class="p-3"
                                />
                            </div>
                        </FormField>
                    </div>
                </div>
            </div>

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
