<template>
    <SystemLayout>
        <div class="space-y-6 text-gray-900 dark:text-gray-100">
            <!-- Header Banner -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 shadow-xl border border-indigo-900/30"
            >
                <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <div
                            class="flex items-center gap-2 bg-indigo-500/10 text-indigo-400 text-xs px-3 py-1 rounded-full border border-indigo-500/20 w-fit"
                        >
                            <span
                                class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-ping"
                            ></span>
                            {{ $t('system.developer.console_badge') }}
                        </div>
                        <h1 class="text-white text-3xl font-extrabold mt-3 tracking-tight">
                            {{ $t('system.developer.title') }}
                        </h1>
                        <p class="text-indigo-200/70 text-sm mt-1">
                            {{ $t('system.developer.subtitle') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            @click="runFreshAudit"
                            :disabled="loading"
                            class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-2xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 border border-indigo-500/30"
                        >
                            <svg
                                v-if="loading"
                                class="animate-spin h-5 w-5 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"
                                />
                            </svg>
                            {{ $t('system.developer.run_audit') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Overall Scores Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Large radial score display card -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden"
                >
                    <div
                        class="absolute -top-10 -left-10 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"
                    />
                    <h2
                        class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                        {{ $t('system.developer.overall_score') }}
                    </h2>

                    <div class="relative my-6 flex items-center justify-center">
                        <div
                            class="w-36 h-36 rounded-full border-8 border-gray-100 dark:border-gray-700 flex items-center justify-center ring-8 ring-indigo-500/10"
                        >
                            <span class="text-4xl font-black text-indigo-600 dark:text-indigo-400">
                                {{ snapshot ? Math.round(snapshot.score_overall) : 0 }}%
                            </span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span
                            :class="releaseGateStatus.class"
                            class="px-3 py-1 text-xs font-bold rounded-full border"
                        >
                            {{
                                $t('system.developer.release_status', {
                                    status: releaseGateStatus.text,
                                })
                            }}
                        </span>
                        <p class="text-xs text-gray-400 mt-2">
                            {{ $t('system.developer.release_required') }}
                        </p>
                    </div>
                </div>

                <!-- Score breakdown stats grid -->
                <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div
                        v-for="sc in scoreBreakdown"
                        :key="sc.name"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                            >
                                {{ sc.name }}
                            </span>
                            <span
                                :class="
                                    sc.score >= 90
                                        ? 'text-emerald-500'
                                        : sc.score >= 70
                                          ? 'text-amber-500'
                                          : 'text-rose-500'
                                "
                                class="text-sm font-bold"
                            >
                                {{ Math.round(sc.score) }}%
                            </span>
                        </div>
                        <div
                            class="mt-4 bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden"
                        >
                            <div
                                :class="
                                    sc.score >= 90
                                        ? 'bg-emerald-500'
                                        : sc.score >= 70
                                          ? 'bg-amber-500'
                                          : 'bg-rose-500'
                                "
                                class="h-full rounded-full transition-all duration-500"
                                :style="{ width: sc.score + '%' }"
                            ></div>
                        </div>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">
                            {{
                                $t(
                                    sc.score >= 90
                                        ? 'system.developer.gate_passed'
                                        : 'system.developer.gate_attention'
                                )
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Switching Layout -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
            >
                <!-- Tab Headers -->
                <div
                    class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto bg-gray-50 dark:bg-gray-900/50"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="
                            activeTab === tab.id
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        "
                        class="px-6 py-4 font-semibold text-sm transition-all border-b-2 whitespace-nowrap"
                    >
                        {{ $t(`system.developer.tabs.${tab.id}`) }}
                    </button>
                </div>

                <div class="p-6">
                    <!-- Tab 1: Violations Log -->
                    <div v-if="activeTab === 'violations'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg">
                                {{ $t('system.developer.violations_title') }}
                            </h3>
                            <span class="text-xs text-gray-400">
                                {{ $t('system.developer.showing_last') }}
                            </span>
                        </div>

                        <div
                            v-if="violations.length === 0"
                            class="text-center py-12 text-gray-400 dark:text-gray-500"
                        >
                            {{ $t('system.developer.no_violations') }}
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-start border-collapse">
                                <thead>
                                    <tr
                                        class="border-b border-gray-100 dark:border-gray-700 text-left text-xs uppercase text-gray-400 font-bold"
                                    >
                                        <th class="py-3 px-4">
                                            {{ $t('system.developer.col.severity') }}
                                        </th>
                                        <th class="py-3 px-4">
                                            {{ $t('system.developer.col.category') }}
                                        </th>
                                        <th class="py-3 px-4">
                                            {{ $t('system.developer.col.file_path') }}
                                        </th>
                                        <th class="py-3 px-4">
                                            {{ $t('system.developer.col.details') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="v in violations"
                                        :key="v.id"
                                        class="border-b border-gray-50 dark:border-gray-800 text-sm hover:bg-gray-50 dark:hover:bg-gray-850"
                                    >
                                        <td class="py-3 px-4">
                                            <span
                                                :class="
                                                    v.severity === 'critical'
                                                        ? 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                                        : v.severity === 'high'
                                                          ? 'bg-orange-500/10 text-orange-500 border-orange-500/20'
                                                          : 'bg-gray-500/10 text-gray-400 border-gray-500/20'
                                                "
                                                class="px-2 py-0.5 text-xs font-bold rounded-full border"
                                            >
                                                {{ v.severity }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-3 px-4 font-semibold text-indigo-600 dark:text-indigo-400 uppercase text-xs"
                                        >
                                            {{ v.category }}
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs max-w-xs truncate">
                                            {{ v.file_path }}
                                            <span v-if="v.line_number" class="text-indigo-400">
                                                :L{{ v.line_number }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                            {{ v.description }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Dependency Graph Map -->
                    <div v-if="activeTab === 'graph'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg">
                                {{ $t('system.developer.graph_title') }}
                            </h3>
                            <button
                                @click="loadGraphData"
                                class="text-xs text-indigo-500 hover:underline"
                            >
                                {{ $t('system.developer.reload_map') }}
                            </button>
                        </div>
                        <p class="text-xs text-gray-400">
                            {{ $t('system.developer.graph_subtitle') }}
                        </p>

                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-900/50 p-6 h-[400px] flex items-center justify-center relative overflow-hidden"
                        >
                            <div
                                class="relative flex flex-col items-center justify-center gap-6 z-10"
                            >
                                <div
                                    class="bg-indigo-600 text-white font-bold px-6 py-3 rounded-2xl shadow-lg shadow-indigo-600/20 border border-indigo-500/30"
                                >
                                    {{ $t('system.developer.work_orders_module') }}
                                </div>
                                <div class="flex gap-4">
                                    <div
                                        v-for="node in graphNodes"
                                        :key="node"
                                        class="bg-white dark:bg-gray-800 text-xs font-bold px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                                    >
                                        {{ node }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="absolute inset-0 opacity-5 pointer-events-none"
                                style="
                                    background-image:
                                        linear-gradient(
                                            rgba(99, 102, 241, 0.3) 1px,
                                            transparent 1px
                                        ),
                                        linear-gradient(
                                            90deg,
                                            rgba(99, 102, 241, 0.3) 1px,
                                            transparent 1px
                                        );
                                    background-size: 24px 24px;
                                "
                            ></div>
                        </div>
                    </div>

                    <!-- Tab 3: Performance Logs -->
                    <div v-if="activeTab === 'performance'" class="space-y-4">
                        <h3 class="font-bold text-lg">
                            {{ $t('system.developer.performance_title') }}
                        </h3>

                        <div
                            v-if="slowQueries.length === 0"
                            class="text-center py-12 text-gray-400 dark:text-gray-500"
                        >
                            {{ $t('system.developer.no_slow_queries') }}
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="q in slowQueries"
                                :key="q.id"
                                class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="
                                                q.type === 'n_plus_one'
                                                    ? 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                                    : 'bg-amber-500/10 text-amber-500 border-amber-500/20'
                                            "
                                            class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full border"
                                        >
                                            {{ q.type }}
                                        </span>
                                        <span class="text-xs text-gray-400 font-mono">
                                            {{ q.caller }}
                                            <span v-if="q.caller_line">:{{ q.caller_line }}</span>
                                        </span>
                                    </div>
                                    <p
                                        class="font-mono text-xs text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-gray-100 dark:border-gray-750 break-all select-all"
                                    >
                                        {{ q.sql_statement }}
                                    </p>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span
                                        class="text-sm font-black text-gray-800 dark:text-gray-200"
                                    >
                                        {{ q.execution_time_ms }} ms
                                    </span>
                                    <p class="text-[10px] text-gray-400">
                                        {{
                                            $t('system.developer.occurrences', {
                                                n: q.occurrences,
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: AI Architectural Advisor -->
                    <div v-if="activeTab === 'ai_advisor'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <svg
                                    class="w-5 h-5 text-indigo-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                    />
                                </svg>
                                {{ $t('system.developer.ai_advisor_title') }}
                            </h3>
                            <button
                                @click="fetchAiAdvice"
                                :disabled="loadingAi"
                                class="text-xs bg-indigo-600/10 text-indigo-600 hover:bg-indigo-600/20 px-3 py-1.5 rounded-lg border border-indigo-500/20"
                            >
                                {{
                                    loadingAi
                                        ? $t('system.developer.analyzing')
                                        : $t('system.developer.generate_suggestions')
                                }}
                            </button>
                        </div>

                        <div
                            v-if="aiAdviceList.length === 0"
                            class="text-center py-12 text-gray-400 dark:text-gray-500"
                        >
                            {{
                                loadingAi
                                    ? $t('system.developer.consulting_advisor')
                                    : $t('system.developer.no_suggestions')
                            }}
                        </div>

                        <div v-else class="space-y-6">
                            <div
                                v-for="(advice, idx) in aiAdviceList"
                                :key="idx"
                                class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-3xl p-6 space-y-4 shadow-sm"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3"
                                >
                                    <span class="font-bold text-gray-800 dark:text-white">
                                        {{ advice.problem }}
                                    </span>
                                    <span
                                        class="bg-rose-500/10 text-rose-500 border border-rose-500/20 text-xs font-bold px-2 py-0.5 rounded-full uppercase"
                                    >
                                        {{ $t('system.developer.risk', { level: advice.risk }) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                    <div class="space-y-1">
                                        <span
                                            class="text-gray-400 font-bold uppercase tracking-wider"
                                        >
                                            {{ $t('system.developer.impact_evidence') }}
                                        </span>
                                        <p class="text-gray-600 dark:text-gray-300 font-mono">
                                            {{ advice.evidence }}
                                        </p>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="text-gray-400 font-bold uppercase tracking-wider"
                                        >
                                            {{ $t('system.developer.recommended_refactoring') }}
                                        </span>
                                        <p class="text-gray-600 dark:text-gray-300">
                                            {{ advice.solution }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-750 p-4 rounded-2xl"
                                >
                                    <span
                                        class="text-xs text-gray-400 font-bold uppercase tracking-wider"
                                    >
                                        {{ $t('system.developer.implementation_plan') }}
                                    </span>
                                    <ul
                                        class="list-disc pl-5 mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        <li
                                            v-for="(step, sIdx) in advice.plan"
                                            :key="sIdx"
                                            class="font-mono text-xs"
                                        >
                                            {{ step }}
                                        </li>
                                    </ul>
                                </div>

                                <div class="flex gap-4 text-[10px] text-gray-400">
                                    <span>
                                        {{
                                            $t('system.developer.required_test', {
                                                test: advice.tests,
                                            })
                                        }}
                                    </span>
                                    <span>|</span>
                                    <span>
                                        {{
                                            $t('system.developer.rollback_plan', {
                                                plan: advice.rollback,
                                            })
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const { t } = useI18n();

const props = defineProps({
    snapshot: Object,
    violations: Array,
    stats: Array,
    slowQueries: Array,
    aiMemories: Array,
    historical: Array,
});

const loading = ref(false);
const loadingAi = ref(false);
const activeTab = ref('violations');
const aiAdviceList = ref([]);

const tabs = [{ id: 'violations' }, { id: 'graph' }, { id: 'performance' }, { id: 'ai_advisor' }];

// Static dependency-graph node labels. Each label is a translation
// key; the actual text is resolved through i18n at render time via
// the v-for on `graphNodes` (which composes a translated list).
const graphNodeKeys = ['inventory', 'vehicles', 'customers', 'invoices'];
const graphNodes = computed(() => graphNodeKeys.map((k) => t(`system.developer.graph_nodes.${k}`)));

const scoreBreakdown = computed(() => {
    if (!props.snapshot) return [];
    return [
        { name: t('system.developer.score_labels.security'), score: props.snapshot.score_security },
        { name: t('system.developer.score_labels.testing'), score: props.snapshot.score_testing },
        {
            name: t('system.developer.score_labels.architecture'),
            score: props.snapshot.score_architecture,
        },
        {
            name: t('system.developer.score_labels.performance'),
            score: props.snapshot.score_performance,
        },
        { name: t('system.developer.score_labels.ui'), score: props.snapshot.score_ui },
        {
            name: t('system.developer.score_labels.documentation'),
            score: props.snapshot.score_documentation,
        },
    ];
});

const releaseGateStatus = computed(() => {
    if (!props.snapshot) {
        return {
            text: t('system.developer.gate_unknown'),
            class: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        };
    }

    // Checks standard Release Gate rules
    const securityOk = props.snapshot.score_security >= 100;
    const testingOk = props.snapshot.score_testing >= 80;
    const overallOk = props.snapshot.score_overall >= 90;

    if (securityOk && testingOk && overallOk) {
        return {
            text: t('system.developer.gate_approved'),
            class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
        };
    }
    return {
        text: t('system.developer.gate_rejected'),
        class: 'bg-rose-500/10 text-rose-500 border-rose-500/20',
    };
});

const runFreshAudit = () => {
    loading.value = true;
    router.post(
        route('system.developer.audit'),
        {},
        {
            onFinish: () => {
                loading.value = false;
            },
        }
    );
};

const fetchAiAdvice = () => {
    loadingAi.value = true;
    axios
        .post(route('system.developer.ai-advice'))
        .then((res) => {
            aiAdviceList.value = res.data.advice;
        })
        .finally(() => {
            loadingAi.value = false;
        });
};
</script>
