<template>
    <AppLayout>
        <Head :title="$t('settings.cards.print.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('settings.cards.print.title')"
                :subtitle="$t('settings.cards.print.description')"
            >
                <template #back>
                    <BackButton href="/app/settings/integrations" />
                </template>

                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <button
                        type="button"
                        @click="save()"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white font-bold rounded-xl shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin h-4 w-4"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
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
                            {{ form.processing ? $t('common.loading') : $t('common.save') }}
                        </span>
                    </button>
                </template>
            </PageHeader>

            <!-- Main Layout: Settings (left) + Live Preview (right, always visible) -->
            <div class="flex flex-col xl:flex-row gap-6 items-start">
                <!-- ═══════════ Settings Panel (left) ═══════════ -->
                <div
                    class="flex-1 min-w-0 bg-surface rounded-2xl border border-border-subtle shadow-xs overflow-hidden"
                >
                    <!-- Tab Bar -->
                    <AppTabs v-model="activeTab" :tabs="printTabs" variant="underline" class="px-5">
                        <template #icon-appearance>
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
                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
                                />
                            </svg>
                        </template>
                        <template #icon-documents>
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </template>
                    </AppTabs>

                    <!-- ─── Tab: Appearance ─────────────────────────────── -->
                    <div v-show="activeTab === 'appearance'" class="p-6 space-y-6">
                        <!-- Template Selection -->
                        <div>
                            <label
                                class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3"
                            >
                                {{ $t('print_settings.default_template') }}
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    v-for="tmpl in availableTemplates"
                                    :key="tmpl.id"
                                    @click="previewTemplate(tmpl)"
                                    class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 overflow-hidden"
                                    :class="[
                                        form.visual.active_template === tmpl.id
                                            ? 'border-amber-500 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/10 shadow-sm shadow-amber-100 dark:shadow-amber-900/20'
                                            : previewingTemplate === tmpl.id
                                              ? 'border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/10'
                                              : 'border-gray-100 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-gray-50/30 dark:bg-gray-900/20',
                                    ]"
                                >
                                    <!-- Active badge -->
                                    <span
                                        v-if="form.visual.active_template === tmpl.id"
                                        class="absolute top-2.5 end-2.5 inline-flex items-center gap-1 text-[9px] font-black px-2 py-0.5 rounded-full bg-amber-500 text-white"
                                    >
                                        <span class="w-1 h-1 rounded-full bg-white"></span>
                                        {{ $t('print_settings.active_template') }}
                                    </span>
                                    <!-- Previewing badge -->
                                    <span
                                        v-else-if="previewingTemplate === tmpl.id"
                                        class="absolute top-2.5 end-2.5 text-[9px] font-black px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400"
                                    >
                                        {{ $t('print_settings.previewing') }}
                                    </span>

                                    <!-- Page icon -->
                                    <div
                                        class="w-8 h-10 mb-2.5 rounded border flex items-center justify-center flex-shrink-0"
                                        :class="
                                            form.visual.active_template === tmpl.id
                                                ? 'border-amber-200 bg-amber-100/60 dark:bg-amber-900/30'
                                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
                                        "
                                    >
                                        <svg
                                            class="w-4 h-5"
                                            :class="
                                                form.visual.active_template === tmpl.id
                                                    ? 'text-amber-500'
                                                    : 'text-gray-300 dark:text-gray-600'
                                            "
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-xs font-bold text-gray-800 dark:text-gray-100 mb-1"
                                    >
                                        {{ tmpl.name }}
                                    </p>
                                    <p
                                        class="text-[10px] text-gray-400 leading-relaxed line-clamp-2 mb-3"
                                    >
                                        {{ tmpl.description }}
                                    </p>

                                    <div class="flex items-center justify-between">
                                        <button
                                            type="button"
                                            @click.stop="selectTemplate(tmpl)"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg transition-all"
                                            :class="
                                                form.visual.active_template === tmpl.id
                                                    ? 'bg-amber-500 text-white cursor-default shadow-sm'
                                                    : 'border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                            "
                                        >
                                            {{
                                                form.visual.active_template === tmpl.id
                                                    ? $t('print_settings.active_template')
                                                    : $t('common.set_as_default')
                                            }}
                                        </button>
                                        <span class="text-[9px] text-emerald-500 font-bold">
                                            {{ $t('print_settings.included_free') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visual Options -->
                        <div class="space-y-3">
                            <label
                                class="block text-xs font-black text-gray-400 uppercase tracking-widest"
                            >
                                {{ $t('print_settings.brand_primary_color') }}
                            </label>

                            <!-- Show Logo toggle -->
                            <div
                                class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/20"
                            >
                                <span
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    {{ $t('print_settings.show_center_logo') }}
                                </span>
                                <AppToggle
                                    v-model="form.visual.show_logo"
                                    size="sm"
                                    color="warning"
                                />
                            </div>

                            <!-- Color picker -->
                            <div
                                class="flex items-center gap-3 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/20"
                            >
                                <input
                                    v-model="form.visual.primary_color"
                                    type="color"
                                    class="w-9 h-9 rounded-lg border-0 p-0 overflow-hidden bg-transparent cursor-pointer flex-shrink-0"
                                />
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('print_settings.brand_primary_color') }}
                                    </p>
                                    <input
                                        v-model="form.visual.primary_color"
                                        type="text"
                                        dir="ltr"
                                        class="w-full px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-xs focus:ring-1 focus:ring-amber-400 focus:border-amber-400 outline-none"
                                    />
                                </div>
                            </div>

                            <!-- Footer text -->
                            <div
                                class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/20"
                            >
                                <label
                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    {{ $t('print_settings.default_footer_text') }}
                                </label>
                                <textarea
                                    v-model="form.visual.footer_text"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs leading-relaxed resize-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 outline-none transition"
                                    :placeholder="$t('print_settings.footer_placeholder')"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Tab: Documents ──────────────────────────────── -->
                    <div
                        v-show="activeTab === 'documents'"
                        class="flex flex-col sm:flex-row min-h-[460px]"
                    >
                        <!-- Document list sidebar -->
                        <div
                            class="sm:w-60 border-b sm:border-b-0 sm:border-e border-gray-100 dark:border-gray-700 p-3 space-y-1 flex-shrink-0 max-h-[650px] overflow-y-auto"
                        >
                            <button
                                v-for="(doc, key) in form.documents"
                                :key="key"
                                type="button"
                                @click="selectDocument(key)"
                                class="w-full flex items-start gap-2.5 px-3 py-2.5 rounded-xl text-start text-xs font-semibold transition-all group"
                                :class="
                                    selectedDocKey === key
                                        ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/30 border border-transparent'
                                "
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0 mt-0.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <p class="truncate">{{ $t('print_settings.' + key) }}</p>
                                        <span
                                            v-if="doc.template"
                                            class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex-shrink-0"
                                        >
                                            {{ getTemplateShortName(doc.template) }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-[9px] font-normal text-gray-400 truncate mt-0.5 font-mono"
                                    >
                                        {{ doc.title_ar || $t('print_settings.no_custom_title') }}
                                    </p>
                                </div>
                            </button>
                        </div>

                        <!-- Document form -->
                        <div class="flex-1 p-5 overflow-y-auto">
                            <div v-if="selectedDocKey" class="space-y-5">
                                <!-- Title Header -->
                                <div
                                    class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0"
                                        >
                                            <svg
                                                class="w-4 h-4 text-amber-600 dark:text-amber-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3
                                                class="text-sm font-bold text-gray-800 dark:text-gray-100"
                                            >
                                                {{ $t('print_settings.' + selectedDocKey) }}
                                            </h3>
                                            <p class="text-xs text-gray-400">
                                                {{ $t('print_settings.customize') }}
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Active Template Badge for Current Document -->
                                    <span
                                        class="text-[10px] font-bold px-2 py-1 rounded-lg border"
                                        :class="
                                            form.documents[selectedDocKey].template
                                                ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'
                                                : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
                                        "
                                    >
                                        {{
                                            getTemplateName(
                                                form.documents[selectedDocKey].template ||
                                                    form.visual.active_template
                                            )
                                        }}
                                    </span>
                                </div>

                                <!-- Template Link Selector for this specific Document -->
                                <div
                                    class="p-3.5 rounded-xl border border-amber-200/80 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-950/10 space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <label
                                            class="block text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5 text-amber-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            {{ $t('print_settings.document_template_select') }}
                                        </label>
                                        <span
                                            v-if="form.documents[selectedDocKey].template"
                                            class="text-[10px] text-amber-600 dark:text-amber-400 font-bold"
                                        >
                                            {{ $t('print_settings.custom_template_active') }}
                                        </span>
                                        <span v-else class="text-[10px] text-gray-400">
                                            {{ $t('print_settings.using_global_template') }}
                                        </span>
                                    </div>
                                    <SearchableSelect
                                        v-model="form.documents[selectedDocKey].template"
                                        :options="documentTemplateOptions"
                                        :option-value="(option) => option.id"
                                        option-label="label"
                                        required
                                    />
                                </div>

                                <!-- Custom Titles -->
                                <div class="space-y-2">
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest"
                                    >
                                        {{ $t('print_settings.document_title_arabic') }}
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label
                                                class="block text-[10px] text-content-muted mb-1"
                                            >
                                                {{ $t('print_settings.lang_ar') }}
                                            </label>
                                            <input
                                                v-model="form.documents[selectedDocKey].title_ar"
                                                type="text"
                                                dir="rtl"
                                                class="w-full px-3 py-2 rounded-xl border border-border bg-surface text-content-primary text-xs focus:ring-1 focus:ring-action-primary focus:border-action-primary outline-none transition"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] text-content-muted mb-1"
                                            >
                                                {{ $t('print_settings.lang_en') }}
                                            </label>
                                            <input
                                                v-model="form.documents[selectedDocKey].title_en"
                                                type="text"
                                                dir="ltr"
                                                class="w-full px-3 py-2 rounded-xl border border-border bg-surface text-content-primary text-xs focus:ring-1 focus:ring-action-primary focus:border-action-primary outline-none transition"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Options -->
                                <div class="space-y-2">
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest"
                                    >
                                        {{ $t('print_settings.columns.print_terms') }}
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label
                                            v-for="(labelKey, optKey) in docOptions"
                                            :key="optKey"
                                            class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/20 border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-amber-200 dark:hover:border-amber-800 transition-all"
                                        >
                                            <AppToggle
                                                v-model="form.documents[selectedDocKey][optKey]"
                                            />
                                            <span class="text-xs text-gray-600 dark:text-gray-300">
                                                {{ $t('print_settings.' + labelKey) }}
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <button
                                        type="button"
                                        @click="openSignatureModal(selectedDocKey)"
                                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-indigo-200 dark:border-indigo-800 hover:border-indigo-400 dark:hover:border-indigo-600 text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10 font-bold text-xs transition-all"
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
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                            />
                                        </svg>
                                        {{ $t('print_settings.edit_official_signatures') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="openTermsList(selectedDocKey)"
                                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-amber-200 dark:border-amber-800 hover:border-amber-400 dark:hover:border-amber-600 text-amber-600 dark:text-amber-400 bg-amber-50/30 dark:bg-amber-950/10 font-bold text-xs transition-all"
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
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                            />
                                        </svg>
                                        {{ $t('print_settings.edit_terms') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div
                                v-else
                                class="h-full flex flex-col items-center justify-center py-16 text-center text-gray-400"
                            >
                                <svg
                                    class="w-10 h-10 mb-3 opacity-30"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <p class="text-sm">
                                    {{ $t('print_settings.select_document_hint') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ Live Preview Panel (right, always visible) ═══════════ -->
                <div
                    class="xl:w-[430px] flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden sticky top-6"
                >
                    <!-- Preview toolbar -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                    >
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"
                                ></span>
                            </span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200">
                                {{ $t('print_settings.live_preview_platform') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <SearchableSelect
                                v-model="previewDocType"
                                :options="previewDocOptions"
                                option-label="name"
                                option-value="id"
                                compact
                                class="w-36 text-[10px]"
                            />
                            <SearchableSelect
                                v-model="previewScale"
                                :options="previewScaleOptions"
                                option-value="value"
                                option-label="label"
                                required
                                compact
                            />
                        </div>
                    </div>

                    <!-- Paper area -->
                    <div
                        class="bg-gray-900/5 dark:bg-gray-950/20 p-4 flex justify-center overflow-auto max-h-[72vh]"
                    >
                        <div
                            class="transition-all duration-300 shrink-0 transform origin-top"
                            :class="previewScale"
                        >
                            <PrintEngine
                                :documentType="previewDocType"
                                :data="dummyPrintData"
                                :centerData="dummyCenterData"
                                :documentSettings="form.documents[previewDocType]"
                                :visualSettings="previewVisualSettings"
                                :previewMode="true"
                            />
                        </div>
                    </div>

                    <div
                        class="px-4 py-2 text-center text-[9px] text-gray-400 border-t border-gray-100 dark:border-gray-700"
                    >
                        {{ $t('print_settings.preview_disclaimer') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <TermsModal
            v-if="editingDoc"
            :show="true"
            :document="form.documents[editingDoc]"
            :doc-key="editingDoc"
            @close="editingDoc = null"
            @save="handleDocSave"
        />
        <TermsListModal
            v-if="editingTermsDoc"
            :show="true"
            :document="form.documents[editingTermsDoc]"
            :doc-key="editingTermsDoc"
            @close="editingTermsDoc = null"
            @save="handleDocSave"
        />
        <TermEditModal
            v-if="isTermEditOpen"
            :show="true"
            :term="editingTerm"
            @close="closeTermEdit"
            @save="handleTermSaved"
        />
        <SignatureModal
            v-if="editingSignaturesDoc"
            :show="true"
            :document="form.documents[editingSignaturesDoc]"
            :doc-key="editingSignaturesDoc"
            @close="closeSignatureModal"
            @signature-saved="handleSignatureSaved"
            @signature-changed="handleSignatureChanged"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useToast } from '@/Composables/useToast';
import TermsModal from './Modals/TermsModal.vue';
import TermsListModal from './Modals/TermsListModal.vue';
import TermEditModal from './Modals/TermEditModal.vue';
import SignatureModal from './Modals/SignatureModal.vue';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    print_settings: Object,
});

const page = usePage();

const form = useForm({
    section: 'print',
    documents: props.print_settings?.documents ?? {},
    visual: props.print_settings?.visual ?? {
        active_template: 'TemplateDefaultA4',
        show_logo: true,
        primary_color: '#fbbf24',
        footer_text: '',
    },
});

if (!form.visual.active_template) {
    form.visual.active_template = 'TemplateDefaultA4';
}

// ─── Tabs ────────────────────────────────────────────────────────────────────
const activeTab = ref('appearance');

const printTabs = computed(() => [
    { id: 'appearance', label: t('print_settings.general_print_identity') },
    { id: 'documents', label: t('print_settings.documents_title') },
]);

// Document checkbox options: formKey → translation sub-key
const docOptions = {
    print_terms: 'columns.print_terms',
    terms_first_page: 'terms_on_first_page',
    show_stamp: 'enable_stamp',
    show_qr_code: 'show_qr_code_label',
    show_iban: 'show_bank_iban',
    show_customer_address: 'show_customer_address',
};

// ─── Modal state ─────────────────────────────────────────────────────────────
const editingDoc = ref(null);
const editingTermsDoc = ref(null);
const editingSignaturesDoc = ref(null);
const editingTerm = ref(null);
const isTermEditOpen = ref(false);
const editingTermDocKey = ref(null);

// ─── Templates ───────────────────────────────────────────────────────────────
const availableTemplates = [
    {
        id: 'TemplateDefaultA4',
        name: 'Classic A4',
        description: t('print_settings.a4_description'),
    },
    {
        id: 'TemplateThermal80',
        name: 'Thermal 80mm',
        description: t('print_settings.thermal_description_short'),
    },
    {
        id: 'TemplateModernA4',
        name: 'Modern Compact A4',
        description: t('print_settings.a4_description_long'),
    },
    {
        id: 'TemplateSleekThermal',
        name: 'Sleek Thermal 80mm',
        description: t('print_settings.thermal_description'),
    },
];

const previewScaleOptions = computed(() => [
    { value: 'scale-[0.45]', label: '45%' },
    { value: 'scale-[0.55]', label: t('print_settings.zoom_55') },
    { value: 'scale-[0.65]', label: t('print_settings.zoom_65') },
    { value: 'scale-[0.75]', label: t('print_settings.zoom_75') },
]);

const documentTemplateOptions = computed(() => [
    {
        id: null,
        label: `${t('print_settings.default_global_template')} (${getTemplateName(form.visual.active_template)})`,
    },
    ...availableTemplates.map((template) => ({
        id: template.id,
        label: `${template.name} — ${template.description}`,
    })),
]);

function getTemplateName(id) {
    const tmpl = availableTemplates.find((t) => t.id === id);
    return tmpl ? tmpl.name : id || 'Classic A4';
}

function getTemplateShortName(id) {
    if (id === 'TemplateThermal80') return '80mm';
    if (id === 'TemplateSleekThermal') return 'Sleek 80mm';
    if (id === 'TemplateModernA4') return 'Modern A4';
    if (id === 'TemplateDefaultA4') return 'Classic A4';
    return id;
}

const previewingTemplate = ref(form.visual.active_template || 'TemplateDefaultA4');

function previewTemplate(template) {
    previewingTemplate.value = template.id;
}

function selectTemplate(template) {
    form.visual.active_template = template.id;
    previewingTemplate.value = template.id;
}

// ─── Documents ───────────────────────────────────────────────────────────────
const selectedDocKey = ref('invoice');
const previewDocType = ref('invoice');
const previewScale = ref('scale-[0.55]');

const previewDocOptions = computed(() =>
    Object.keys(form.documents || {}).map((key) => ({
        id: key,
        name: t('print_settings.' + key),
    }))
);

function selectDocument(key) {
    selectedDocKey.value = key;
    previewDocType.value = key; // sync preview with selected doc
}

// The template that the preview should actually use:
// If the previewed document has a custom template, use it; otherwise use the currently previewed template.
const effectivePreviewTemplate = computed(() => {
    const docCustom = form.documents[previewDocType.value]?.template;
    if (docCustom) return docCustom;
    return previewingTemplate.value;
});

const previewVisualSettings = computed(() => ({
    ...form.visual,
    active_template: effectivePreviewTemplate.value,
}));

function openEditModal(key) {
    editingDoc.value = key;
}
function openTermsList(key) {
    editingTermsDoc.value = key;
}
function openSignatureModal(key) {
    editingSignaturesDoc.value = key;
}
function closeSignatureModal() {
    editingSignaturesDoc.value = null;
}

function handleDocSave(newDoc) {
    if (editingDoc.value) {
        form.documents[editingDoc.value] = { ...newDoc };
    } else if (editingTermsDoc.value) {
        form.documents[editingTermsDoc.value] = { ...newDoc };
    }
}

function openTermEdit(term) {
    editingTerm.value = term ? { ...term } : null;
    isTermEditOpen.value = true;
}

function closeTermEdit() {
    isTermEditOpen.value = false;
    editingTerm.value = null;
    editingTermDocKey.value = null;
}

function handleTermSaved(updatedTerm) {
    const docKey = editingTermDocKey.value;
    if (!docKey || !form.documents[docKey]) return;
    const doc = form.documents[docKey];
    if (!Array.isArray(doc.terms)) doc.terms = [];

    const cleanTerm = { ...updatedTerm };
    const idx = cleanTerm._index;
    delete cleanTerm._index;

    if (typeof idx === 'number' && idx >= 0 && idx < doc.terms.length) {
        doc.terms[idx] = cleanTerm;
    } else {
        doc.terms.push(cleanTerm);
    }
}

function handleSignatureSaved(sig) {
    const key = editingSignaturesDoc.value;
    if (!key || !sig) return;
    const doc = form.documents[key];
    if (!doc) return;

    const nameAr = sig.name_ar ?? sig.name ?? '';
    const nameEn = sig.name_en ?? sig.name ?? '';
    const list = Array.isArray(doc.signatures) ? [...doc.signatures] : [];

    if (sig.id && list.some((s) => s.id === sig.id)) {
        closeSignatureModal();
        return;
    }

    list.push({
        id: sig.id,
        name_ar: nameAr,
        name_en: nameEn,
        url: sig.url,
        uploaded_at: sig.uploaded_at,
        show: true,
        order: list.length + 1,
    });

    const nextDoc = { ...doc, signatures: list };
    if (!nextDoc.signature) {
        nextDoc.signature = {
            id: sig.id,
            name_ar: nameAr,
            name_en: nameEn,
            url: sig.url,
            show: true,
        };
    }
    form.documents[key] = nextDoc;
    closeSignatureModal();
}

function handleSignatureChanged(payload) {
    const key = editingSignaturesDoc.value;
    if (!key) return;
    const doc = form.documents[key];
    if (!doc || !Array.isArray(doc.signatures)) return;

    if (payload?.action === 'delete') {
        doc.signatures = doc.signatures.filter((s) => s.id !== payload.id);
        if (doc.signature?.id === payload.id) doc.signature = null;
    } else if (
        payload?.action === 'reorder' &&
        Array.isArray(payload.order) &&
        payload.order.length > 0
    ) {
        const byId = new Map(doc.signatures.map((s) => [s.id, s]));
        doc.signatures = payload.order.map((id) => byId.get(id)).filter(Boolean);
    }
    router.reload();
}

watch(
    () => page.props.tenant?.print_settings,
    (newSettings) => {
        if (!newSettings?.documents) return;
        for (const [key, serverDoc] of Object.entries(newSettings.documents)) {
            const local = form.documents[key];
            if (!local) continue;
            local.signatures = Array.isArray(serverDoc.signatures)
                ? serverDoc.signatures.map((s) => ({ ...s }))
                : [];
        }
    },
    { deep: true }
);

function save() {
    form.put('/app/settings/system', {
        preserveScroll: true,
        onSuccess: () => success(t('common.saved_success')),
    });
}

// ─── Center Preview Data ─────────────────────────────────────────────────────
const dummyCenterData = computed(() => ({
    name: t('print_settings.sample_center_name'),
    logo_url: page.props.tenant?.logo_url || '/images/brand/logo-256.png',
    tax_number: '310298374200003',
    address: t('print_settings.sample_address'),
    phone: '+966 55 123 4567',
    website: 'www.khidmapro.com',
    iban: 'SA80 4000 0000 1234 5678 9012',
    stamp_url: props.print_settings?.visual?.stamp_url || '',
}));

// ─── Dynamic Dummy Print Data (Smartly tailored to each document type) ───────
const dummyPrintData = computed(() => {
    const docType = previewDocType.value;

    const base = {
        code:
            docType === 'quotation'
                ? 'QT-2026-0041'
                : docType === 'purchase_invoice'
                  ? 'PINV-8812'
                  : docType === 'purchase_return'
                    ? 'PRET-019'
                    : docType === 'receipt'
                      ? 'REC-5510'
                      : docType === 'credit_note'
                        ? 'CN-0021'
                        : docType === 'parts_invoice'
                          ? 'PINV-3001'
                          : docType === 'subscription_invoice'
                            ? 'SUB-7704'
                            : 'WO-100293',
        created_at: new Date(),
        odometer: '45,210',
        mileage: 45210,
        fuel_level: 70,
        entry_date: new Date(),
        expected_end_date: new Date(Date.now() + 86400000 * 2),
        customer: {
            name: ['purchase_invoice', 'purchase_return'].includes(docType)
                ? t('print_settings.sample_supplier_name')
                : ['payslip', 'employee', 'attendance'].includes(docType)
                  ? t('print_settings.sample_employee_name')
                  : ['payroll', 'customers', 'vehicles'].includes(docType)
                    ? t('print_settings.sample_report_name')
                    : t('print_settings.sample_name'),
            phone: '+966 50 123 4567',
            address: t('print_settings.sample_address'),
            tax_number: '310992384900003',
        },
        vehicle: {
            make: t('print_settings.sample_make'),
            model: t('print_settings.sample_model'),
            year: '2023',
            plate: t('print_settings.sample_plate'),
            color: t('print_settings.pearl_white'),
        },
        damage_marks: [
            { x: 120, y: 140, color: 'red', description: t('print_settings.sample_damage_1') },
            { x: 280, y: 190, color: 'blue', description: t('print_settings.sample_damage_2') },
        ],
        payments: [
            {
                id: 1,
                payment_date: new Date(),
                payment_method: 'card',
                reference: 'AUTH-99120',
                notes: t('print_settings.sample_payment_1'),
                amount: 500,
                type: 'regular',
            },
            {
                id: 2,
                payment_date: new Date(),
                payment_method: 'cash',
                reference: '-',
                notes: t('print_settings.sample_payment_2'),
                amount: 490,
                type: 'regular',
            },
        ],
        amount: 250,
        notes: t('print_settings.sample_description_1'),
    };

    if (['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(docType)) {
        base.items = [
            {
                service_name: t('print_settings.sample_part_1_name'),
                description: t('print_settings.sample_part_1_desc'),
                qty: 2,
                unit_price: 240,
                is_part: true,
            },
            {
                service_name: t('print_settings.sample_part_2_name'),
                description: t('print_settings.sample_part_2_desc'),
                qty: 1,
                unit_price: 65,
                is_part: true,
            },
            {
                service_name: t('print_settings.sample_part_3_name'),
                description: t('print_settings.sample_part_3_desc'),
                qty: 4,
                unit_price: 85,
                is_part: true,
            },
        ];
    } else if (docType === 'payslip') {
        base.items = [
            {
                service_name: t('print_settings.sample_payslip_1_name'),
                description: t('print_settings.sample_payslip_1_desc'),
                qty: 1,
                unit_price: 4500,
                is_part: false,
            },
            {
                service_name: t('print_settings.sample_payslip_2_name'),
                description: t('print_settings.sample_payslip_2_desc'),
                qty: 1,
                unit_price: 1500,
                is_part: false,
            },
            {
                service_name: t('print_settings.sample_payslip_3_name'),
                description: t('print_settings.sample_payslip_3_desc'),
                qty: 1,
                unit_price: 600,
                is_part: false,
            },
        ];
    } else {
        base.items = [
            {
                service_name: t('print_settings.sample_service_computer'),
                description: t('print_settings.sample_description_3'),
                qty: 1,
                unit_price: 150,
                is_part: false,
            },
            {
                service_name: t('print_settings.sample_service_oil'),
                description: t('print_settings.sample_description_2'),
                qty: 1,
                unit_price: 280,
                is_part: false,
            },
            {
                service_name: t('print_settings.sample_service_brakes'),
                description: t('print_settings.sample_description_4'),
                qty: 1,
                unit_price: 320,
                technician: t('print_settings.sample_technician'),
                is_part: false,
            },
            {
                service_name: t('print_settings.sample_line_brakes'),
                description: t('print_settings.sample_part'),
                qty: 1,
                unit_price: 240,
                is_part: true,
            },
        ];
    }

    return base;
});
</script>
