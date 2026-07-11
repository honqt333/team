<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('settings.cards.print.title')"
                :subtitle="$t('settings.cards.print.description')"
                gradientFrom="from-amber-500"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #back>
                    <BackButton href="/app/settings" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        @click="save()"
                        :disabled="form.processing"
                        class="group relative inline-flex items-center gap-2 px-8 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold rounded-xl transition-all hover:scale-[1.02] hover:shadow-lg hover:shadow-amber-500/25 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden"
                    >
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"
                        ></div>
                        <svg
                            v-if="form.processing"
                            class="animate-spin h-5 w-5"
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
                        <span class="relative">
                            {{ form.processing ? $t('common.loading') : $t('common.save') }}
                        </span>
                    </button>
                </template>
            </PageHeader>

            <!-- Main Split-Screen Container -->
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Right Side: Settings Control Panel (5/12 width on large screen) -->
                <div class="w-full lg:w-5/12 space-y-6">
                    <!-- Section 1: Template Selection & Visual Styles -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                    >
                        <h3
                            class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-5"
                        >
                            <svg
                                class="w-5 h-5 text-amber-500"
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
                            نموذج وهوية الطباعة العامة
                        </h3>

                        <!-- Active Template Selector -->
                        <div class="space-y-3 mb-6">
                            <label
                                class="block text-xs font-black text-gray-400 uppercase tracking-widest"
                            >
                                قالب الطباعة الافتراضي
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="tmpl in availableTemplates"
                                    :key="tmpl.id"
                                    @click="previewTemplate(tmpl)"
                                    class="p-4 rounded-2xl border text-start transition-all duration-300 flex flex-col justify-between min-h-[110px] relative overflow-hidden group cursor-pointer"
                                    :class="[
                                        form.visual.active_template === tmpl.id
                                            ? 'border-amber-500 bg-amber-500/[0.04] ring-2 ring-amber-500/20'
                                            : previewingTemplate === tmpl.id
                                              ? 'border-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10 ring-2 ring-indigo-400/20'
                                              : 'border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/30 hover:border-gray-200 dark:hover:border-gray-700',
                                    ]"
                                >
                                    <!-- Previewing indicator -->
                                    <div
                                        v-if="
                                            previewingTemplate === tmpl.id &&
                                            form.visual.active_template !== tmpl.id
                                        "
                                        class="absolute top-2 right-2"
                                    >
                                        <span
                                            class="text-[8px] font-black px-1.5 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400"
                                        >
                                            جاري المعاينة
                                        </span>
                                    </div>

                                    <div class="flex flex-col gap-1 pr-1 pt-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-black text-gray-800 dark:text-gray-200"
                                            >
                                                {{ tmpl.name }}
                                            </span>
                                            <span
                                                v-if="form.visual.active_template === tmpl.id"
                                                class="w-1.5 h-1.5 rounded-full bg-amber-500"
                                            ></span>
                                        </div>
                                        <span
                                            class="text-[10px] text-gray-400 leading-normal line-clamp-2"
                                        >
                                            {{ tmpl.description }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex justify-between items-center mt-3 pt-2 border-t border-gray-100/50 dark:border-gray-800/50 w-full"
                                    >
                                        <button
                                            type="button"
                                            @click.stop="selectTemplate(tmpl)"
                                            class="text-[9px] font-bold px-2.5 py-1 rounded-lg transition-all"
                                            :class="
                                                form.visual.active_template === tmpl.id
                                                    ? 'bg-amber-500 text-white shadow-sm cursor-default'
                                                    : 'bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 dark:bg-gray-800 dark:border-gray-750 dark:text-gray-300'
                                            "
                                        >
                                            {{
                                                form.visual.active_template === tmpl.id
                                                    ? 'القالب النشط'
                                                    : 'تعيين كافتراضي'
                                            }}
                                        </button>
                                        <span class="text-[9px] text-emerald-500 font-bold">
                                            مشمول مجاناً
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- General Toggles & Color -->
                        <div class="space-y-3.5">
                            <!-- Show Logo -->
                            <div
                                class="relative p-3 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/30 dark:bg-gray-900/30 transition-all"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-semibold text-gray-700 dark:text-gray-300"
                                        >
                                            إظهار شعار المركز
                                        </span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.visual.show_logo"
                                            class="sr-only peer"
                                        />
                                        <div
                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"
                                        ></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Primary color -->
                            <div
                                class="relative p-3 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/30 dark:bg-gray-900/30 transition-all"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        اللون الأساسي للهوية
                                    </label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="form.visual.primary_color"
                                        type="color"
                                        class="w-10 h-10 rounded-lg border-0 p-0 overflow-hidden bg-transparent cursor-pointer"
                                    />
                                    <input
                                        v-model="form.visual.primary_color"
                                        type="text"
                                        class="flex-1 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-xs"
                                        dir="ltr"
                                    />
                                </div>
                            </div>

                            <!-- Footer text -->
                            <div
                                class="relative p-3 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/30 dark:bg-gray-900/30 transition-all"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <label
                                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        النص التذييلي الافتراضي (الفوتر)
                                    </label>
                                </div>
                                <textarea
                                    v-model="form.visual.footer_text"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs leading-normal"
                                    :placeholder="$t('print_settings.footer_placeholder')"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Document Customizations -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
                    >
                        <h3
                            class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4"
                        >
                            <svg
                                class="w-5 h-5 text-emerald-500"
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
                            إعدادات المستندات الخاصة
                        </h3>

                        <!-- Document Selector Drawer list -->
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <button
                                v-for="(doc, key) in form.documents"
                                :key="key"
                                type="button"
                                @click="selectDocument(key)"
                                class="w-full p-3 rounded-xl border text-start transition-all flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50"
                                :class="
                                    selectedDocKey === key
                                        ? 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 font-bold'
                                        : 'border-gray-100 dark:border-gray-700/60 bg-transparent'
                                "
                            >
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-800 dark:text-gray-200">
                                        {{ $t('print_settings.' + key) }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-mono font-normal">
                                        {{ doc.title_ar || 'بدون عنوان مخصص' }}
                                    </span>
                                </div>
                                <svg
                                    class="w-4 h-4 text-gray-400"
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
                            </button>
                        </div>

                        <!-- Active Document Form Customization (Visible only when a document is clicked) -->
                        <div
                            v-if="selectedDocKey"
                            class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700/80 space-y-4"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    تخصيص: {{ $t('print_settings.' + selectedDocKey) }}
                                </span>
                                <button
                                    type="button"
                                    @click="selectedDocKey = null"
                                    class="text-[10px] text-gray-400 hover:text-red-500"
                                >
                                    إغلاق التخصيص
                                </button>
                            </div>

                            <!-- Title Inputs (Arabic & English) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[10px] font-bold text-gray-400 uppercase"
                                    >
                                        عنوان المستند المطبوع (عربي)
                                    </label>
                                    <input
                                        v-model="form.documents[selectedDocKey].title_ar"
                                        type="text"
                                        class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-950 dark:text-white text-xs text-end"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="block text-[10px] font-bold text-gray-400 uppercase"
                                    >
                                        Document Print Title (English)
                                    </label>
                                    <input
                                        v-model="form.documents[selectedDocKey].title_en"
                                        type="text"
                                        class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-950 dark:text-white text-xs text-start"
                                    />
                                </div>
                            </div>

                            <!-- Specific Document Toggles -->
                            <div
                                class="grid grid-cols-2 gap-2 text-[10px] text-gray-600 dark:text-gray-300 font-semibold"
                            >
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.documents[selectedDocKey].print_terms"
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>طباعة الشروط</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.documents[selectedDocKey].terms_first_page"
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>الشروط بالصفحة الأولى</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.documents[selectedDocKey].show_stamp"
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>تفعيل الختم</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.documents[selectedDocKey].show_qr_code"
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>إظهار كود QR</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.documents[selectedDocKey].show_iban"
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>إظهار IBAN البنكي</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 p-2 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700/50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="
                                            form.documents[selectedDocKey].show_customer_address
                                        "
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span>إظهار عنوان العميل</span>
                                </label>
                            </div>

                            <!-- Signatures & Terms Custom Modals Links -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="openSignatureModal(selectedDocKey)"
                                    class="flex items-center justify-center gap-1.5 py-2 px-3 border border-indigo-200 hover:border-indigo-400 text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10 rounded-xl text-[10px] font-bold transition-all"
                                >
                                    <svg
                                        class="w-3.5 h-3.5"
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
                                    تعديل التوقيعات الرسمية
                                </button>
                                <button
                                    type="button"
                                    @click="openTermsList(selectedDocKey)"
                                    class="flex items-center justify-center gap-1.5 py-2 px-3 border border-amber-200 hover:border-amber-400 text-amber-600 dark:text-amber-400 bg-amber-50/30 dark:bg-amber-950/10 rounded-xl text-[10px] font-bold transition-all"
                                >
                                    <svg
                                        class="w-3.5 h-3.5"
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
                                    تعديل الشروط والأحكام
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Side: Interactive Live Preview Sandbox (7/12 width) -->
                <div
                    class="w-full lg:w-7/12 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 sticky top-6"
                >
                    <!-- Sandbox Control Bar -->
                    <div
                        class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6 flex-wrap gap-4"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"
                            ></span>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                منصة المعاينة التفاعلية الحية
                            </h3>
                        </div>

                        <!-- Toolbar Options -->
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <!-- Document selector in preview -->
                            <select
                                v-model="previewDocType"
                                class="px-2 py-1 rounded bg-gray-50 border border-gray-200 dark:bg-gray-700 dark:border-gray-600 text-gray-800 dark:text-gray-200 text-xs cursor-pointer focus:ring-0 focus:outline-none"
                            >
                                <option
                                    v-for="(doc, key) in form.documents"
                                    :key="key"
                                    :value="key"
                                >
                                    معاينة: {{ $t('print_settings.' + key) }}
                                </option>
                            </select>

                            <!-- Zoom / Scale controls -->
                            <select
                                v-model="previewScale"
                                class="px-2 py-1 rounded bg-gray-50 border border-gray-200 dark:bg-gray-700 dark:border-gray-600 text-gray-800 dark:text-gray-200 text-xs cursor-pointer focus:ring-0 focus:outline-none"
                            >
                                <option value="scale-[0.55]">مكبر (55%)</option>
                                <option value="scale-[0.65]">مكبر (65%)</option>
                                <option value="scale-[0.75]">مكبر (75%)</option>
                                <option value="scale-[0.85]">مكبر (85%)</option>
                                <option value="scale-[1.0]">الحجم الأصلي (100%)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Outer Paper Container -->
                    <div
                        class="bg-gray-900/5 dark:bg-gray-950/20 p-6 rounded-xl border border-dashed border-gray-200 dark:border-gray-800 flex justify-center overflow-auto max-h-[70vh]"
                    >
                        <!-- Scaled Paper Engine Box -->
                        <div
                            class="transition-all duration-300 shrink-0 transform"
                            :class="previewScale"
                        >
                            <PrintEngine
                                :documentType="previewDocType"
                                :data="dummyPrintData"
                                :centerData="dummyCenterData"
                                :documentSettings="form.documents[previewDocType]"
                                :visualSettings="{
                                    ...form.visual,
                                    active_template: previewingTemplate,
                                }"
                                :previewMode="true"
                            />
                        </div>
                    </div>

                    <div class="mt-4 text-[10px] text-gray-400 text-center select-none">
                        * المعاينة تمثل الشكل التقريبي النهائي للمستند عند الطباعة.
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms Modal -->
        <TermsModal
            v-if="editingDoc"
            :show="true"
            :document="form.documents[editingDoc]"
            :doc-key="editingDoc"
            @close="editingDoc = null"
            @save="handleDocSave"
        />

        <!-- Terms List Modal -->
        <TermsListModal
            v-if="editingTermsDoc"
            :show="true"
            :document="form.documents[editingTermsDoc]"
            :doc-key="editingTermsDoc"
            @close="editingTermsDoc = null"
            @save="handleDocSave"
        />
        <!-- Term Edit Modal (top-level binding) -->
        <TermEditModal
            v-if="isTermEditOpen"
            :show="true"
            :term="editingTerm"
            @close="closeTermEdit"
            @save="handleTermSaved"
        />
        <!-- Signature Modal (draw / upload / library) -->
        <SignatureModal
            v-if="editingSignaturesDoc"
            :show="true"
            :document="form.documents[editingSignaturesDoc]"
            :doc-key="editingSignaturesDoc"
            @close="closeSignatureModal"
            @signature-saved="handleSignatureSaved"
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import { useToast } from '@/Composables/useToast';
import TermsModal from './Modals/TermsModal.vue';
import TermsListModal from './Modals/TermsListModal.vue';
import TermEditModal from './Modals/TermEditModal.vue';
import SignatureModal from './Modals/SignatureModal.vue';

// Import newly created PrintEngine wrapper
import PrintEngine from '@/Components/Print/PrintEngine.vue';

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

// If visual.active_template is missing in backend, set default
if (!form.visual.active_template) {
    form.visual.active_template = 'TemplateDefaultA4';
}

const editingDoc = ref(null);
const editingTermsDoc = ref(null);
const editingSignaturesDoc = ref(null);

// Term-edit modal state (top-level binding for TermEditModal).
// `editingTerm` holds the term being edited; null when modal is closed.
// `isTermEditOpen` controls modal visibility (also gated by v-if in template).
// `editingTermDocKey` tracks which document's terms list to mutate on save
// because TermEditModal emits only the term payload (no document context).
const editingTerm = ref(null);
const isTermEditOpen = ref(false);
const editingTermDocKey = ref(null);


const availableTemplates = [
    {
        id: 'TemplateDefaultA4',
        name: 'Classic A4',
        description: 'طباعة A4 للمكاتب والمستندات الرسمية والمبيعات',
    },
    {
        id: 'TemplateThermal80',
        name: 'Thermal 80mm',
        description: 'طباعة حرارية ضيقة للفواتير السريعة والعملاء ورسائل الواتساب',
    },
    {
        id: 'TemplateModernA4',
        name: 'Modern Compact A4',
        description: 'قالب A4 عصري وموفر للحبر ذو تصميم مضغوط وأنيق وجدول خدمات ملون',
    },
    {
        id: 'TemplateSleekThermal',
        name: 'Sleek Thermal 80mm',
        description: 'تصميم إيصال حراري حديث بلمسة داكنة وتفاصيل انسيابية وتصميم أنيق',
    },
];

// Tracks which template is being *previewed* (may differ from saved active_template)
const previewingTemplate = ref(form.visual.active_template || 'TemplateDefaultA4');

// Click on card body → preview only (no save)
function previewTemplate(template) {
    previewingTemplate.value = template.id;
}

// Click on "تعيين كافتراضي" button → save as default
function selectTemplate(template) {
    form.visual.active_template = template.id;
    previewingTemplate.value = template.id;
}

// Sandbox reactive state
const selectedDocKey = ref('invoice'); // Current active customization
const previewDocType = ref('invoice'); // Current active preview document
const previewScale = ref('scale-[0.65]'); // Default zoom layout

// Select document to customize and preview
function selectDocument(key) {
    selectedDocKey.value = key;
    previewDocType.value = key;
}

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

// Open TermEditModal for a specific term in a specific document.
// `term` may be:
//   - an existing term object (with optional `_index` to know position) → edit mode
//   - a blank template object → add mode (no _index)
function openTermEdit(term) {
    editingTerm.value = term ? { ...term } : null;
    isTermEditOpen.value = true;
}

function closeTermEdit() {
    isTermEditOpen.value = false;
    editingTerm.value = null;
    editingTermDocKey.value = null;
}

// Receive a saved term from TermEditModal and update the document's terms
// list in place (no reload, no server call). Decision logic:
//   - If the saved term carries a valid `_index` → update at that position
//   - Otherwise → append as a new term
// `_index` is stripped before storing so it never leaks into the form payload.
function handleTermSaved(updatedTerm) {
    const docKey = editingTermDocKey.value;
    if (!docKey || !form.documents[docKey]) {
        return;
    }
    const doc = form.documents[docKey];
    if (!Array.isArray(doc.terms)) {
        doc.terms = [];
    }

    const cleanTerm = { ...updatedTerm };
    const idx = cleanTerm._index;
    delete cleanTerm._index;

    if (typeof idx === 'number' && idx >= 0 && idx < doc.terms.length) {
        // Edit existing term in place
        doc.terms[idx] = cleanTerm;
    } else {
        // Append new term
        doc.terms.push(cleanTerm);
    }
}

/**
 * Called by SignatureModal after the user saves / picks a signature.
 * We append it to the current document's `signatures` list and ensure
 * the doc has a `signature` placeholder for the TemplateDefaultA4 footer
 * (the first signature becomes the default).
 */
function handleSignatureSaved(sig) {
    const key = editingSignaturesDoc.value;
    if (!key || !sig) return;

    const doc = form.documents[key];
    if (!doc) return;

    const list = Array.isArray(doc.signatures) ? [...doc.signatures] : [];
    // Dedupe by id — if it already exists, no-op
    if (sig.id && list.some((s) => s.id === sig.id)) {
        closeSignatureModal();
        return;
    }
    list.push({
        id: sig.id,
        name_ar: sig.name_ar || doc.signature?.name_ar || '',
        name_en: sig.name_en || doc.name_en || '',
        url: sig.url,
        uploaded_at: sig.uploaded_at,
        show: true,
        order: list.length + 1,
    });
    // Stamp the first signature as the default footer reference
    const nextDoc = { ...doc, signatures: list };
    if (!nextDoc.signature) {
        nextDoc.signature = {
            id: sig.id,
            name_ar: sig.name_ar || '',
            name_en: sig.name_en || '',
            url: sig.url,
            show: true,
        };
    }
    form.documents[key] = nextDoc;
    closeSignatureModal();
}

function save() {
    form.put('/app/settings/system', {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
        },
    });
}

