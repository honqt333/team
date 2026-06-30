import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

export const PACKAGES_DEPT_KEY = 'packages';
export const UNASSIGNED_DEPT_KEY = '0';

export function useWorkOrderItems({ workOrder, itemsByDepartment, departments, services }) {
    const { t } = useI18n();
    const { success } = useToast();
    const { confirm } = useConfirm();

    const getWorkOrder = () => typeof workOrder === 'function' ? workOrder() : workOrder;
    const getItemsByDepartment = () => typeof itemsByDepartment === 'function' ? itemsByDepartment() : itemsByDepartment;

    // ─── Local state ─────────────────────────────────────────────────────────
    const showDeptMenu = ref(false);
    const expandedDepartments = ref([]);

    // ─── Computed ─────────────────────────────────────────────────────────────
    /**
     * Departments that should be displayed in the accordion.
     * Shows departments that have items or are linked to the work order,
     * plus the virtual "packages" bucket if applicable.
     */
    const displayDepartments = computed(() => {
        const deptIds = new Set();

        // Add departments that have items (active or cancelled)
        Object.entries(getItemsByDepartment()).forEach(([id, items]) => {
            if (id !== UNASSIGNED_DEPT_KEY && id !== PACKAGES_DEPT_KEY) {
                if (items.length > 0) {
                    deptIds.add(parseInt(id));
                }
            }
        });

        // Add work order's linked departments
        getWorkOrder().departments?.forEach(dept => deptIds.add(dept.id));

        // Get database departments matching active list
        const list = departments.filter(d => deptIds.has(d.id));

        // Virtual packages section — show if it has package items or the flag is active
        const packageItems = getItemsByDepartment()[PACKAGES_DEPT_KEY];
        const hasPackageItems = packageItems && packageItems.length > 0;
        const showPackagesSection = getWorkOrder().show_packages_section;

        if (hasPackageItems || showPackagesSection) {
            list.push({
                id: PACKAGES_DEPT_KEY,
                name_ar: 'باقات الخدمات',
                name_en: 'Service Packages',
                is_virtual: true,
            });
        }

        return list;
    });

    /**
     * Departments that can still be added to the work order.
     */
    const availableDepartments = computed(() => {
        const usedIds = displayDepartments.value.map(d => d.id);
        const list = departments.filter(d => !usedIds.includes(d.id));

        // Append virtual packages department if packages exist and it's not already used
        const hasAvailablePackages = services?.some(s => s.type === 'package');
        if (hasAvailablePackages && !usedIds.includes(PACKAGES_DEPT_KEY)) {
            list.push({
                id: PACKAGES_DEPT_KEY,
                name_ar: 'باقات الخدمات',
                name_en: 'Service Packages',
                name: 'Service Packages',
            });
        }

        return list;
    });

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function getDepartmentItems(deptId) {
        return getItemsByDepartment()[deptId] || [];
    }

    function toggleDepartment(deptId) {
        const idx = expandedDepartments.value.indexOf(deptId);
        if (idx > -1) {
            expandedDepartments.value.splice(idx, 1);
        } else {
            expandedDepartments.value.push(deptId);
        }
    }

    // ─── Actions ──────────────────────────────────────────────────────────────
    function addDepartment(deptId) {
        showDeptMenu.value = false;
        router.post(route('work-orders.departments.store', getWorkOrder().id), {
            department_id: deptId,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
                if (!expandedDepartments.value.includes(deptId)) {
                    expandedDepartments.value.push(deptId);
                }
            },
        });
    }

    async function removeDepartment(deptId) {
        const confirmed = await confirm({
            title: t('common.confirm_delete_title'),
            message: t('common.confirm_delete_message'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            type: 'danger',
        });

        if (confirmed) {
            router.delete(
                route('work-orders.departments.destroy', { work_order: getWorkOrder().id, department_id: deptId }),
                { onSuccess: () => success(t('common.deleted_success')) }
            );
        }
    }

    return {
        // state
        showDeptMenu,
        expandedDepartments,
        // computed
        displayDepartments,
        availableDepartments,
        // helpers
        getDepartmentItems,
        toggleDepartment,
        // actions
        addDepartment,
        removeDepartment,
    };
}
