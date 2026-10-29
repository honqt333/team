<template>
    <AppLayout>
        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <PageHeader
                title="معالج الربط الذاتي مع هيئة الزكاة (ZATCA)"
                subtitle="ربط المنظومة بمنصة فاتورة وإصدار شهادات الفوترة الإلكترونية (المرحلة الثانية)"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-700"
                glowFrom="from-emerald-500"
                badgeBg="bg-emerald-50/50 dark:bg-emerald-900/30"
                badgeText="text-emerald-600 dark:text-emerald-400"
                badgeBorder="border-emerald-100/50 dark:border-emerald-800/30"
                badgeDot="bg-emerald-500"
            >
                <template #back>
                    <BackButton href="/app/settings/company" />
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
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <div
                        v-if="setting.is_active || setting.is_simulated"
                        class="flex items-center gap-2"
                    >
                        <span
                            v-if="setting.is_active"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>{{ $t('zatca.link_active_approved') }}</span>
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700"
                        >
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>{{ $t('zatca.simulation_not_zatca_certificate') }}</span>
                        </span>
                        <button
                            @click="confirmReset"
                            class="px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors"
                        >
                            {{ $t('zatca.reset_handler') }}
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Stepper Progress Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700/60 shadow-sm"
            >
                <div class="grid grid-cols-4 gap-2">
                    <div
                        v-for="(st, idx) in steps"
                        :key="st.id"
                        class="relative flex flex-col items-center text-center group cursor-pointer"
                        @click="jumpToStepIfAllowed(idx + 1)"
                    >
                        <div
                            :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-200 z-10',
                                currentStep === idx + 1
                                    ? 'bg-emerald-600 text-white ring-4 ring-emerald-100 dark:ring-emerald-900/40 shadow-md'
                                    : currentStep > idx + 1 ||
                                        (idx + 1 === 4 &&
                                            (setting.is_active || setting.is_simulated))
                                      ? 'bg-emerald-500 text-white'
                                      : 'bg-gray-100 dark:bg-gray-700 text-gray-400',
                            ]"
                        >
                            <svg
                                v-if="
                                    currentStep > idx + 1 ||
                                    (idx + 1 === 4 && (setting.is_active || setting.is_simulated))
                                "
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span v-else>{{ idx + 1 }}</span>
                        </div>
                        <span
                            :class="[
                                'text-xs font-bold mt-2.5 transition-colors line-clamp-1',
                                currentStep === idx + 1
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-gray-500 dark:text-gray-400',
                            ]"
                        >
                            {{ st.title }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Step 1: CSR & Solution Details -->
            <div
                v-if="currentStep === 1"
                class="bg-white dark:bg-gray-800 rounded-2xl p-7 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-6"
            >
                <div
                    class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-5"
                >
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('zatca.step_1_title') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('zatca.key_pair_description') }}
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                    >
                        {{ $t('zatca.step_1_of_4') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('zatca.establishment_name') }}
                        </label>
                        <input
                            type="text"
                            disabled
                            :value="tenant.legal_name || tenant.name"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 text-sm font-medium"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('zatca.tax_number_15') }}
                        </label>
                        <input
                            type="text"
                            disabled
                            :value="tenant.vat_number"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 text-sm font-mono font-bold"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('zatca.link_environment') }}
                        </label>
                        <SearchableSelect
                            v-model="csrForm.environment"
                            :options="environmentOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="اختر بيئة الربط..."
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('zatca.egs_serial_number') }}
                        </label>
                        <input
                            v-model="csrForm.egs_serial_number"
                            type="text"
                            placeholder="توليد تلقائي إذا تُرك فارغاً"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div
                    v-if="setting.has_csr"
                    class="p-4 bg-emerald-50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-emerald-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span>{{ $t('zatca.csr_created') }}</span>
                    </div>
                    <button
                        @click="currentStep = 2"
                        class="text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:underline"
                    >
                        {{ $t('zatca.continue_to_step_2') }}
                    </button>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button
                        @click="submitCsr"
                        :disabled="csrForm.processing"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50 flex items-center gap-2"
                    >
                        <span>
                            {{
                                setting.has_csr
                                    ? $t('zatca.regenerate_csr')
                                    : $t('zatca.generate_csr_and_continue')
                            }}
                        </span>
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
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step 2: OTP from Fatoora Portal -->
            <div
                v-if="currentStep === 2"
                class="bg-white dark:bg-gray-800 rounded-2xl p-7 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-6"
            >
                <div
                    class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-5"
                >
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('zatca.step_2_title') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('zatca.otp_instructions') }}
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                    >
                        {{ $t('zatca.step_2_of_4') }}
                    </span>
                </div>

                <!-- Guidance Box -->
                <div
                    class="p-4 bg-sky-50 dark:bg-sky-950/20 rounded-xl border border-sky-200 dark:border-sky-800 text-xs text-sky-900 dark:text-sky-300 space-y-3"
                >
                    <h4 class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span>{{ $t('zatca.how_to_get_otp') }}</span>
                    </h4>
                    <ol
                        class="list-decimal list-inside space-y-1 ps-2 leading-relaxed text-sky-800 dark:text-sky-200"
                    >
                        <li>
                            {{ $t('zatca.go_to_fatoora') }}
                            <a
                                href="https://fatoora.zatca.gov.sa"
                                target="_blank"
                                rel="noopener"
                                class="underline font-bold"
                            >
                                fatoora.zatca.gov.sa
                            </a>
                            {{ $t('zatca.with_your_account') }}
                        </li>
                        <li>{{ $t('zatca.go_to_egs_section') }}</li>
                        <li>{{ $t('zatca.add_egs_hint') }}</li>
                        <li>{{ $t('zatca.otp_hint') }}</li>
                    </ol>

                    <div
                        v-if="props.setting.environment === 'production'"
                        class="p-3 bg-amber-100/70 dark:bg-amber-950/40 rounded-lg border border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-200 font-medium"
                    >
                        ⚠️
                        <strong>{{ $t('zatca.live_production_mode') }}</strong>
                        {{ $t('zatca.test_code_rejected') }}
                    </div>
                    <div
                        v-else
                        class="p-3 bg-emerald-100/70 dark:bg-emerald-950/40 rounded-lg border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200 font-medium"
                    >
                        💡
                        <strong>{{ $t('zatca.simulation_env_label') }}</strong>
                        {{ $t('zatca.can_use_test_code') }}
                        <strong>123456</strong>
                        {{ $t('zatca.for_successful_test') }}
                    </div>
                </div>

                <div class="max-w-md mx-auto py-3 space-y-4 text-center">
                    <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                        {{ $t('zatca.fatoora_otp') }}
                    </label>
                    <input
                        v-model="otpForm.otp"
                        type="text"
                        maxlength="6"
                        :placeholder="
                            props.setting.environment === 'production'
                                ? 'أدخل الرمز الفعلي'
                                : '123456'
                        "
                        class="w-full text-center text-2xl font-mono font-black tracking-[0.5em] px-4 py-3 rounded-2xl border-2 border-emerald-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-4 focus:ring-emerald-200 dark:focus:ring-emerald-900/40"
                    />
                    <p v-if="otpForm.errors.otp" class="text-xs text-red-500 font-bold">
                        {{ otpForm.errors.otp }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        @click="currentStep = 1"
                        class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        {{ $t('zatca.go_back') }}
                    </button>
                    <button
                        @click="submitOtp"
                        :disabled="otpForm.processing || !otpForm.otp || otpForm.otp.length < 6"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50 flex items-center gap-2"
                    >
                        <span>{{ $t('zatca.request_certificate_now') }}</span>
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
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step 3: Compliance Checks Simulation -->
            <div
                v-if="currentStep === 3"
                class="bg-white dark:bg-gray-800 rounded-2xl p-7 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-6"
            >
                <div
                    class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-5"
                >
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $t('zatca.step_3_title') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('zatca.samples_requirement') }}
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                    >
                        {{ $t('zatca.step_3_of_4') }}
                    </span>
                </div>

                <!-- Checks List -->
                <div class="space-y-3">
                    <div
                        v-for="(chk, idx) in complianceChecks"
                        :key="chk.code"
                        class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-start justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 shrink-0 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center font-bold text-xs"
                            >
                                0{{ idx + 1 }}
                            </div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ chk.name }}
                                </h5>
                                <span class="text-xs text-gray-500 font-mono">{{ chk.code }}</span>
                                <p
                                    v-if="chk.detail"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed"
                                >
                                    {{ chk.detail }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <span
                                v-if="chk.status === 'PASSED'"
                                class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center gap-1.5"
                            >
                                <svg
                                    class="w-4 h-4 text-emerald-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>{{ $t('zatca.status_passed') }}</span>
                            </span>
                            <span
                                v-else-if="chk.status === 'FAILED'"
                                class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"
                            >
                                <span>{{ $t('zatca.status_failed') }}</span>
                            </span>
                            <span
                                v-else-if="chk.status === 'NOT_EXECUTED'"
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                <span>{{ $t('zatca.status_not_executed') }}</span>
                            </span>
                            <span
                                v-else-if="runningChecks"
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 animate-pulse"
                            >
                                {{ $t('zatca.testing') }}
                            </span>
                            <span
                                v-else
                                class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                {{ $t('work_orders.work_order_statuses.ready_for_qc') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- The result the server actually returned. It is never
                     assumed to be a success: the invoice checks cannot be
                     executed by this build, and the copy has to say so. -->
                <div
                    v-if="checksMessage"
                    class="p-4 rounded-xl border text-xs leading-relaxed"
                    :class="
                        checksStatus === 'failed'
                            ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50 text-red-800 dark:text-red-300'
                            : 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300'
                    "
                >
                    {{ checksMessage }}
                </div>

                <div
                    class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        @click="currentStep = 2"
                        class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        {{ $t('zatca.go_back') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <button
                            @click="runComplianceTests"
                            :disabled="runningChecks"
                            class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50"
                        >
                            {{ runningChecks ? $t('zatca.checking_now') : $t('zatca.run_checks') }}
                        </button>
                        <button
                            @click="submitProduction"
                            :disabled="submittingProd"
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50 flex items-center gap-2"
                        >
                            <span>{{ $t('zatca.production_certificate') }}</span>
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
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Active Production CSID Dashboard -->
            <div
                v-if="currentStep === 4 || setting.is_active || setting.is_simulated"
                class="space-y-6"
            >
                <!-- A simulated credential must never be presented as a
                     live, officially approved integration. -->
                <div
                    v-if="setting.is_simulated"
                    class="bg-gradient-to-br from-amber-900 to-stone-950 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl border border-amber-700/40"
                >
                    <div
                        class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-amber-500/10 blur-3xl"
                    ></div>
                    <div
                        class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
                    >
                        <div class="space-y-2">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30 flex items-center gap-1.5 w-fit"
                            >
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span>{{ $t('zatca.simulation_not_live_phase_2') }}</span>
                            </span>
                            <h3 class="text-2xl font-black mt-2">
                                {{ $t('zatca.test_only_identifier') }}
                            </h3>
                            <p class="text-xs text-amber-200/80 max-w-xl leading-relaxed">
                                {{ $t('zatca.sandbox_identifier_warning') }}
                            </p>
                        </div>
                        <div
                            class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center shrink-0"
                        >
                            <svg
                                class="w-10 h-10 text-amber-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="bg-gradient-to-br from-emerald-900 to-teal-950 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl border border-emerald-700/40"
                >
                    <div
                        class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-emerald-500/10 blur-3xl"
                    ></div>
                    <div
                        class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
                    >
                        <div class="space-y-2">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1.5 w-fit"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"
                                ></span>
                                <span>{{ $t('zatca.phase_2_official') }}</span>
                            </span>
                            <h3 class="text-2xl font-black mt-2">
                                {{ $t('zatca.fully_connected') }}
                            </h3>
                            <p class="text-xs text-emerald-200/80 max-w-xl leading-relaxed">
                                {{ $t('zatca.qr_description') }}
                            </p>
                        </div>
                        <div
                            class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center shrink-0"
                        >
                            <svg
                                class="w-10 h-10 text-emerald-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Integration Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div
                        class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-2"
                    >
                        <span class="text-xs font-bold text-gray-400 uppercase">
                            {{ $t('zatca.device_serial_number') }}
                        </span>
                        <div class="text-sm font-black font-mono text-gray-900 dark:text-white">
                            {{ setting.egs_serial_number || '1-CARAG|2-V2|MAIN' }}
                        </div>
                        <p class="text-[11px] text-gray-500">
                            {{ $t('zatca.registered_device_id') }}
                        </p>
                    </div>

                    <div
                        class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-2"
                    >
                        <span class="text-xs font-bold text-gray-400 uppercase">
                            {{ $t('zatca.current_environment') }}
                        </span>
                        <div
                            class="text-sm font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>
                                {{
                                    setting.environment === 'production'
                                        ? $t('zatca.env_production')
                                        : $t('zatca.env_simulation_approved')
                                }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500">
                            {{
                                setting.is_simulated
                                    ? $t('zatca.simulation_not_certified')
                                    : $t('zatca.link_approved_success')
                            }}
                        </p>
                    </div>

                    <div
                        class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-2"
                    >
                        <span class="text-xs font-bold text-gray-400 uppercase">
                            {{ $t('zatca.certificate_validity') }}
                        </span>
                        <div class="text-sm font-black font-mono text-gray-900 dark:text-white">
                            {{
                                setting.is_simulated
                                    ? $t('zatca.not_applicable_simulation')
                                    : setting.production_csid_expires_at ||
                                      $t('zatca.valid_for_a_year')
                            }}
                        </div>
                        <p class="text-[11px] text-gray-500">
                            {{ $t('zatca.production_certificate_renewal_date') }}
                        </p>
                    </div>
                </div>

                <div
                    class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between"
                >
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $t('zatca.reissue_or_change_env') }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $t('zatca.restart_hint') }}
                        </p>
                    </div>
                    <button
                        @click="confirmReset"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-300 rounded-xl transition-colors"
                    >
                        {{ $t('zatca.restart_handler') }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const environmentOptions = [
    { value: 'simulation', label: t('zatca.env_simulation_gateway') },
    { value: 'production', label: t('zatca.env_production_gateway') },
    { value: 'sandbox', label: t('zatca.env_developer_sandbox') },
];

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    setting: {
        type: Object,
        required: true,
    },
});

const currentStep = ref(
    props.setting.is_active || props.setting.is_simulated
        ? 4
        : props.setting.has_compliance_csid
          ? 3
          : props.setting.has_csr
            ? 2
            : 1
);

const steps = [
    { id: 1, title: t('zatca.billing_device_csr') },
    { id: 2, title: t('zatca.otp') },
    { id: 3, title: t('zatca.compliance_check') },
    { id: 4, title: t('zatca.live_link_active') },
];

const csrForm = useForm({
    environment: props.setting.environment || 'simulation',
    egs_serial_number: props.setting.egs_serial_number || '',
});

const otpForm = useForm({
    otp: '',
});

const runningChecks = ref(false);
const submittingProd = ref(false);

// The check list is filled by the server. It starts empty and is
// NEVER optimistically marked as passed: the previous version set
// `passed = true` from a setTimeout without ever calling the backend,
// which is how the wizard could report a green result for checks that
// had not run.
const complianceChecks = ref([]);
const checksStatus = ref(null);
const checksMessage = ref('');

const jumpToStepIfAllowed = (target) => {
    if (props.setting.is_active || props.setting.is_simulated) {
        currentStep.value = target;
        return;
    }
    if (target === 1) currentStep.value = 1;
    if (target === 2 && props.setting.has_csr) currentStep.value = 2;
    if (target === 3 && props.setting.has_compliance_csid) currentStep.value = 3;
    if (target === 4 && props.setting.has_production_csid) currentStep.value = 4;
};

const submitCsr = () => {
    csrForm.post('/app/settings/zatca/csr', {
        onSuccess: () => {
            currentStep.value = 2;
        },
    });
};

const submitOtp = () => {
    otpForm.post('/app/settings/zatca/compliance', {
        onSuccess: () => {
            currentStep.value = 3;
            runComplianceTests();
        },
    });
};

const runComplianceTests = async () => {
    runningChecks.value = true;
    checksStatus.value = null;
    checksMessage.value = '';

    try {
        const response = await fetch('/app/settings/zatca/check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content'),
            },
        });

        const result = await response.json();

        complianceChecks.value = result.checks ?? [];
        checksStatus.value = result.status ?? (response.ok ? 'success' : 'failed');
        checksMessage.value = result.message ?? '';

        if (!response.ok && !result.message) {
            checksStatus.value = 'failed';
            checksMessage.value = t('zatca.checks_run_error');
        }
    } catch (err) {
        checksStatus.value = 'failed';
        checksMessage.value = t('zatca.checks_connection_error');
    } finally {
        runningChecks.value = false;
    }
};

const submitProduction = () => {
    submittingProd.value = true;
    router.post(
        '/app/settings/zatca/production',
        {},
        {
            onSuccess: () => {
                currentStep.value = 4;
                submittingProd.value = false;
            },
            onError: () => {
                submittingProd.value = false;
            },
        }
    );
};

const confirmReset = () => {
    if (confirm(t('zatca.reset_confirm'))) {
        router.post(
            '/app/settings/zatca/reset',
            {},
            {
                onSuccess: () => {
                    currentStep.value = 1;
                },
            }
        );
    }
};
</script>
