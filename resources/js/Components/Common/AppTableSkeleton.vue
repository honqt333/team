<script setup>
defineProps({
    rows: {
        type: Number,
        default: 5,
    },
    cols: {
        type: Number,
        default: 5,
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
    showPagination: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div
        class="w-full bg-surface rounded-2xl border border-border overflow-hidden shadow-sm animate-pulse"
    >
        <!-- Table Header Skeleton -->
        <div
            v-if="showHeader"
            class="px-6 py-4 bg-surface-muted/80 border-b border-border flex items-center justify-between gap-4"
        >
            <div class="h-4 bg-border rounded-lg w-28"></div>
            <div class="flex items-center gap-2">
                <div class="h-8 bg-border rounded-xl w-24"></div>
                <div class="h-8 bg-border rounded-xl w-20"></div>
            </div>
        </div>

        <!-- Table Grid / Rows -->
        <div class="divide-y divide-border">
            <!-- Column Labels Row -->
            <div
                class="hidden md:grid gap-4 px-6 py-3.5 bg-surface-muted/40"
                :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }"
            >
                <div
                    v-for="c in cols"
                    :key="'head-' + c"
                    class="h-3 bg-border rounded-md"
                    :style="{ width: `${40 + (c % 3) * 20}%` }"
                ></div>
            </div>

            <!-- Data Rows -->
            <div
                v-for="r in rows"
                :key="'row-' + r"
                class="px-6 py-4 flex flex-col md:grid gap-4 items-center"
                :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }"
            >
                <!-- First column has badge/avatar look -->
                <div class="flex items-center gap-3 w-full">
                    <div class="w-9 h-9 rounded-xl bg-border shrink-0"></div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="h-3.5 bg-border rounded-md w-3/4"></div>
                        <div class="h-2.5 bg-surface-muted rounded-md w-1/2"></div>
                    </div>
                </div>

                <!-- Middle Columns -->
                <div
                    v-for="c in cols - 2"
                    :key="'col-' + r + '-' + c"
                    class="w-full flex items-center"
                >
                    <div
                        class="h-3 bg-border rounded-md"
                        :style="{ width: `${50 + ((r + c) % 4) * 15}%` }"
                    ></div>
                </div>

                <!-- Last Column (Actions) -->
                <div class="w-full flex items-center justify-end gap-2">
                    <div class="h-7 w-7 rounded-lg bg-border"></div>
                    <div class="h-7 w-7 rounded-lg bg-border"></div>
                </div>
            </div>
        </div>

        <!-- Pagination Skeleton -->
        <div
            v-if="showPagination"
            class="px-6 py-4 bg-surface-muted/50 border-t border-border flex items-center justify-between"
        >
            <div class="h-3 bg-border rounded-md w-32"></div>
            <div class="flex items-center gap-1.5">
                <div class="h-8 w-8 rounded-lg bg-border"></div>
                <div class="h-8 w-8 rounded-lg bg-border"></div>
                <div class="h-8 w-8 rounded-lg bg-border"></div>
            </div>
        </div>
    </div>
</template>
