<template>
    <div>
        <div class="space-y-6 pb-12">
            <!-- ============================================================ -->
            <!-- Header Section (According to AUDIT_STANDARDS.md)             -->
            <!-- ============================================================ -->
            <div
                class="relative overflow-hidden rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 sm:p-8 shadow-sm"
            >
                <!-- Background decorative SVG with low opacity -->
                <svg
                    class="absolute -end-10 -bottom-10 w-72 h-72 text-action-primary opacity-[0.03] pointer-events-none"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    />
                </svg>

                <div
                    class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
                >
                    <div class="flex items-start sm:items-center gap-5">
                        <!-- Giant Gradient Icon -->
                        <div
                            class="w-16 h-16 rounded-2xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center text-white ring-4 ring-focus-ring shadow-xl shadow-focus-ring-soft shrink-0"
                        >
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <h1
                                    class="text-2xl sm:text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight"
                                >
                                    {{ $t('help.admin.title') }}
                                </h1>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-selection-bg text-action-primary border border-action-primary/25"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-action-primary animate-pulse"
                                    ></span>
                                    <span>{{ $t('help.admin.controller_hint') }}</span>
                                </span>
                            </div>
                            <p
                                class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-2xl leading-relaxed"
                            >
                                {{ $t('help.admin.description') }}
                            </p>
                        </div>
                    </div>

                    <!-- Stats Pill -->
                    <div
                        class="flex items-center gap-4 bg-gray-50 dark:bg-gray-900/50 p-3 px-4 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 self-stretch sm:self-auto justify-around"
                    >
                        <div class="text-center px-2">
                            <span class="block text-lg font-black text-action-primary">
                                {{ form.topics.length }}
                            </span>
                            <span
                                class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                            >
                                {{ $t('help.admin.type.topic') }}
                            </span>
                        </div>
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-center px-2">
                            <span class="block text-lg font-black text-purple-600">
                                {{ form.video_tutorials.length }}
                            </span>
                            <span
                                class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                            >
                                {{ $t('help.admin.type.video') }}
                            </span>
                        </div>
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-center px-2">
                            <span
                                class="block text-lg font-black text-emerald-600 dark:text-emerald-400"
                            >
                                {{ form.faqs.length }}
                            </span>
                            <span
                                class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                            >
                                {{ $t('help.admin.type.faq') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Actions Bar (According to AUDIT_STANDARDS.md)                -->
            <!-- ============================================================ -->
            <div
                class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5 w-full sm:w-auto flex-wrap">
                    <a
                        href="/app/help"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold transition-all hover:-translate-y-0.5 w-full sm:w-auto"
                    >
                        <span>{{ $t('help.admin.preview') }}</span>
                        <svg
                            class="w-4 h-4 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                    </a>

                    <button
                        type="button"
                        @click="handleResetDefaults"
                        :disabled="resetting || form.processing"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 text-xs font-bold transition-all disabled:opacity-50 w-full sm:w-auto"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>{{ $t('help.admin.restore_defaults') }}</span>
                    </button>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-sm font-bold shadow-lg shadow-focus-ring-soft transition-all hover:-translate-y-0.5 disabled:opacity-50 w-full sm:w-auto"
                    >
                        <svg
                            v-if="form.processing"
                            class="w-4 h-4 animate-spin"
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
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span>
                            {{
                                form.processing
                                    ? $t('help.admin.saving')
                                    : $t('common.save_changes')
                            }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- Tabs Navigation                                              -->
            <!-- ============================================================ -->
            <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url />

            <!-- ============================================================ -->
            <!-- TAB 1: Support Channels (قنوات الدعم والتواصل)                 -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'channels'" class="space-y-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-6"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4"
                    >
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ $t('help.admin.support.title') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $t('help.admin.support.description') }}
                            </p>
                        </div>
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- WhatsApp -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.whatsapp') }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.contact_whatsapp"
                                    type="text"
                                    dir="ltr"
                                    placeholder="+966 555 785 658"
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                                />
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $t('help.admin.support.whatsapp_hint') }}
                            </p>
                        </div>

                        <!-- WhatsApp Enabled Toggle -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.show_whatsapp') }}
                            </label>
                            <div
                                class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50"
                            >
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{
                                        form.contact_whatsapp_enabled
                                            ? $t('help.admin.support.enabled')
                                            : $t('help.admin.support.disabled')
                                    }}
                                </span>
                                <AppToggle
                                    v-model="form.contact_whatsapp_enabled"
                                    color="success"
                                />
                            </div>
                        </div>

                        <!-- Support Phone -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.phone') }}
                            </label>
                            <input
                                v-model="form.contact_phone"
                                type="text"
                                dir="ltr"
                                placeholder="920000000"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            />
                        </div>

                        <!-- Support Email -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.email') }}
                            </label>
                            <input
                                v-model="form.contact_email"
                                type="email"
                                dir="ltr"
                                placeholder="support@khidmh.pro"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            />
                        </div>

                        <!-- Support Hours -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.hours') }}
                            </label>
                            <input
                                v-model="form.contact_hours"
                                type="text"
                                :placeholder="
                                    $t('system.help.saturday_-_thursday_800_am_-_1000_pm')
                                "
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            />
                        </div>

                        <!-- External Ticket URL -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.support.ticket_url') }}
                            </label>
                            <input
                                v-model="form.contact_ticket_url"
                                type="url"
                                dir="ltr"
                                placeholder="https://support.khidmh.pro"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 2: Video Tutorials (الشروحات المرئية)                      -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'videos'" class="space-y-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('help.admin.videos.title') }} ({{ form.video_tutorials.length }})
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('help.admin.videos.description') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openVideoModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft hover:-translate-y-0.5 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('help.admin.videos.add') }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="(video, idx) in form.video_tutorials"
                        :key="video.id || idx"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span
                                    class="px-2.5 py-1 rounded-md text-xs font-bold bg-selection-bg text-selection-text"
                                >
                                    {{ video.category || $t('help.admin.videos.category_general') }}
                                </span>
                                <span class="text-xs font-mono font-bold text-gray-400">
                                    ⏱️ {{ video.duration || '00:00' }}
                                </span>
                            </div>

                            <h4
                                class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1"
                            >
                                {{ video.title }}
                            </h4>
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed"
                            >
                                {{ video.description }}
                            </p>

                            <div v-if="video.video_url" class="pt-1">
                                <span
                                    class="text-[11px] font-mono text-action-primary truncate block"
                                >
                                    🔗 {{ video.video_url }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-end gap-2"
                        >
                            <button
                                type="button"
                                @click="openVideoModal(video, idx)"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-action-primary hover:bg-selection-bg transition-colors"
                            >
                                {{ $t('help.admin.edit') }}
                            </button>
                            <button
                                type="button"
                                @click="deleteVideo(idx)"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                            >
                                {{ $t('help.admin.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 3: Knowledge Base Topics (مواضيع دليل المعرفة)              -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'topics'" class="space-y-6">
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('help.admin.topics.title') }}{{ form.topics.length }})
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('help.admin.topics.description') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 w-full sm:w-auto">
                        <div class="w-48">
                            <SearchableSelect
                                v-model="selectedCategoryFilter"
                                :options="categoryFilterOptions"
                                option-label="label"
                                option-value="value"
                                :compact="true"
                            />
                        </div>

                        <button
                            type="button"
                            @click="openTopicModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft hover:-translate-y-0.5 transition-all shrink-0"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>{{ $t('help.admin.topics.add') }}</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(topic, idx) in displayedTopics"
                        :key="topic.id || idx"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <span
                                    class="w-9 h-9 rounded-xl bg-selection-bg text-action-primary font-black text-xs flex items-center justify-center shrink-0 border border-action-primary/25"
                                >
                                    {{ topic.stepNumber || idx + 1 }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ topic.title }}
                                        </h4>
                                        <span
                                            class="px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                                        >
                                            {{ getCategoryName(topic.category) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ topic.summary }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    @click="openTopicModal(topic, getTopicOriginalIndex(topic))"
                                    class="p-2 rounded-lg text-action-primary hover:bg-selection-bg transition-colors"
                                    :title="$t('system.help.edit_topic')"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteTopic(getTopicOriginalIndex(topic))"
                                    class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                    :title="$t('system.help.delete_topic')"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Steps Preview -->
                        <div
                            v-if="topic.steps && topic.steps.length"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3.5 border border-gray-100 dark:border-gray-800 text-xs text-gray-700 dark:text-gray-300"
                        >
                            <span class="font-bold block mb-1 text-gray-900 dark:text-white">
                                {{ $t('help.admin.steps.title') }}{{ topic.steps.length }}):
                            </span>
                            <ol
                                class="list-decimal list-inside space-y-1 ps-1 text-[11px] text-gray-600 dark:text-gray-400"
                            >
                                <li
                                    v-for="(step, sIdx) in topic.steps"
                                    :key="sIdx"
                                    class="leading-relaxed"
                                >
                                    {{ step }}
                                </li>
                            </ol>
                        </div>

                        <!-- Footer Tags -->
                        <div class="flex items-center gap-3 text-xs flex-wrap pt-1">
                            <span
                                v-if="topic.tip"
                                class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 text-[11px]"
                            >
                                {{ $t('help.admin.steps.tip') }} {{ topic.tip }}
                            </span>
                            <span
                                v-if="topic.link"
                                class="inline-flex items-center gap-1 text-action-primary text-[11px] font-mono"
                            >
                                {{ $t('help.admin.steps.link') }} {{ topic.link.label }} ({{
                                    topic.link.url
                                }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 4: Categories (أقسام الدليل)                               -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'categories'" class="space-y-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('help.admin.categories.title') }}{{ form.categories.length }})
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('help.admin.categories.description') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCategoryModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft hover:-translate-y-0.5 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('help.admin.categories.add') }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(cat, idx) in form.categories"
                        :key="cat.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-selection-bg text-action-primary font-bold flex items-center justify-center shrink-0"
                            >
                                #
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ cat.name }}
                                </h4>
                                <span class="text-xs font-mono text-gray-400">
                                    ID: {{ cat.id }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="openCategoryModal(cat, idx)"
                                class="p-1.5 rounded-lg text-action-primary hover:bg-selection-bg"
                            >
                                {{ $t('help.admin.edit') }}
                            </button>
                            <button
                                type="button"
                                @click="deleteCategory(idx)"
                                class="p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                            >
                                {{ $t('help.admin.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 5: FAQs (الأسئلة الشائعة)                                   -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'faqs'" class="space-y-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('help.admin.faq.title') }}{{ form.faqs.length }})
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('help.admin.faq.description') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openFaqModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft hover:-translate-y-0.5 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('help.admin.faq.add') }}</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(faq, idx) in form.faqs"
                        :key="faq.id || idx"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-2"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-2.5">
                                <span class="font-black text-action-primary text-sm">
                                    {{ $t('help.admin.faq.prefix') }}
                                </span>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ faq.q }}
                                </h4>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="openFaqModal(faq, idx)"
                                    class="px-2.5 py-1 text-xs font-bold text-action-primary hover:bg-selection-bg rounded-lg"
                                >
                                    {{ $t('help.admin.edit') }}
                                </button>
                                <button
                                    type="button"
                                    @click="deleteFaq(idx)"
                                    class="px-2.5 py-1 text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                                >
                                    {{ $t('help.admin.delete') }}
                                </button>
                            </div>
                        </div>

                        <div class="ps-6 text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 6: Quick Start Items (بطاقات البدء السريع)                  -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'quick_start'" class="space-y-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $t('help.admin.quick_cards.title') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $t('help.admin.quick_cards.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div
                        v-for="(item, idx) in form.quick_start_items"
                        :key="item.id || idx"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3"
                        >
                            <span class="text-xs font-bold text-action-primary">
                                {{ $t('help.admin.quick_cards.number') }}{{ idx + 1 }}
                            </span>
                            <span class="text-xs font-mono text-gray-400">
                                {{ $t('help.admin.quick_cards.go_to') }} {{ item.targetCategory }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('help.admin.quick_cards.card_title') }}
                                </label>
                                <input
                                    v-model="item.title"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs font-bold"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('help.admin.quick_cards.short_description') }}
                                </label>
                                <textarea
                                    v-model="item.desc"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs"
                                ></textarea>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('help.admin.quick_cards.target_section') }}
                                </label>
                                <SearchableSelect
                                    v-model="item.targetCategory"
                                    :options="categoryFormOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('system.help.select_the_section')"
                                    :compact="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- MODALS                                                       -->
        <!-- ============================================================ -->

        <!-- Video Modal -->
        <BaseModal
            :show="videoModal.open"
            :title="
                videoModal.isEditing ? $t('help.admin.videos.edit') : $t('help.admin.videos.add')
            "
            size="md"
            @close="videoModal.open = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.videos.video_title') }}
                    </label>
                    <input
                        v-model="videoModal.form.title"
                        type="text"
                        :placeholder="
                            $t('system.help.e.g._the_work_order_lifecycle_from_entry_to_exit')
                        "
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('help.admin.category') }}
                        </label>
                        <input
                            v-model="videoModal.form.category"
                            type="text"
                            :placeholder="$t('system.help.e.g._work_orders')"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('help.admin.videos.duration_auto') }}
                            </label>
                            <span
                                v-if="fetchingDuration"
                                class="text-[11px] text-action-primary font-medium flex items-center gap-1"
                            >
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
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
                                        d="M4 12a8 8 0 018-8v8H4z"
                                    ></path>
                                </svg>
                                {{ $t('help.admin.videos.fetching') }}
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model="videoModal.form.duration"
                                type="text"
                                dir="ltr"
                                placeholder="05:20"
                                class="w-full px-4 py-2.5 pe-28 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono"
                            />
                            <button
                                v-if="videoModal.form.video_url"
                                type="button"
                                @click="autoFetchVideoDuration"
                                :disabled="fetchingDuration"
                                class="absolute inset-y-1 end-1 px-2.5 bg-selection-bg hover:bg-selection-bg text-action-primary rounded-lg text-[11px] font-bold transition-colors flex items-center gap-1"
                                :title="
                                    $t(
                                        'system.help.recalculate_the_duration_automatically_from_the_'
                                    )
                                "
                            >
                                <span>{{ $t('help.admin.videos.calculate') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.videos.url') }}
                    </label>
                    <input
                        v-model="videoModal.form.video_url"
                        @change="autoFetchVideoDuration"
                        @paste="onVideoUrlPaste"
                        type="url"
                        dir="ltr"
                        :placeholder="$t('system.help.video_url_example')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono"
                    />
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                        {{ $t('help.admin.videos.url_hint') }}
                    </p>

                    <!-- Live preview in modal -->
                    <div
                        v-if="getVideoEmbed(videoModal.form.video_url)"
                        class="mt-3 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 aspect-video bg-black max-h-52"
                    >
                        <iframe
                            v-if="
                                ['youtube', 'vimeo', 'iframe'].includes(
                                    getVideoEmbed(videoModal.form.video_url).type
                                )
                            "
                            :src="getVideoEmbed(videoModal.form.video_url).src"
                            class="w-full h-full border-0"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                            "
                            allowfullscreen
                        ></iframe>
                        <video
                            v-else-if="getVideoEmbed(videoModal.form.video_url).type === 'video'"
                            controls
                            class="w-full h-full"
                            :src="getVideoEmbed(videoModal.form.video_url).src"
                        ></video>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.videos.guide_description') }}
                    </label>
                    <textarea
                        v-model="videoModal.form.description"
                        rows="3"
                        :placeholder="
                            $t('system.help.a_simple_explanation_of_what_the_training_video_')
                        "
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2.5 w-full">
                    <button
                        type="button"
                        @click="videoModal.open = false"
                        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveVideoModal"
                        class="px-5 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft"
                    >
                        {{ $t('help.admin.videos.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Topic Modal -->
        <BaseModal
            :show="topicModal.open"
            :title="
                topicModal.isEditing
                    ? $t('help.admin.topics.edit')
                    : $t('help.admin.topics.add_modal')
            "
            size="lg"
            @close="topicModal.open = false"
        >
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('help.admin.topics.parent_section') }}
                        </label>
                        <SearchableSelect
                            v-model="topicModal.form.category"
                            :options="categoryFormOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('system.help.select_the_section')"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('help.admin.topics.step_number') }}
                        </label>
                        <input
                            v-model="topicModal.form.stepNumber"
                            type="text"
                            placeholder="01"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono text-center"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.topics.topic_title') }}
                    </label>
                    <input
                        v-model="topicModal.form.title"
                        type="text"
                        :placeholder="
                            $t('system.help.e.g._how_do_you_create_a_new_work_order_for_a_ve')
                        "
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.topics.summary') }}
                    </label>
                    <input
                        v-model="topicModal.form.summary"
                        type="text"
                        :placeholder="$t('system.help.a_short_description_of_this_guide')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>

                <!-- Steps list manager -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            {{ $t('help.admin.topics.steps') }}{{ topicModal.form.steps.length }})
                        </label>
                        <button
                            type="button"
                            @click="addTopicStep"
                            class="text-xs text-action-primary font-bold hover:underline"
                        >
                            {{ $t('help.admin.topics.add_step') }}
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="(st, sIdx) in topicModal.form.steps"
                            :key="sIdx"
                            class="flex items-center gap-2"
                        >
                            <span class="w-6 text-xs font-mono text-gray-400 text-center">
                                {{ sIdx + 1 }}.
                            </span>
                            <input
                                v-model="topicModal.form.steps[sIdx]"
                                type="text"
                                :placeholder="$t('system.help.write_the_step_here')"
                                class="flex-1 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs"
                            />
                            <button
                                type="button"
                                @click="removeTopicStep(sIdx)"
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg"
                                :title="$t('system.help.delete_this_step')"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.topics.tip_placeholder') }}
                    </label>
                    <input
                        v-model="topicModal.form.tip"
                        type="text"
                        :placeholder="
                            $t('system.help.e.g._you_can_attach_photos_of_the_vehicle_damage')
                        "
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('help.admin.topics.link_title') }}
                        </label>
                        <input
                            v-model="topicModal.form.linkLabel"
                            type="text"
                            :placeholder="$t('system.help.go_to_work_orders')"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('help.admin.topics.link_url') }}
                        </label>
                        <input
                            v-model="topicModal.form.linkUrl"
                            type="text"
                            dir="ltr"
                            placeholder="/app/work-orders"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono"
                        />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2.5 w-full">
                    <button
                        type="button"
                        @click="topicModal.open = false"
                        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveTopicModal"
                        class="px-5 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft"
                    >
                        {{ $t('help.admin.topics.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Category Modal -->
        <BaseModal
            :show="categoryModal.open"
            :title="
                categoryModal.isEditing
                    ? $t('help.admin.categories.edit')
                    : $t('help.admin.categories.add_modal')
            "
            size="sm"
            @close="categoryModal.open = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.categories.slug') }}
                    </label>
                    <input
                        v-model="categoryModal.form.id"
                        type="text"
                        dir="ltr"
                        placeholder="work_orders"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.categories.name') }}
                    </label>
                    <input
                        v-model="categoryModal.form.name"
                        type="text"
                        :placeholder="$t('system.help.work_orders_and_services')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2.5 w-full">
                    <button
                        type="button"
                        @click="categoryModal.open = false"
                        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveCategoryModal"
                        class="px-5 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft"
                    >
                        {{ $t('help.admin.categories.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- FAQ Modal -->
        <BaseModal
            :show="faqModal.open"
            :title="faqModal.isEditing ? $t('help.admin.faq.edit') : $t('help.admin.faq.add')"
            size="md"
            @close="faqModal.open = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.faq.question') }}
                    </label>
                    <input
                        v-model="faqModal.form.q"
                        type="text"
                        :placeholder="$t('system.help.write_the_question_here')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('help.admin.faq.answer') }}
                    </label>
                    <textarea
                        v-model="faqModal.form.a"
                        rows="4"
                        :placeholder="$t('system.help.write_the_detailed_answer_here')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2.5 w-full">
                    <button
                        type="button"
                        @click="faqModal.open = false"
                        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveFaqModal"
                        class="px-5 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold shadow-md shadow-focus-ring-soft"
                    >
                        {{ $t('help.admin.faq.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed, h } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useVideoEmbed } from '@/Composables/useVideoEmbed';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const { success: toastSuccess, error: toastError } = useToast();
const { confirm } = useConfirm();

const activeTab = ref('channels');
const resetting = ref(false);
const selectedCategoryFilter = ref('all');

// Reactive form with all help settings
const form = useForm({
    contact_whatsapp: props.settings?.contact_whatsapp || '+966 555 785 658',
    contact_phone: props.settings?.contact_phone || '920000000',
    contact_email: props.settings?.contact_email || 'support@khidmh.pro',
    contact_hours: props.settings?.contact_hours || t('help.support.hours'),
    contact_whatsapp_enabled: props.settings?.contact_whatsapp_enabled !== false,
    contact_ticket_url: props.settings?.contact_ticket_url || '',

    quick_start_items: JSON.parse(JSON.stringify(props.settings?.quick_start_items || [])),
    video_tutorials: JSON.parse(JSON.stringify(props.settings?.video_tutorials || [])),
    categories: JSON.parse(JSON.stringify(props.settings?.categories || [])),
    topics: JSON.parse(JSON.stringify(props.settings?.topics || [])),
    faqs: JSON.parse(JSON.stringify(props.settings?.faqs || [])),
});

const categoryFilterOptions = computed(() => [
    { value: 'all', label: t('help.admin.filter.all_sections') },
    ...form.categories.map((c) => ({ value: c.id, label: c.name })),
]);

const categoryFormOptions = computed(() =>
    form.categories.map((c) => ({ value: c.id, label: `${c.name} (${c.id})` }))
);

// Render-function icons for tabs
const IconPhone = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        }),
    ]);

const IconVideo = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
        }),
    ]);

const IconBook = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        }),
    ]);

