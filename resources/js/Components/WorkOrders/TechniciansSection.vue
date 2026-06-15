<template>
    <div class="space-y-6">
        <!-- Toolbar / Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2.5">
                    <span class="text-2xl">👷</span>
                    {{ labels.title }}
                </h3>

                <!-- Add Technician Dropdown -->
                <div class="relative" v-if="!readOnly && unassignedTechnicians.length > 0">
                    <!-- Invisible overlay to close dropdown -->
                    <div v-if="showAddTechDropdown" @click="showAddTechDropdown = false" class="fixed inset-0 z-20"></div>

                    <button 
                        @click="showAddTechDropdown = !showAddTechDropdown" 
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none z-30 relative"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        {{ labels.addTechnician }}
                    </button>

                    <!-- Dropdown Menu -->
                    <div 
                        v-if="showAddTechDropdown" 
                        class="absolute start-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 rounded-xl shadow-xl z-30 overflow-hidden divide-y divide-gray-50 dark:divide-gray-750"
                    >
                        <div class="py-1 max-h-60 overflow-y-auto">
                            <button 
                                v-for="tech in unassignedTechnicians" 
                                :key="tech.id"
                                @click="addTechnicianCard(tech)"
                                class="w-full px-4 py-3 text-start flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <!-- Photo / Avatar -->
                                <img 
                                    v-if="tech.photo_url" 
                                    :src="tech.photo_url" 
                                    class="w-8 h-8 rounded-full object-cover border border-gray-100 dark:border-gray-850 flex-shrink-0" 
                                    alt=""
                                />
                                <div 
                                    v-else 
                                    class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs flex-shrink-0 border border-indigo-100 dark:border-indigo-900"
                                >
                                    {{ getInitials(tech.name) }}
                                </div>

                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">
                                        {{ getTechName(tech) }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        {{ getTechJobTitle(tech) || '-' }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 self-end sm:self-auto">
                <!-- Grid / Table View Switcher -->
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button 
                        @click="setViewMode('grid')" 
                        :class="[
                            'p-2 rounded-lg transition-all',
                            viewMode === 'grid'
                                ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400'
                                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button 
                        @click="setViewMode('table')" 
                        :class="[
                            'p-2 rounded-lg transition-all',
                            viewMode === 'table'
                                ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400'
                                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid Card View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div 
                v-for="tech in assignedTechnicians" 
                :key="tech.id" 
                class="bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700/80 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600 transition-all duration-200 flex flex-col justify-between"
            >
                <!-- Top Section: Photo, Name, Job Title & Print Button -->
                <div>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Photo/Avatar -->
                            <img 
                                v-if="tech.photo_url" 
                                :src="tech.photo_url" 
                                class="w-12 h-12 rounded-full object-cover border-2 border-indigo-50 dark:border-indigo-950 flex-shrink-0"
                                alt=""
                            />
                            <div 
                                v-else 
                                class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg border-2 border-indigo-50 dark:border-indigo-950 flex-shrink-0"
                            >
                                {{ getInitials(tech.name) }}
                            </div>
                            
                            <!-- Name and Title -->
                            <div class="min-w-0 flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-gray-100 text-base truncate">
                                    {{ getTechName(tech) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate mt-0.5">
                                    {{ getTechJobTitle(tech) || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Delete/Remove Technician Button (if no services assigned) -->
                        <button 
                            v-if="tech.items.length === 0 && !readOnly"
                            @click="removeTechnicianCard(tech.id)"
                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-all"
                            title="إزالة الفني"
                        >
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        
                        <!-- Print Button (if services are assigned) -->
                        <button 
                            v-else-if="tech.items.length > 0"
                            @click="printTechnicianCard(tech.id)"
                            class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all"
                            :title="labels.printWorkCard"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 022 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Divider -->
                    <div class="my-4 border-t border-gray-100 dark:border-gray-700/60"></div>

                    <!-- Services Header with Add button -->
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🛠️</span>
                            {{ labels.services }}
                        </h4>

                        <!-- Plus Button to add services -->
                        <div class="relative" v-if="!readOnly && getUnassociatedItems(tech).length > 0">
                            <!-- Invisible overlay to close dropdown -->
                            <div v-if="activeServiceTechId === tech.id" @click="activeServiceTechId = null" class="fixed inset-0 z-10"></div>

                            <button 
                                @click.stop="toggleAddServiceDropdown(tech.id)"
                                class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/55 text-indigo-600 dark:text-indigo-400 flex items-center justify-center transition-colors shadow-sm relative z-20"
                                :title="labels.selectService"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>

                            <!-- Add Service Dropdown -->
                            <div 
                                v-if="activeServiceTechId === tech.id"
                                class="absolute right-0 mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 rounded-xl shadow-xl z-20 overflow-hidden divide-y divide-gray-50 dark:divide-gray-750"
                            >
                                <div class="py-1 max-h-52 overflow-y-auto">
                                    <button 
                                        v-for="item in getUnassociatedItems(tech)" 
                                        :key="item.id"
                                        @click="assignServiceToTech(tech.id, item.id)"
                                        class="w-full px-4 py-2.5 text-start text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-2"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        <span class="truncate">{{ getItemName(item) }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services List -->
                <div class="flex-grow">
                    <div v-if="tech.items.length > 0" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <div 
                            v-for="item in tech.items" 
                            :key="item.id"
                            class="group flex items-center justify-between py-2 px-3 bg-gray-50/70 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800/80 text-xs text-gray-700 dark:text-gray-300 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/20 hover:border-indigo-100 dark:hover:border-indigo-900/40 transition-colors"
                        >
                            <button 
                                @click="$emit('click-service', item.id)"
                                class="font-semibold text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate pr-2 text-start transition-colors"
                            >
                                {{ getItemName(item) }}
                                <span v-if="item.pivot_share !== undefined && parseFloat(item.pivot_share) < 100" class="ms-1 text-gray-400 group-hover:text-indigo-400 font-mono">
                                    ({{ parseFloat(parseFloat(item.pivot_share).toFixed(2)) }}%)
                                </span>
                            </button>

                            <!-- Delete Button (قمامة) on the far left -->
                            <button 
                                v-if="!readOnly"
                                @click="removeServiceFromTech(tech.id, item.id)"
                                class="text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 p-1 rounded-lg transition-all flex-shrink-0"
                                title="حذف الخدمة"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div 
                        v-else 
                        class="flex flex-col items-center justify-center py-6 text-center text-xs text-gray-400 dark:text-gray-500 italic bg-gray-50/50 dark:bg-gray-900/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-700/60"
                    >
                        {{ labels.noServices }}
                    </div>
                </div>
            </div>

            <!-- Empty Grid State -->
            <div 
                v-if="assignedTechnicians.length === 0" 
                class="col-span-full py-16 flex flex-col items-center justify-center text-center text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700 rounded-2xl p-6 shadow-sm"
            >
                <span class="text-4xl mb-3">👷</span>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ labels.noTechnicians }}</p>
            </div>
        </div>

        <!-- Table View -->
        <div v-else class="bg-white dark:bg-gray-800 border border-gray-150 dark:border-gray-700/80 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3.5 text-start text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                {{ labels.name }}
                            </th>
                            <th class="px-6 py-3.5 text-start text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                {{ labels.jobTitle }}
                            </th>
                            <th class="px-6 py-3.5 text-start text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                {{ labels.services }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <tr v-for="tech in assignedTechnicians" :key="tech.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                            <!-- Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <!-- Photo/Avatar -->
                                    <img 
                                        v-if="tech.photo_url" 
                                        :src="tech.photo_url" 
                                        class="w-8 h-8 rounded-full object-cover border border-gray-100 dark:border-gray-800" 
                                        alt=""
                                    />
                                    <div 
                                        v-else 
                                        class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs border border-indigo-100 dark:border-indigo-900"
                                    >
                                        {{ getInitials(tech.name) }}
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                        {{ getTechName(tech) }}
                                    </span>
                                </div>
                            </td>

                            <!-- Job Title -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-medium">
                                {{ getTechJobTitle(tech) || '-' }}
                            </td>

                            <!-- Services -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5 max-w-xl">
                                    <button 
                                        v-for="item in tech.items" 
                                        :key="item.id"
                                        @click="$emit('click-service', item.id)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors border border-gray-150/20 dark:border-gray-650"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        {{ getItemName(item) }}
                                        <span v-if="item.pivot_share !== undefined && parseFloat(item.pivot_share) < 100" class="ms-1 text-gray-450 dark:text-gray-400 font-mono">
                                            ({{ parseFloat(parseFloat(item.pivot_share).toFixed(2)) }}%)
                                        </span>
                                    </button>
                                    <span v-if="tech.items.length === 0" class="text-xs text-gray-450 italic">
                                        {{ labels.noServices }}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty Table State -->
                        <tr v-if="assignedTechnicians.length === 0">
                            <td colspan="3" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-4xl">👷</span>
                                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ labels.noTechnicians }}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: { type: Object, default: () => ({}) },
    technicians: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['click-service']);

const { t, locale } = useI18n();
const { success: toastSuccess, error: toastError } = useToast();
const { confirm } = useConfirm();

// Local view mode (persisted in localStorage)
const viewMode = ref(localStorage.getItem('work_orders_technicians_view_mode') || 'grid');
const setViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('work_orders_technicians_view_mode', mode);
};

// Locally added technicians list (to allow rendering cards with 0 services)
const locallyAddedTechs = ref([]);
const showAddTechDropdown = ref(false);
const activeServiceTechId = ref(null);

// Watch itemsByDepartment to clean up locallyAddedTechs once they get assigned services in the DB
watch(() => props.itemsByDepartment, (newVal) => {
    const dbTechIds = new Set();
    Object.values(newVal || {}).flat().forEach(item => {
        if (item.technicians) {
            item.technicians.forEach(tech => dbTechIds.add(tech.id));
        }
    });
    locallyAddedTechs.value = locallyAddedTechs.value.filter(t => !dbTechIds.has(t.id));
}, { deep: true, immediate: true });

// Localized Labels helper
const labels = computed(() => {
    return locale.value === 'ar' ? {
        title: 'الفنيون',
        addTechnician: 'إضافة فني',
        printWorkCard: 'طباعة كرت العمل للفني',
        services: 'الخدمات',
        noServices: 'لا يوجد خدمات مرتبطة بهذا الفني',
        noTechnicians: 'لا يوجد فنيين معينين حالياً',
        selectService: 'اختر الخدمة لإسنادها',
        selectTechnician: 'اختر فني لإضافته',
        confirmRemoveService: 'هل أنت متأكد من إزالة هذه الخدمة من الفني؟',
        jobTitle: 'مسمى الوظيفة',
        name: 'الاسم',
    } : {
        title: 'Technicians',
        addTechnician: 'Add Technician',
        printWorkCard: 'Print Technician Work Card',
        services: 'Services',
        noServices: 'No services assigned to this technician',
        noTechnicians: 'No technicians assigned yet',
        selectService: 'Select service to assign',
        selectTechnician: 'Select technician to add',
        confirmRemoveService: 'Are you sure you want to remove this service from the technician?',
        jobTitle: 'Job Title',
        name: 'Name',
    };
});

// Computed: Aggregate technicians from all items and combine with local list
const assignedTechnicians = computed(() => {
    const techs = new Map();

    // 1. Iterate through all items (flattened from departments)
    const allItems = Object.values(props.itemsByDepartment || {}).flat();

    allItems.forEach(item => {
        if (item.technicians && item.technicians.length > 0) {
            item.technicians.forEach(tech => {
                if (!techs.has(tech.id)) {
                    techs.set(tech.id, {
                        id: tech.id,
                        name: tech.name,
                        employee: tech.employee,
                        photo_url: tech.photo_url,
                        items: []
                    });
                }
                techs.get(tech.id).items.push({
                    ...item,
                    pivot_share: tech.pivot?.share
                });
            });
        }
    });

    // 2. Add locally added technicians who are not already in the map
    locallyAddedTechs.value.forEach(localTech => {
        if (!techs.has(localTech.id)) {
            techs.set(localTech.id, {
                id: localTech.id,
                name: localTech.name,
                employee: localTech.employee || { 
                    name_ar: localTech.name_ar, 
                    name_en: localTech.name_en,
                    display_name: localTech.name,
                    jobTitle: (localTech.job_title_ar || localTech.job_title_en) ? { 
                        name_ar: localTech.job_title_ar, 
                        name_en: localTech.job_title_en 
                    } : null
                },
                photo_url: localTech.photo_url,
                items: []
            });
        }
    });

    return Array.from(techs.values());
});

// Computed: Technicians not associated with any service/card
const unassignedTechnicians = computed(() => {
    const assignedIds = new Set(assignedTechnicians.value.map(t => t.id));
    return (props.technicians || []).filter(tech => !assignedIds.has(tech.id));
});

// Helper: Get items in this work order that a technician is NOT associated with
const getUnassociatedItems = (tech) => {
    const allItems = Object.values(props.itemsByDepartment || {}).flat();
    const assignedItemIds = new Set(tech.items.map(item => item.id));
    return allItems.filter(item => {
        const isNotAssigned = !assignedItemIds.has(item.id);
        const hasValidStatus = item.status === 'pending' || item.status === 'in_progress';
        return isNotAssigned && hasValidStatus;
    });
};

// Helper: Localized service item name
const getItemName = (item) => {
    if (!item) return '';
    if (item.service) {
        const name = locale.value === 'ar' ? (item.service.name_ar || item.service.name_en) : (item.service.name_en || item.service.name_ar);
        const nameTrimmed = (name || '').trim();
        if (nameTrimmed === 'أخرى' || nameTrimmed === 'اخرى' || nameTrimmed.toLowerCase() === 'other') {
            return item.description || item.title || name;
        }
        return name;
    }
    return item.description || item.title || t('common.na');
};

// Helper: Localized technician name
const getTechName = (tech) => {
    if (!tech) return '';
    if (tech.employee) {
        return locale.value === 'ar' 
            ? (tech.employee.name_ar || tech.employee.name_en || tech.name) 
            : (tech.employee.name_en || tech.employee.name_ar || tech.name);
    }
    if (locale.value === 'ar' && tech.name_ar) return tech.name_ar;
    if (tech.name_en) return tech.name_en;
    if (tech.name_ar) return tech.name_ar;
    return tech.name;
};

// Helper: Localized technician job title
const getTechJobTitle = (tech) => {
    if (!tech) return '';
    const emp = tech.employee;
    if (emp) {
        const title = emp.job_title || emp.jobTitle;
        if (title) {
            return locale.value === 'ar' 
                ? (title.name_ar || title.name_en) 
                : (title.name_en || title.name_ar);
        }
    }
    if (locale.value === 'ar' && tech.job_title_ar) return tech.job_title_ar;
    if (tech.job_title_en) return tech.job_title_en;
    if (tech.job_title_ar) return tech.job_title_ar;
    return '';
};

// Helper: Get initials (first letter of first + last name)
const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
};

