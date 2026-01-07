<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المستخدمين الإداريين</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة مسؤولي النظام وصلاحياتهم</p>
                </div>
                <div class="flex gap-3">
                    <a href="/system/activity-log" class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        سجل النشاط
                    </a>
                    <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                        + إضافة مستخدم
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4">
                <input 
                    type="text" 
                    v-model="search" 
                    placeholder="بحث بالاسم أو البريد..."
                    @keyup.enter="applyFilters"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-64"
                />
                <select v-model="role" @change="applyFilters" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">كل الأدوار</option>
                    <option value="super_admin">مدير النظام</option>
                    <option value="admin">مسؤول</option>
                    <option value="support">دعم فني</option>
                </select>
            </div>
            
            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدم</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الدور</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">آخر دخول</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="admin in admins.data" :key="admin.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-bold">
                                        {{ admin.name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ admin.name }}</p>
                                        <p class="text-xs text-gray-500">{{ admin.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="getRoleClass(admin.role)" class="px-2 py-1 rounded-full text-xs">
                                    {{ getRoleLabel(admin.role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="admin.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded-full text-xs">
                                    {{ admin.is_active ? 'نشط' : 'معطّل' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-sm">
                                {{ admin.last_login_at ? formatDate(admin.last_login_at) : 'لم يسجل دخوله' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button @click="openEditModal(admin)" class="text-indigo-600 hover:text-indigo-800 text-sm">تعديل</button>
                                    <button @click="deleteAdmin(admin)" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!admins.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد مستخدمين</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-2xl shadow-xl max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ editingAdmin ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم *</label>
                                    <input type="text" v-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">البريد الإلكتروني *</label>
                                    <input type="email" v-model="form.email" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" dir="ltr" />
                                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم الجوال</label>
                                    <input type="text" v-model="form.phone" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" dir="ltr" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        كلمة المرور {{ editingAdmin ? '(اتركها فارغة للإبقاء)' : '*' }}
                                    </label>
                                    <input type="password" v-model="form.password" :required="!editingAdmin" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" dir="ltr" />
                                </div>
                            </div>
                            
                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الدور *</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label v-for="(label, key) in roles" :key="key" :class="['border rounded-lg p-3 cursor-pointer transition', form.role === key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700']">
                                        <input type="radio" v-model="form.role" :value="key" class="hidden" />
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ label }}</p>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Permissions (if not super_admin) -->
                            <div v-if="form.role !== 'super_admin'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الصلاحيات</label>
                                <div class="space-y-4">
                                    <div v-for="(perms, group) in permissions" :key="group" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <p class="font-medium text-gray-900 dark:text-white mb-2">{{ groupLabels[group] }}</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label v-for="(label, key) in perms" :key="key" class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" :value="key" v-model="form.permissions" class="rounded border-gray-300 text-indigo-600" />
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">حساب نشط</span>
                                </label>
                            </div>
                            
                            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                    {{ form.processing ? 'جاري الحفظ...' : (editingAdmin ? 'حفظ التغييرات' : 'إنشاء المستخدم') }}
                                </button>
                                <button type="button" @click="closeModal" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    admins: Object,
    filters: Object,
    permissions: Object,
});

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const showModal = ref(false);
const editingAdmin = ref(null);

const roles = { super_admin: 'مدير النظام', admin: 'مسؤول', support: 'دعم فني' };
const groupLabels = { tenants: 'المستأجرين', billing: 'الفوترة', settings: 'الإعدادات', credits: 'الأرصدة' };

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'admin',
    permissions: [],
    is_active: true,
});

const openCreateModal = () => {
    editingAdmin.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (admin) => {
    editingAdmin.value = admin;
    form.name = admin.name;
    form.email = admin.email;
    form.phone = admin.phone || '';
    form.password = '';
    form.role = admin.role;
    form.permissions = admin.permissions || [];
    form.is_active = admin.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingAdmin.value = null;
};

const submitForm = () => {
    if (editingAdmin.value) {
        form.put(`/system/admin-users/${editingAdmin.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/admin-users', {
            onSuccess: () => closeModal(),
        });
    }
};

const applyFilters = () => {
    router.get('/system/admin-users', {
        search: search.value || undefined,
        role: role.value || undefined,
    }, { preserveState: true });
};

const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';

const getRoleLabel = (role) => ({ super_admin: 'مدير النظام', admin: 'مسؤول', support: 'دعم فني' }[role] || role);
const getRoleClass = (role) => ({
    super_admin: 'bg-purple-100 text-purple-700',
    admin: 'bg-blue-100 text-blue-700',
    support: 'bg-gray-100 text-gray-700',
}[role] || 'bg-gray-100 text-gray-700');

const deleteAdmin = (admin) => {
    if (confirm(`هل تريد حذف "${admin.name}"؟`)) {
        router.delete(`/system/admin-users/${admin.id}`);
    }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