// Sandbox Dummy Data
const dummyCenterData = computed(() => ({
    name: 'مركز خدمة برو',
    logo_url: page.props.tenant?.logo_url || '/images/logo.png',
    tax_number: '310298374200003',
    address: 'الرياض، حي الصحافة، طريق الملك عبد العزيز',
    phone: '+966 55 123 4567',
    website: 'www.khidmapro.com',
    iban: 'SA80 4000 0000 1234 5678 9012',
    stamp_url: props.print_settings?.visual?.stamp_url || '',
}));

const dummyPrintData = computed(() => ({
    code: 'WO-100293',
    created_at: new Date(),
    odometer: '45,210',
    expected_end_date: new Date(Date.now() + 86400000 * 2), // 2 days from now
    customer: {
        name: 'أحمد عبد الله القحطاني',
        phone: '+966 50 123 4567',
    },
    vehicle: {
        make: 'تويوتا',
        model: 'كامري GLE',
        year: '2023',
        plate: 'ح ص ك 9922',
        color: 'أبيض لؤلؤي',
    },
    items: [
        {
            service_name: 'فحص كمبيوتر وبرمجة كاملة',
            description: 'فحص جميع الحساسات ومسح الأخطاء السابقة وإعادة التدوير',
            qty: 1,
            unit_price: 150,
            is_part: false,
        },
        {
            service_name: 'تغيير زيت المحرك مع الفلتر',
            description: 'زيت سينثيتك بالكامل 5W-30 يقطع مسافة 10,000 كم مع السيفون الأصلي',
            qty: 1,
            unit_price: 280,
            is_part: false,
        },
        {
            service_name: 'تغيير فحمات الفرامل الأمامية',
            description: 'قطع غيار أصلية مع خرط الهوبات الأمامية وضبط الميزانية',
            qty: 1,
            unit_price: 320,
            technician: 'المهندس سليم',
            is_part: false,
        },
        {
            service_name: 'فحمات فرامل أمامية (قطع غيار)',
            description: 'من المستودع الرئيسي - تويوتا أصلي',
            qty: 1,
            unit_price: 240,
            is_part: true,
        },
    ],
    amount: 250, // For receipt
    notes: 'دفعة مقدمة لصيانة السيارة المذكورة أعلاه وبدء أعمال الفحص والبرمجة.',
}));
</script>