const IconFolder = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        }),
    ]);

const IconQuestion = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        }),
    ]);

const IconLightning = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M13 10V3L4 14h7v7l9-11h-7z',
        }),
    ]);

const tabs = computed(() => [
    { id: 'channels', label: t('help.admin.filter.support'), icon: IconPhone },
    {
        id: 'videos',
        label: t('help.admin.filter.videos'),
        count: form.video_tutorials.length,
        icon: IconVideo,
    },
    {
        id: 'topics',
        label: t('help.admin.filter.topics'),
        count: form.topics.length,
        icon: IconBook,
    },
    {
        id: 'categories',
        label: t('help.admin.filter.sections'),
        count: form.categories.length,
        icon: IconFolder,
    },
    { id: 'faqs', label: t('help.admin.filter.faq'), count: form.faqs.length, icon: IconQuestion },
    {
        id: 'quick_start',
        label: t('help.admin.filter.quick_cards'),
        count: form.quick_start_items.length,
        icon: IconLightning,
    },
]);

// Helper for topics filtering
const displayedTopics = computed(() => {
    if (selectedCategoryFilter.value === 'all') {
        return form.topics;
    }
    return form.topics.filter((t) => t.category === selectedCategoryFilter.value);
});

const getTopicOriginalIndex = (topic) => {
    return form.topics.indexOf(topic);
};

