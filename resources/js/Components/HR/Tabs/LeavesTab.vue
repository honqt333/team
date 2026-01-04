<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-xl">🏖️</span>
                {{ $t('hr.leaves.title') }}
            </h3>
            <button
                @click="openLeaveModal()"
                class="px-4 py-2 bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 rounded-lg hover:bg-violet-200 dark:hover:bg-violet-900/50 text-sm font-medium"
            >
                + {{ $t('hr.leaves.add') }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.type') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.duration') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.dates') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.status') }}</th>
                        <th class="px-6 py-3 font-medium">{{ $t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $t(`hr.leaves.types.${leave.type}`) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                            {{ leave.days }} {{ $t('common.days') }}
                        </td>
                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400 dir-ltr text-right">
                            {{ formatDate(leave.start_date) }} → {{ formatDate(leave.end_date) }}
                        </td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                leave.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                leave.status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                            ]">
                                {{ $t(`hr.leaves.statuses.${leave.status}`) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <template v-if="leave.status === 'pending'">
                                    <!-- Approve Button -->
                                    <button 
                                        @click="updateLeaveStatus(leave, 'approved')" 
                                        class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-xs font-medium hover:bg-emerald-200"
                                        :title="$t('hr.leaves.approve')"
                                    >
                                        ✓ {{ $t('hr.leaves.approve') }}
                                    </button>
                                    <!-- Reject Button -->
                                    <button 
                                        @click="updateLeaveStatus(leave, 'rejected')" 
                                        class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded text-xs font-medium hover:bg-red-200"
                                        :title="$t('hr.leaves.reject')"
                                    >
                                        ✗ {{ $t('hr.leaves.reject') }}
                                    </button>
                                    <!-- Edit Button -->
                                    <button @click="openLeaveModal(leave)" class="text-blue-600 hover:text-blue-800" :title="$t('common.edit')">
                                        ✏️
                                    </button>
                                    <!-- Delete Button -->
                                    <button @click="deleteLeave(leave)" class="text-red-600 hover:text-red-800" :title="$t('common.delete')">
                                        🗑️
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="leaves.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            {{ $t('common.no_data') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Leave Modal -->
        <Modal :show="showLeaveModal" @close="closeLeaveModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ editingLeave ? $t('hr.leaves.edit') : $t('hr.leaves.add') }}
                </h3>

                <form @submit.prevent="submitLeave" class="space-y-4">
                    <!-- Leave Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.leaves.type') }}
                        </label>
                        <select
                            v-model="leaveForm.type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                        >
                            <option value="annual">{{ $t('hr.leaves.types.annual') }}</option>
                            <option value="sick">{{ $t('hr.leaves.types.sick') }}</option>
                            <option value="unpaid">{{ $t('hr.leaves.types.unpaid') }}</option>
                            <option value="emergency">{{ $t('hr.leaves.types.emergency') }}</option>
                            <option value="other">{{ $t('hr.leaves.types.other') }}</option>
                        </select>
                        <p v-if="leaveForm.errors.type" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.type }}</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('common.from') }}
                            </label>
                            <input
                                type="date"
                                v-model="leaveForm.start_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                            />
                            <p v-if="leaveForm.errors.start_date" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('common.to') }}
                            </label>
                            <input
                                type="date"
                                v-model="leaveForm.end_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                            />
                            <p v-if="leaveForm.errors.end_date" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.leaves.reason') }}
                        </label>
                        <textarea
                            v-model="leaveForm.reason"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                        ></textarea>
                         <p v-if="leaveForm.errors.reason" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="closeLeaveModal"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="leaveForm.processing"
                            class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50"
                        >
                            {{ $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    employee: Object,
    leaves: {
        type: Array,
        default: () => []
    }
});

const { t } = useI18n();
const { success, error } = useToast();

// Format date from ISO to readable format
function formatDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-CA'); // Returns YYYY-MM-DD format
    } catch {
        return dateString;
    }
}

// Leave management
const showLeaveModal = ref(false);
const editingLeave = ref(null);

const leaveForm = useForm({
    type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
    employee_id: props.employee.id
});

function openLeaveModal(leave = null) {
    editingLeave.value = leave;
    if (leave) {
        leaveForm.type = leave.type;
        // Convert ISO dates to YYYY-MM-DD for input[type="date"]
        leaveForm.start_date = formatDate(leave.start_date);
        leaveForm.end_date = formatDate(leave.end_date);
        leaveForm.reason = leave.reason;
    } else {
        leaveForm.reset();
        leaveForm.type = 'annual';
        leaveForm.employee_id = props.employee.id;
    }
    showLeaveModal.value = true;
}

function closeLeaveModal() {
    showLeaveModal.value = false;
    leaveForm.reset();
    editingLeave.value = null;
}

function submitLeave() {
    const url = editingLeave.value 
        ? route('app.hr.leaves.update', editingLeave.value.id)
        : route('app.hr.leaves.store');
    
    const method = editingLeave.value ? 'put' : 'post';

    leaveForm[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            closeLeaveModal();
            success(t('common.saved_success'));
        },
        onError: () => error(t('common.error_occurred'))
    });
}

function deleteLeave(leave) {
    if (!confirm(t('common.confirm_delete'))) return;
    
    router.delete(route('app.hr.leaves.destroy', leave.id), {
        preserveScroll: true,
        onSuccess: () => success(t('common.deleted_success')),
        onError: () => error(t('common.error_occurred'))
    });
}

function updateLeaveStatus(leave, status) {
    router.put(route('app.hr.leaves.update-status', leave.id), {
        status: status
    }, {
        preserveScroll: true,
        onSuccess: () => success(t('common.saved_success')),
        onError: (errors) => {
            console.error('Leave status update error:', errors);
            error(t('common.error_occurred'));
        }
    });
}
</script>
