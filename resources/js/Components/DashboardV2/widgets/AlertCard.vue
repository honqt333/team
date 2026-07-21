<script setup>
defineProps({
    title: { type: String, required: true },
    count: { type: Number, default: 0 },
    description: { type: String, default: null },
    href: { type: String, default: null },
    icon: { type: String, default: null },
    color: { type: String, default: 'slate' }, // slate, amber, rose, sky
});

const colorMap = {
    slate: {
        bg: 'bg-slate-50 dark:bg-slate-800/50',
        border: 'border-slate-200 dark:border-slate-700',
        text: 'text-slate-700 dark:text-slate-200',
        iconBg: 'bg-slate-100 dark:bg-slate-700',
        iconText: 'text-slate-600 dark:text-slate-300',
    },
    amber: {
        bg: 'bg-amber-50/50 dark:bg-amber-900/10',
        border: 'border-amber-200 dark:border-amber-800/50',
        text: 'text-amber-700 dark:text-amber-300',
        iconBg: 'bg-amber-100 dark:bg-amber-900/30',
        iconText: 'text-amber-600 dark:text-amber-300',
    },
    rose: {
        bg: 'bg-rose-50/50 dark:bg-rose-900/10',
        border: 'border-rose-200 dark:border-rose-800/50',
        text: 'text-rose-700 dark:text-rose-300',
        iconBg: 'bg-rose-100 dark:bg-rose-900/30',
        iconText: 'text-rose-600 dark:text-rose-300',
    },
    sky: {
        bg: 'bg-sky-50/50 dark:bg-sky-900/10',
        border: 'border-sky-200 dark:border-sky-800/50',
        text: 'text-sky-700 dark:text-sky-300',
        iconBg: 'bg-sky-100 dark:bg-sky-900/30',
        iconText: 'text-sky-600 dark:text-sky-300',
    },
};
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="[
            'group flex items-center gap-3 rounded-lg border p-4 transition-all hover:shadow-sm',
            colorMap[color]?.bg || colorMap.slate.bg,
            colorMap[color]?.border || colorMap.slate.border,
        ]"
    >
        <div
            v-if="icon"
            :class="[
                'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md',
                colorMap[color]?.iconBg || colorMap.slate.iconBg,
                colorMap[color]?.iconText || colorMap.slate.iconText,
            ]"
            v-html="icon"
        />
        <div class="min-w-0 flex-1">
            <p
                :class="[
                    'text-xs font-medium uppercase tracking-wide',
                    colorMap[color]?.text || colorMap.slate.text,
                ]"
            >
                {{ title }}
            </p>
            <p class="mt-0.5 text-2xl font-bold text-slate-900 dark:text-white">
                {{ count }}
            </p>
            <p v-if="description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <svg
            class="h-4 w-4 flex-shrink-0 text-slate-400 transition-transform group-hover:translate-x-1 dark:text-slate-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
            />
        </svg>
    </a>
    <div
        v-else
        :class="[
            'flex items-center gap-3 rounded-lg border p-4',
            colorMap[color]?.bg || colorMap.slate.bg,
            colorMap[color]?.border || colorMap.slate.border,
        ]"
    >
        <div
            v-if="icon"
            :class="[
                'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-md',
                colorMap[color]?.iconBg || colorMap.slate.iconBg,
                colorMap[color]?.iconText || colorMap.slate.iconText,
            ]"
            v-html="icon"
        />
        <div class="min-w-0 flex-1">
            <p
                :class="[
                    'text-xs font-medium uppercase tracking-wide',
                    colorMap[color]?.text || colorMap.slate.text,
                ]"
            >
                {{ title }}
            </p>
            <p class="mt-0.5 text-2xl font-bold text-slate-900 dark:text-white">
                {{ count }}
            </p>
            <p v-if="description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
    </div>
</template>