const getCategoryName = (catId) => {
    const cat = form.categories.find((c) => c.id === catId);
    return cat ? cat.name : catId;
};

// Form submit
const submitForm = () => {
    form.put('/system/settings/help', {
        preserveScroll: true,
        onSuccess: () => {
            toastSuccess(t('help.admin.messages.saved'));
        },
        onError: () => {
            toastError(t('help.admin.messages.save_error'));
        },
    });
};

// Reset defaults handler
const handleResetDefaults = async () => {
    const confirmed = await confirm({
        title: t('help.admin.restore_confirm_title'),
        message: t('help.admin.restore_confirm_body'),
        confirmText: t('help.admin.restore_confirm_yes'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    resetting.value = true;
    router.post(
        '/system/settings/help/reset',
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toastSuccess(t('help.admin.messages.restored'));
                // Update local form state from refreshed props
                form.contact_whatsapp = props.settings.contact_whatsapp;
                form.contact_phone = props.settings.contact_phone;
                form.contact_email = props.settings.contact_email;
                form.contact_hours = props.settings.contact_hours;
                form.contact_whatsapp_enabled = props.settings.contact_whatsapp_enabled;
                form.contact_ticket_url = props.settings.contact_ticket_url;
                form.quick_start_items = JSON.parse(
                    JSON.stringify(props.settings.quick_start_items || [])
                );
                form.video_tutorials = JSON.parse(
                    JSON.stringify(props.settings.video_tutorials || [])
                );
                form.categories = JSON.parse(JSON.stringify(props.settings.categories || []));
                form.topics = JSON.parse(JSON.stringify(props.settings.topics || []));
                form.faqs = JSON.parse(JSON.stringify(props.settings.faqs || []));
            },
            onFinish: () => {
                resetting.value = false;
            },
        }
    );
};

// ============================================================================
// Modals State & Handlers
// ============================================================================

const { getVideoEmbed } = useVideoEmbed();
// Video Modal
const videoModal = ref({
    open: false,
    isEditing: false,
    index: -1,
    form: {
        id: '',
        title: '',
        category: '',
        duration: '',
        video_url: '',
        description: '',
    },
});

const openVideoModal = (video = null, index = -1) => {
    if (video) {
        videoModal.value = {
            open: true,
            isEditing: true,
            index,
            form: { ...video },
        };
    } else {
        videoModal.value = {
            open: true,
            isEditing: false,
            index: -1,
            form: {
                id: 'v_' + Date.now(),
                title: '',
                category: form.categories[0]?.name || t('help.admin.videos.category_general'),
                duration: '05:00',
                video_url: '',
                description: '',
            },
        };
    }
};

const saveVideoModal = () => {
    if (!videoModal.value.form.title) {
        toastError(t('help.admin.messages.video_title_required'));
        return;
    }
    if (videoModal.value.isEditing && videoModal.value.index >= 0) {
        form.video_tutorials[videoModal.value.index] = { ...videoModal.value.form };
    } else {
        form.video_tutorials.push({ ...videoModal.value.form });
    }
    videoModal.value.open = false;
    toastSuccess(t('help.admin.messages.videos_updated'));
};

const deleteVideo = async (index) => {
    const confirmed = await confirm({
        title: t('help.admin.delete_video_title'),
        message: t('help.admin.delete_video_body'),
        confirmText: t('help.admin.delete'),
        cancelText: t('common.cancel'),
    });
    if (!confirmed) return;
    form.video_tutorials.splice(index, 1);
    toastSuccess(t('help.admin.messages.video_deleted'));
};

const fetchingDuration = ref(false);

const autoFetchVideoDuration = async () => {
    const url = videoModal.value.form.video_url?.trim();
    if (!url) return;

    fetchingDuration.value = true;
    try {
        // 1. If it's a direct browser-playable video file (.mp4, .webm, .ogg)
        if (/\.(mp4|webm|ogg)($|\?)/i.test(url)) {
            const v = document.createElement('video');
            v.preload = 'metadata';
            v.onloadedmetadata = () => {
                const sec = Math.floor(v.duration);
                if (sec > 0) {
                    const m = Math.floor(sec / 60);
                    const s = sec % 60;
                    videoModal.value.form.duration = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    toastSuccess(
                        t('help.admin.messages.duration_calculated', {
                            duration: videoModal.value.form.duration,
                        })
                    );
                }
                fetchingDuration.value = false;
            };
            v.onerror = () => {
                fetchingDuration.value = false;
            };
            v.src = url;
            return;
        }

        // 2. Call backend endpoint for YouTube, Vimeo, etc.
        const response = await axios.post('/system/settings/help/fetch-video-meta', { url });
        if (response.data?.success && response.data?.duration) {
            videoModal.value.form.duration = response.data.duration;
            if (!videoModal.value.form.title && response.data.title) {
                videoModal.value.form.title = response.data.title;
            }
            toastSuccess(
                t('help.admin.messages.duration_calculated', { duration: response.data.duration })
            );
        }
    } catch (e) {
        // silent fallback
    } finally {
        fetchingDuration.value = false;
    }
};

const onVideoUrlPaste = () => {
    setTimeout(() => {
        autoFetchVideoDuration();
    }, 120);
};

// Topic Modal
const topicModal = ref({
    open: false,
    isEditing: false,
    index: -1,
    form: {
        id: 0,
        category: '',
        stepNumber: '01',
        title: '',
        summary: '',
        steps: [''],
        tip: '',
        linkLabel: '',
        linkUrl: '',
    },
});

const openTopicModal = (topic = null, index = -1) => {
    if (topic) {
        topicModal.value = {
            open: true,
            isEditing: true,
            index,
            form: {
                id: topic.id,
                category: topic.category,
                stepNumber: topic.stepNumber || '01',
                title: topic.title,
                summary: topic.summary || '',
                steps: Array.isArray(topic.steps) ? [...topic.steps] : [''],
                tip: topic.tip || '',
                linkLabel: topic.link?.label || '',
                linkUrl: topic.link?.url || '',
            },
        };
    } else {
        topicModal.value = {
            open: true,
            isEditing: false,
            index: -1,
            form: {
                id: Date.now(),
                category: form.categories[0]?.id || 'work_orders',
                stepNumber: '0' + (form.topics.length + 1),
                title: '',
                summary: '',
                steps: [t('help.admin.steps.first_step')],
                tip: '',
                linkLabel: '',
                linkUrl: '',
            },
        };
    }
};

const addTopicStep = () => {
    topicModal.value.form.steps.push('');
};

const removeTopicStep = (sIdx) => {
    if (topicModal.value.form.steps.length > 1) {
        topicModal.value.form.steps.splice(sIdx, 1);
    }
};

const saveTopicModal = () => {
    if (!topicModal.value.form.title) {
        toastError(t('help.admin.messages.topic_title_required'));
        return;
    }

    const cleanedSteps = topicModal.value.form.steps.filter((s) => s.trim() !== '');

    const topicData = {
        id: topicModal.value.form.id,
        category: topicModal.value.form.category,
        stepNumber: topicModal.value.form.stepNumber,
        title: topicModal.value.form.title,
        summary: topicModal.value.form.summary,
        steps: cleanedSteps,
        tip: topicModal.value.form.tip || null,
        link:
            topicModal.value.form.linkLabel && topicModal.value.form.linkUrl
                ? { label: topicModal.value.form.linkLabel, url: topicModal.value.form.linkUrl }
                : null,
    };

    if (topicModal.value.isEditing && topicModal.value.index >= 0) {
        form.topics[topicModal.value.index] = topicData;
    } else {
        form.topics.push(topicData);
    }

    topicModal.value.open = false;
    toastSuccess(t('help.admin.messages.topic_updated'));
};

const deleteTopic = async (index) => {
    const confirmed = await confirm({
        title: t('help.admin.delete_topic_title'),
        message: t('help.admin.delete_topic_body'),
        confirmText: t('help.admin.delete'),
        cancelText: t('common.cancel'),
    });
    if (!confirmed) return;
    form.topics.splice(index, 1);
    toastSuccess(t('help.admin.messages.topic_deleted'));
};

// Category Modal
const categoryModal = ref({
    open: false,
    isEditing: false,
    index: -1,
    form: {
        id: '',
        name: '',
    },
});

const openCategoryModal = (cat = null, index = -1) => {
    if (cat) {
        categoryModal.value = {
            open: true,
            isEditing: true,
            index,
            form: { ...cat },
        };
    } else {
        categoryModal.value = {
            open: true,
            isEditing: false,
            index: -1,
            form: {
                id: '',
                name: '',
            },
        };
    }
};

const saveCategoryModal = () => {
    if (!categoryModal.value.form.id || !categoryModal.value.form.name) {
        toastError(t('help.admin.messages.section_required'));
        return;
    }
    if (categoryModal.value.isEditing && categoryModal.value.index >= 0) {
        form.categories[categoryModal.value.index] = { ...categoryModal.value.form };
    } else {
        form.categories.push({ ...categoryModal.value.form });
    }
    categoryModal.value.open = false;
    toastSuccess(t('help.admin.messages.sections_updated'));
};

const deleteCategory = async (index) => {
    const confirmed = await confirm({
        title: t('help.admin.delete_section_title'),
        message: t('help.admin.delete_section_body'),
        confirmText: t('help.admin.delete'),
        cancelText: t('common.cancel'),
    });
    if (!confirmed) return;
    form.categories.splice(index, 1);
    toastSuccess(t('help.admin.messages.section_deleted'));
};

// FAQ Modal
const faqModal = ref({
    open: false,
    isEditing: false,
    index: -1,
    form: {
        id: '',
        q: '',
        a: '',
    },
});

const openFaqModal = (faq = null, index = -1) => {
    if (faq) {
        faqModal.value = {
            open: true,
            isEditing: true,
            index,
            form: { ...faq },
        };
    } else {
        faqModal.value = {
            open: true,
            isEditing: false,
            index: -1,
            form: {
                id: 'faq_' + Date.now(),
                q: '',
                a: '',
            },
        };
    }
};

const saveFaqModal = () => {
    if (!faqModal.value.form.q || !faqModal.value.form.a) {
        toastError(t('help.admin.messages.faq_required'));
        return;
    }
    if (faqModal.value.isEditing && faqModal.value.index >= 0) {
        form.faqs[faqModal.value.index] = { ...faqModal.value.form };
    } else {
        form.faqs.push({ ...faqModal.value.form });
    }
    faqModal.value.open = false;
    toastSuccess(t('help.admin.messages.faq_updated'));
};

const deleteFaq = async (index) => {
    const confirmed = await confirm({
        title: t('help.admin.delete_question_title'),
        message: t('help.admin.delete_question_body'),
        confirmText: t('help.admin.delete'),
        cancelText: t('common.cancel'),
    });
    if (!confirmed) return;
    form.faqs.splice(index, 1);
    toastSuccess(t('help.admin.messages.question_deleted'));
};
</script>