// Action: Local addition of technician card
const addTechnicianCard = (tech) => {
    if (!locallyAddedTechs.value.some(t => t.id === tech.id)) {
        locallyAddedTechs.value.push(tech);
    }
    showAddTechDropdown.value = false;
};

// Action: Assign service to technician
const assignServiceToTech = (techId, itemId) => {
    axios.post(route('work-orders.items.technicians.store', { work_order: props.workOrder.id, item: itemId }), {
        user_id: techId
    })
    .then(response => {
        toastSuccess(t('messages.technician_assigned'));
        activeServiceTechId.value = null;
        
        // Remove from locally added list if it is now persistent
        locallyAddedTechs.value = locallyAddedTechs.value.filter(t => t.id !== techId);
        
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    })
    .catch(error => {
        console.error(error);
        const errMsg = error.response?.data?.error || t('messages.technician_not_belong_to_center');
        toastError(errMsg);
    });
};

// Action: Remove service from technician
const removeServiceFromTech = async (techId, itemId) => {
    const confirmed = await confirm({
        title: locale.value === 'ar' ? 'تأكيد الحذف' : 'Confirm Delete',
        message: labels.value.confirmRemoveService,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        variant: 'danger'
    });

    if (!confirmed) return;

    axios.delete(route('work-orders.items.technicians.destroy', { 
        work_order: props.workOrder.id, 
        item: itemId, 
        user: techId 
    }))
    .then(response => {
        toastSuccess(t('messages.technician_removed'));
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    })
    .catch(error => {
        console.error(error);
        toastError(t('common.error'));
    });
};

// Action: Toggle service assignment menu
const toggleAddServiceDropdown = (techId) => {
    if (activeServiceTechId.value === techId) {
        activeServiceTechId.value = null;
    } else {
        activeServiceTechId.value = techId;
    }
};

// Action: Remove technician card
const removeTechnicianCard = async (techId) => {
    const confirmed = await confirm({
        title: locale.value === 'ar' ? 'تأكيد الإزالة' : 'Confirm Removal',
        message: locale.value === 'ar' ? 'هل أنت متأكد من إزالة هذا الفني؟' : 'Are you sure you want to remove this technician?',
        confirmText: locale.value === 'ar' ? 'إزالة' : 'Remove',
        cancelText: t('common.cancel'),
        variant: 'danger'
    });

    if (confirmed) {
        locallyAddedTechs.value = locallyAddedTechs.value.filter(t => t.id !== techId);
    }
};

// Action: Print card for this technician only
const printTechnicianCard = (techId) => {
    const url = route('work-orders.print.services', { 
        workOrder: props.workOrder.id, 
        technician_id: techId 
    });
    window.open(url, '_blank');
};
</script>
