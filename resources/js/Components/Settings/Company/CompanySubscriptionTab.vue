<template>
    <div class="space-y-8">
        <!-- Section 1: Subscription Details & Available Plans -->
        <div
            v-if="!activeTab || activeTab === 'subscription' || activeTab === 'all'"
            class="space-y-8"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/50 pb-4"
            >
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('company_profile.tabs.subscription') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        عرض تفاصيل اشتراكك الحالي والباقات المتاحة للترقية
                    </p>
                </div>
            </div>

            <!-- Top Notice Banner for Pending Receipt -->
            <div
                v-if="isReceiptUnderReview"
                class="bg-blue-50/90 dark:bg-blue-950/50 border-2 border-blue-200 dark:border-blue-800 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden"
            >
                <div class="flex items-start sm:items-center gap-3.5 relative z-10">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md animate-pulse"
                    >
                        📋
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-black text-sm text-blue-900 dark:text-blue-100">
                                إيصال التحويل البنكي قيد المراجعة من الإدارة
                            </h4>
                            <span
                                class="px-2 py-0.5 text-[10px] font-extrabold bg-blue-600 text-white rounded-full"
                            >
                                قيد التدقيق
                            </span>
                        </div>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1 leading-relaxed">
                            تم استلام إيصال التحويل لباقة
                            <strong class="font-black text-blue-900 dark:text-blue-100">
                                {{ getPlanName(currentSubscription) }}
                            </strong>
                            . تم إيقاف طلبات الاشتراك الجديدة مؤقتاً لحين انتهاء الفحص وتفعيل حسابك.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span
                        class="px-3 py-1.5 text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 rounded-xl border border-blue-300/60 dark:border-blue-700"
                    >
                        ⏳ قيد التدقيق والاعتماد
                    </span>
                </div>
            </div>

            <!-- Top Notice Banner for Pending Online Payment -->
            <div
                v-else-if="currentSubscription?.status === 'pending'"
                class="bg-amber-50/90 dark:bg-amber-950/50 border-2 border-amber-200 dark:border-amber-800 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md"
                    >
                        ⏳
                    </div>
                    <div>
                        <h4 class="font-black text-sm text-amber-900 dark:text-amber-100">
                            طلب دفع إلكتروني غير مكتمل
                        </h4>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            لديك طلب دفع غير مكتمل لباقة
                            <strong>{{ getPlanName(currentSubscription) }}</strong>
                            . يمكنك إكمال الدفع أو اختيار باقة أخرى.
                        </p>
                    </div>
                </div>
                <button
                    @click="openSubscribeModal(currentSubscription?.plan || availablePlans[0])"
                    type="button"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shadow-md transition-colors shrink-0 cursor-pointer"
                >
                    إكمال الدفع الآن
                </button>
            </div>

            <!-- Current Subscription Info Card -->
            <div
                class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/20 dark:to-purple-950/20 p-6 rounded-2xl border border-indigo-100/50 dark:border-indigo-900/30 shadow-sm relative overflow-hidden"
            >
                <div
                    class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-200/20 dark:bg-indigo-900/10 rounded-full blur-2xl"
                ></div>

                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10"
                >
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span
                                class="text-xs uppercase tracking-wider font-semibold text-indigo-600 dark:text-indigo-400"
                            >
                                الاشتراك الحالي
                            </span>

                            <!-- Active Status -->
                            <span
                                v-if="currentSubscription?.status === 'active'"
                                class="px-3 py-1 text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 rounded-full font-bold border border-emerald-300/50 flex items-center gap-1.5"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                نشط
                            </span>

                            <!-- Bank Transfer Receipt Pending Review -->
                            <span
                                v-else-if="isReceiptUnderReview"
                                class="px-3 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 rounded-full font-bold border border-blue-300/50 flex items-center gap-1.5"
                            >
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                📋 الإيصال قيد المراجعة
                            </span>

                            <!-- Pending Online Payment -->
                            <span
                                v-else-if="currentSubscription?.status === 'pending'"
                                class="px-3 py-1 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-full font-bold border border-amber-300/50 flex items-center gap-1.5"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"
                                ></span>
                                ⏳ طلب معلق
                            </span>

                            <!-- Trial Status -->
                            <span
                                v-else-if="
                                    currentSubscription?.status === 'trialing' ||
                                    currentSubscription?.status === 'trial'
                                "
                                class="px-3 py-1 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-full font-bold border border-amber-300/50 flex items-center gap-1.5"
                            >
                                🎁 فترة تجريبية
                            </span>

                            <!-- Expired Status -->
                            <span
                                v-else
                                class="px-3 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full font-bold border border-gray-300/50"
                            >
                                منتهي
                            </span>

                            <!-- Remaining Days Counter Badge -->
                            <span
                                v-if="daysRemainingText && currentSubscription?.status === 'active'"
                                :class="[
                                    'px-2.5 py-0.5 text-xs rounded-full font-semibold',
                                    daysRemaining > 5
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                        : daysRemaining > 0
                                          ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                          : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                ]"
                            >
                                ⏳ {{ daysRemainingText }}
                            </span>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{
                                currentSubscription
                                    ? getPlanName(currentSubscription)
                                    : 'لا يوجد اشتراك نشط'
                            }}
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span v-if="currentSubscription?.ends_at">
                                ينتهي في: {{ formatDate(currentSubscription.ends_at) }}
                            </span>
                            <span v-else-if="isReceiptUnderReview">
                                جاري التحقق من إيصال التحويل البنكي وتفعيل الاشتراك...
                            </span>
                            <span v-else>لا يوجد تاريخ انتهاء محدد</span>
                        </p>
                    </div>

                    <div class="text-start md:text-end space-y-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">
                            قيمة الاشتراك
                        </span>
                        <span class="text-3xl font-black text-gray-900 dark:text-white">
                            {{
                                currentSubscription
                                    ? formatCurrency(currentSubscription.price || 0)
                                    : formatCurrency(0)
                            }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">
                            /
                            {{ currentSubscription?.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Available Subscriptions -->
            <div class="space-y-6">
                <div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">
                        الاشتراكات المتاحة
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        اختر الباقة المناسبة لاحتياجات مركزك واشترك مباشرة بكل سهولة
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="plan in availablePlans"
                        :key="plan.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 flex flex-col justify-between hover:shadow-lg transition-all duration-300 relative group"
                        :class="{
                            'ring-2 ring-indigo-600 dark:ring-indigo-400':
                                currentSubscription?.plan_id === plan.id &&
                                currentSubscription?.status === 'active',
                        }"
                    >
                        <div
                            v-if="
                                currentSubscription?.plan_id === plan.id &&
                                currentSubscription?.status === 'active'
                            "
                            class="absolute -top-3 right-6 bg-indigo-600 text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow"
                        >
                            باقتك الحالية
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h5 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ plan.name_ar }}
                                </h5>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ plan.name_en }}
                                </p>
                            </div>

                            <div class="py-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-black text-gray-900 dark:text-white">
                                        {{ formatCurrency(plan.price_monthly) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        / شهري
                                    </span>
                                </div>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <span
                                        class="text-sm font-semibold text-gray-500 dark:text-gray-400"
                                    >
                                        {{ formatCurrency(plan.price_yearly) }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">/ سنوي</span>
                                </div>
                            </div>

                            <!-- Features -->
                            <div
                                class="space-y-2 pt-4 border-t border-gray-100 dark:border-gray-700/50"
                            >
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    ميزات الباقة:
                                </p>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(feat, fIdx) in getFeatures(plan)"
                                        :key="fIdx"
                                        class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        <svg
                                            class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5"
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
                                        <span>{{ feat }}</span>
                                    </li>
                                    <li
                                        v-if="!getFeatures(plan).length"
                                        class="text-xs text-gray-400 italic"
                                    >
                                        لا توجد ميزات مسجلة
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="pt-6 mt-6 border-t border-gray-100 dark:border-gray-700/50">
                            <!-- Disabled during bank transfer receipt review -->
                            <button
                                v-if="isReceiptUnderReview"
                                disabled
                                type="button"
                                class="w-full py-2.5 px-4 rounded-xl text-center text-xs font-bold bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 cursor-not-allowed flex items-center justify-center gap-1.5 opacity-90 shadow-sm"
                            >
                                <span>📋</span>
                                {{
                                    locale === 'ar'
                                        ? 'الإيصال قيد المراجعة'
                                        : 'Receipt Under Review'
                                }}
                            </button>

                            <!-- Disabled if this plan is the current active plan -->
                            <button
                                v-else-if="
                                    currentSubscription?.plan_id === plan.id &&
                                    currentSubscription?.status === 'active'
                                "
                                disabled
                                type="button"
                                class="w-full py-2.5 px-4 rounded-xl text-center text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed"
                            >
                                {{ locale === 'ar' ? 'باقتك الحالية' : 'Your current plan' }}
                            </button>

                            <!-- Enabled Subscribe / Upgrade button -->
                            <button
                                v-else
                                @click="openSubscribeModal(plan)"
                                type="button"
                                class="block w-full py-2.5 px-4 rounded-xl text-center text-xs font-bold transition-all duration-300 bg-indigo-600 hover:bg-indigo-700 text-white shadow-md hover:shadow-lg cursor-pointer"
                            >
                                {{
                                    currentSubscription?.status === 'active'
                                        ? locale === 'ar'
                                            ? 'ترقية الباقة'
                                            : 'Upgrade Plan'
                                        : locale === 'ar'
                                          ? 'اشترك الآن'
                                          : 'Subscribe now'
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Support Notice Card -->
                <div
                    class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row items-center justify-between gap-4 mt-8"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-gray-900 dark:text-white">
                                هل تحتاج إلى مساعدة في الترقية أو اختيار الباقة؟
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                فريق الدعم الفني جاهز لمساعدتك في أي وقت وتفعيل الاشتراك المناسب
                                لمركزك
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a
                            :href="`tel:${contact?.phone || '+966599999999'}`"
                            class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 transition-colors flex items-center gap-1.5 shadow-sm"
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
                                    stroke-width="1.5"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                />
                            </svg>
                            اتصل بنا
                        </a>
                        <a
                            :href="`mailto:${contact?.email || 'support@example.com'}`"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-xs font-bold text-white transition-colors flex items-center gap-1.5 shadow-md"
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
                                    stroke-width="1.5"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            راسلنا
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Subscription History Table -->
        <div
            v-if="!activeTab || activeTab === 'subscription_history' || activeTab === 'all'"
            class="space-y-6"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/50 pb-4"
            >
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $t('company_profile.tabs.subscription_history') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        سجل الاشتراكات وتواريخ التجديد والمدفوعات الخاصة بالمؤسسة
                    </p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-start">
                        <thead
                            class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-4 text-center">#</th>
                                <th class="px-6 py-4 text-start">تاريخ الطلب</th>
                                <th class="px-6 py-4 text-start">الباقة والحالة</th>
                                <th class="px-6 py-4 text-start">قيمة الباقة</th>
                                <th class="px-6 py-4 text-start">تاريخ البداية</th>
                                <th class="px-6 py-4 text-start">تاريخ الإنتهاء</th>
                                <th class="px-6 py-4 text-end">مبلغ الخصم</th>
                                <th class="px-6 py-4 text-end">المبلغ الصافي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="(sub, index) in subscriptions"
                                :key="sub.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 text-gray-900 dark:text-gray-100"
                            >
                                <td class="px-6 py-4 text-center font-medium">{{ index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                    {{ formatDate(sub.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold">{{ getPlanName(sub) }}</span>

                                        <!-- Status Badges in Table -->
                                        <span
                                            v-if="sub.status === 'active'"
                                            class="px-2.5 py-0.5 text-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 rounded-full font-bold"
                                        >
                                            نشط
                                        </span>
                                        <span
                                            v-else-if="
                                                sub.status === 'pending_payment' ||
                                                (sub.status === 'pending' && sub.receipts?.length)
                                            "
                                            class="px-2.5 py-0.5 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 rounded-full font-bold"
                                        >
                                            📋 الإيصال قيد المراجعة
                                        </span>
                                        <span
                                            v-else-if="sub.status === 'pending'"
                                            class="px-2.5 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-full font-bold"
                                        >
                                            ⏳ طلب معلق
                                        </span>
                                        <span
                                            v-else-if="
                                                sub.status === 'trialing' || sub.status === 'trial'
                                            "
                                            class="px-2.5 py-0.5 text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-full font-bold"
                                        >
                                            تجريبي
                                        </span>
                                        <span
                                            v-else-if="sub.status === 'cancelled'"
                                            class="px-2.5 py-0.5 text-xs bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 rounded-full font-bold"
                                        >
                                            ملغي
                                        </span>
                                        <span
                                            v-else
                                            class="px-2.5 py-0.5 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 rounded-full font-medium"
                                        >
                                            منتهي
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                    {{ formatCurrency(sub.price) }} /
                                    {{ sub.billing_cycle === 'yearly' ? 'سنة' : 'شهر' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                    {{ formatDate(sub.starts_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                                    {{ formatDate(sub.ends_at) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-end font-mono text-xs text-red-600 dark:text-red-400"
                                >
                                    {{ formatCurrency(sub.discount_amount || 0) }}
                                </td>
                                <td class="px-6 py-4 text-end font-mono text-xs font-bold">
                                    {{
                                        formatCurrency(
                                            (sub.price || 0) - (sub.discount_amount || 0)
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr v-if="!subscriptions || !subscriptions.length">
                                <td
                                    colspan="8"
                                    class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                                >
                                    لا توجد اشتراكات مسجلة حالياً
                                </td>
                            </tr>
                        </tbody>
                        <tfoot
                            v-if="subscriptions && subscriptions.length"
                            class="bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 font-bold"
                        >
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-4 text-start text-gray-900 dark:text-white"
                                >
                                    المجموع الإجمالي للمدفوعات
                                </td>
                                <td
                                    class="px-6 py-4 text-end font-mono text-xs text-red-600 dark:text-red-400"
                                >
                                    {{ formatCurrency(totalDiscount) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-end font-mono text-xs text-emerald-600 dark:text-emerald-400 font-black"
                                >
                                    {{ formatCurrency(totalNet) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Interactive Subscribe Checkout Modal -->
        <SubscribeModal
            :open="showSubscribeModal"
            :plan="selectedPlan"
            :enabled-gateways="enabledGateways"
            :payment-settings="paymentSettings"
            :bank-accounts="bankAccounts"
            @close="showSubscribeModal = false"
            @success="showSubscribeModal = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SubscribeModal from '@/Components/Subscription/SubscribeModal.vue';

const props = defineProps({
    activeTab: String,
    currentSubscription: Object,
    availablePlans: Array,
    subscriptions: Array,
    contact: Object,
    locale: String,
    currency: { type: String, default: 'SAR' },
    enabledGateways: { type: Array, default: () => [] },
    paymentSettings: { type: Object, default: () => ({}) },
    bankAccounts: { type: Array, default: () => [] },
});

const showSubscribeModal = ref(false);
const selectedPlan = ref(null);

const openSubscribeModal = (plan) => {
    if (isReceiptUnderReview.value) return;
    selectedPlan.value = plan;
    showSubscribeModal.value = true;
};

const isReceiptUnderReview = computed(() => {
    if (!props.currentSubscription) return false;
    if (props.currentSubscription.status === 'pending_payment') return true;
    if (
        props.currentSubscription.status === 'pending' &&
        props.currentSubscription.receipts?.length > 0
    )
        return true;
    return false;
});

const formatCurrency = (value) => {
    const num = parseFloat(value);
    const curr = props.currency || 'SAR';
    if (isNaN(num)) return `0.00 ${curr}`;
    return (
        new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ` ${curr}`
    );
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getPlanName = (sub) => {
    const name =
        props.locale === 'en'
            ? sub.plan?.name_en || sub.plan?.name_ar
            : sub.plan?.name_ar || sub.plan?.name_en;
    if (!name)
        return sub.status === 'trialing' || sub.status === 'trial' ? 'فترة تجريبية' : 'باقة مجهولة';
    return name;
};

const getFeatures = (plan) => {
    let feat =
        props.locale === 'en'
            ? plan.features_en || plan.features_ar || []
            : plan.features_ar || plan.features_en || [];
    if (typeof feat === 'string') {
        try {
            feat = JSON.parse(feat);
        } catch (e) {
            feat = [feat];
        }
    }
    return Array.isArray(feat) ? feat : [];
};

const daysRemaining = computed(() => {
    if (!props.currentSubscription?.ends_at) return null;
    const end = new Date(props.currentSubscription.ends_at);
    const now = new Date();
    const diffMs = end - now;
    return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
});

const daysRemainingText = computed(() => {
    if (daysRemaining.value === null) return null;
    if (daysRemaining.value > 0) return `متبقي ${daysRemaining.value} يوم`;
    if (daysRemaining.value === 0) return 'ينتهي اليوم';
    return `منتهي منذ ${Math.abs(daysRemaining.value)} يوم`;
});

const totalDiscount = computed(() => {
    if (!props.subscriptions || !props.subscriptions.length) return 0;
    return props.subscriptions.reduce((sum, s) => sum + (parseFloat(s.discount_amount) || 0), 0);
});

const totalNet = computed(() => {
    if (!props.subscriptions || !props.subscriptions.length) return 0;
    return props.subscriptions.reduce((sum, s) => {
        const price = parseFloat(s.price) || 0;
        const discount = parseFloat(s.discount_amount) || 0;
        return sum + (price - discount);
    }, 0);
});
</script>
