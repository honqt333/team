<script setup>
const props = defineProps({
    status: { type: String, required: true },
});

const statusMap = {
    // work order statuses
    open: {
        label: 'Open',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    in_progress: {
        label: 'In Progress',
        class: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    },
    on_hold: {
        label: 'On Hold',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    ready_for_qc: {
        label: 'Ready for QC',
        class: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    },
    done: {
        label: 'Done',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    completed: {
        label: 'Completed',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    cancelled: {
        label: 'Cancelled',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
    pending: {
        label: 'Pending',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    // invoice payment statuses
    paid: {
        label: 'Paid',
        class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
    unpaid: {
        label: 'Unpaid',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
    partial: {
        label: 'Partial',
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    overdue: {
        label: 'Overdue',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    },
};

const meta =
    props.status in statusMap
        ? statusMap[props.status]
        : {
              label: props.status,
              class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
          };
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium',
            meta.class,
        ]"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
        {{ meta.label }}
    </span>
</template>
