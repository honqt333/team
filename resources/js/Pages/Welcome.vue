<script setup>
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const email = ref('');
const submitted = ref(false);
const loading = ref(false);
const error = ref('');
const langLoading = ref(false);

async function switchLang() {
    if (langLoading.value) return;
    langLoading.value = true;
    const current = document.documentElement.getAttribute('dir');
    const next = current === 'rtl' ? 'en' : 'ar';
    try {
        await axios.post(route('locale.set'), { locale: next });
        window.location.reload();
    } catch (e) {
        langLoading.value = false;
    }
}

function submitEmail() {
    if (!email.value || !email.value.includes('@')) {
        error.value = true;
        return;
    }
    loading.value = true;
    error.value = false;
    setTimeout(() => {
        loading.value = false;
        submitted.value = true;
    }, 800);
}
</script>

<template>

    <Head :title="$t('common.app_name') + ' — ' + $t('teaser.coming_soon')" />

    <div class="min-h-screen bg-[#080810] text-white overflow-x-hidden selection:bg-amber-500/30"
        :dir="$page.props.locale === 'ar' ? 'rtl' : 'ltr'"
        :style="$page.props.locale === 'ar' ? 'font-family:Cairo,sans-serif' : 'font-family:Inter,sans-serif'">
        <!-- ===== BACKGROUND ===== -->
        <div class="fixed inset-0 pointer-events-none">
            <!-- Gradient mesh -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#0d0820] via-[#080810] to-[#0a1020]"></div>
            <!-- Orbs -->
            <div class="absolute -top-32 left-1/3 w-[600px] h-[600px] bg-amber-500/8 rounded-full blur-[140px]"
                style="animation:pulse 6s ease-in-out infinite"></div>
            <div class="absolute top-1/2 -right-40 w-[500px] h-[500px] bg-blue-600/8 rounded-full blur-[140px]"
                style="animation:pulse 8s ease-in-out infinite;animation-delay:2s"></div>
            <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-purple-600/6 rounded-full blur-[120px]"
                style="animation:pulse 7s ease-in-out infinite;animation-delay:4s"></div>
            <!-- Dot grid -->
            <div class="absolute inset-0 opacity-[0.025]"
                style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:32px 32px">
            </div>
            <!-- Glow line -->
            <div
                class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-500/40 to-transparent">
            </div>
        </div>

        <div class="relative z-10">
            <!-- ===== HEADER ===== -->
            <header class="container mx-auto px-6 py-6 flex justify-between items-center max-w-7xl">
                <!-- Logo -->
                <div class="flex items-center gap-3 group">
                    <div class="relative">
                        <div class="absolute inset-0 bg-amber-500/30 rounded-2xl blur-lg group-hover:blur-xl transition-all duration-300"></div>
                        <div class="relative w-12 h-12 rounded-2xl overflow-hidden shadow-xl border border-white/10">
                            <img src="/images/logo.png" alt="Khidmh Pro Logo" class="w-full h-full object-contain bg-white/5 p-1" />
                        </div>
                    </div>
                    <div>
                        <div class="text-xl font-black tracking-tight leading-none">
                            Khidmh <span class="text-amber-400">Pro</span>
                        </div>
                        <div class="text-[10px] text-gray-500 font-medium tracking-widest uppercase">Workshop OS</div>
                    </div>
                </div>

                <!-- Nav actions -->
                <div class="flex items-center gap-3">
                    <!-- Language switcher -->
                    <button @click="switchLang" :disabled="langLoading"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 hover:border-white/20 transition-all duration-200 text-sm font-semibold text-gray-300 hover:text-white disabled:opacity-50 disabled:cursor-wait">
                        <svg v-if="langLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        {{ $page.props.locale === 'ar' ? 'English' : 'العربية' }}
                    </button>
                    <!-- معلومات زر الدخول 
                    <template v-if="canLogin">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="flex items-center gap-2 px-5 py-2 rounded-xl bg-amber-500 text-black font-bold hover:bg-amber-400 transition-all duration-200 text-sm shadow-lg shadow-amber-500/25"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            {{ $t('teaser.back_to_login') }}
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="flex items-center gap-2 px-5 py-2 rounded-xl bg-white/8 border border-white/15 hover:bg-white/12 transition-all duration-200 text-sm font-semibold text-gray-200 hover:text-white"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            {{ $page.props.locale === 'ar' ? 'تسجيل الدخول' : 'Sign In' }}
                        </Link>
                    </template>
-->
                </div>
            </header>

            <!-- ===== HERO ===== -->
            <main class="container mx-auto px-6 max-w-7xl">

                <!-- Badge -->
                <div class="flex justify-center mt-16 mb-8">
                    <div
                        class="inline-flex items-center gap-2.5 px-5 py-2 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 text-sm font-bold backdrop-blur-sm">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400"></span>
                        </span>
                        {{ $t('teaser.coming_soon') }}
                        <span class="w-px h-3 bg-amber-500/40"></span>
                        <span class="text-amber-500/70 font-normal text-xs">2026</span>
                    </div>
                </div>

                <!-- Headline -->
                <div class="text-center max-w-5xl mx-auto mb-8">
                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-black leading-[1.08] tracking-tight mb-6">
                        <span class="block text-white">{{ $page.props.locale === 'ar' ? 'نظام إدارة' : 'Next-Gen'
                        }}</span>
                        <span
                            class="block bg-gradient-to-r from-amber-400 via-orange-400 to-amber-300 bg-clip-text text-transparent">
                            {{ $page.props.locale === 'ar' ? 'مراكز الصيانة' : 'Workshop OS' }}
                        </span>
                        <span class="block text-white/70 text-4xl md:text-5xl lg:text-6xl mt-1">
                            {{ $page.props.locale === 'ar' ? 'المستقبل قادم' : 'is Almost Here' }}
                        </span>
                    </h1>
                    <p class="text-lg md:text-xl text-gray-400 leading-relaxed max-w-2xl mx-auto">
                        {{ $t('teaser.subtitle') }}
                    </p>
                </div>

                <!-- Email Form -->
                <div class="flex justify-center mb-6">
                    <div class="w-full max-w-md">
                        <!-- Success state -->
                        <Transition name="fade">
                            <div v-if="submitted"
                                class="flex items-center gap-3 px-6 py-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-semibold text-sm">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $page.props.locale === 'ar' ? 'تم التسجيل! سنبلغك عند الإطلاق 🎉' : "You're on the list! We'll notify you at launch 🎉" }}
                            </div>
                        </Transition>

                        <!-- Form -->
                        <div v-if="!submitted" class="relative">
                            <div
                                class="absolute -inset-0.5 bg-gradient-to-r from-amber-500/30 to-orange-500/30 rounded-2xl blur-sm">
                            </div>
                            <div
                                class="relative flex gap-2 bg-white/5 p-2 rounded-2xl border border-white/15 backdrop-blur-md">
                                <div class="flex-1 relative">
                                    <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <input v-model="email" type="email" :placeholder="$t('teaser.placeholder')"
                                        :class="['w-full bg-transparent border-none outline-none focus:ring-0 py-3 text-white placeholder-gray-500 text-sm', $page.props.locale === 'ar' ? 'pr-10 pl-3' : 'pl-10 pr-3', error ? 'placeholder-red-400' : '']"
                                        @keyup.enter="submitEmail" />
                                </div>
                                <button @click="submitEmail" :disabled="loading"
                                    class="flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-black font-black hover:from-amber-400 hover:to-orange-400 transition-all duration-200 whitespace-nowrap text-sm shadow-lg shadow-amber-500/25 disabled:opacity-70">
                                    <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span v-else>{{ $t('teaser.notify_me') }}</span>
                                </button>
                            </div>
                            <p v-if="error" class="text-red-400 text-xs mt-2 text-center">{{ $page.props.locale === 'ar' ? 'يرجى إدخال بريد إلكتروني صحيح' : 'Please enter a valid email address' }}</p>
                        </div>

                        <p class="text-center text-gray-600 text-xs mt-3">{{ $page.props.locale === 'ar' ? 'لن نشارك بريدك مع أي طرف ثالث' : 'No spam, unsubscribe any time.' }}</p>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="flex justify-center gap-8 md:gap-16 mb-20 mt-4">
                    <div class="text-center">
                        <div class="text-2xl font-black text-amber-400">+500</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $page.props.locale === 'ar' ? 'عميل منتظر' : 'Waitlist' }}</div>
                    </div>
                    <div class="w-px bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-amber-400">30+</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $page.props.locale === 'ar' ? 'ميزة جديدة' : 'New Features' }}</div>
                    </div>
                    <div class="w-px bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-amber-400">2x</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $page.props.locale === 'ar' ? 'أسرع' : 'Faster' }}</div>
                    </div>
                </div>

                <!-- ===== DASHBOARD PREVIEW IMAGE ===== -->
                <div class="relative max-w-6xl mx-auto mb-28">
                    <!-- Glow behind image -->
                    <div class="absolute inset-x-10 -bottom-10 h-40 bg-amber-500/15 blur-3xl rounded-full"></div>
                    <div
                        class="absolute -inset-px bg-gradient-to-b from-amber-500/20 via-transparent to-transparent rounded-[2rem]">
                    </div>

                    <!-- Image frame -->
                    <div
                        class="relative rounded-[2rem] overflow-hidden border border-white/10 shadow-2xl shadow-black/60 bg-[#0d0d1a]">
                        <!-- Browser chrome bar -->
                        <div class="flex items-center gap-2 px-5 py-3 bg-white/5 border-b border-white/8">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-500/70"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/70"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-500/70"></div>
                            </div>
                            <div class="flex-1 mx-4">
                                <div
                                    class="flex items-center gap-2 px-3 py-1 rounded-lg bg-white/5 border border-white/8 max-w-sm mx-auto">
                                    <svg class="w-3 h-3 text-gray-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span class="text-gray-500 text-xs">app.khidmhpro.com/dashboard</span>
                                </div>
                            </div>
                        </div>
                        <!-- Dashboard screenshot -->
                        <img src="/images/logo.png" alt="Khidmh Pro Dashboard Preview" class="w-full h-auto block"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                        <!-- Fallback placeholder if image not found -->
                        <div
                            class="hidden h-64 md:h-96 items-center justify-center bg-gradient-to-br from-[#0d0d20] to-[#1a1030]">
                            <div class="text-center">
                                <div
                                    class="w-20 h-20 rounded-3xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-amber-500/50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm">Dashboard Preview Coming Soon</p>
                            </div>
                        </div>
                    </div>

                    <!-- Overlay fade at bottom of image -->
                    <div
                        class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-[#080810] to-transparent rounded-b-[2rem]">
                    </div>
                </div>

                <!-- ===== FEATURES GRID ===== -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 max-w-5xl mx-auto mb-24">

                    <!-- Card 1: Print -->
                    <div
                        class="relative p-7 rounded-3xl bg-gradient-to-br from-white/5 to-white/2 border border-white/10 hover:border-amber-500/30 transition-all duration-300 group overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-3xl">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 rounded-2xl bg-amber-500/12 border border-amber-500/20 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-amber-500/20 transition-all duration-300">
                                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold mb-2 text-white">{{ $t('teaser.features.print') }}</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">{{ $t('teaser.features.print_desc') }}</p>
                        </div>
                    </div>

                    <!-- Card 2: Finance -->
                    <div
                        class="relative p-7 rounded-3xl bg-gradient-to-br from-white/5 to-white/2 border border-white/10 hover:border-blue-500/30 transition-all duration-300 group overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-3xl">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 rounded-2xl bg-blue-500/12 border border-blue-500/20 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-blue-500/20 transition-all duration-300">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold mb-2 text-white">{{ $t('teaser.features.finance') }}</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">{{ $t('teaser.features.finance_desc') }}
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Speed -->
                    <div
                        class="relative p-7 rounded-3xl bg-gradient-to-br from-white/5 to-white/2 border border-white/10 hover:border-purple-500/30 transition-all duration-300 group overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-3xl">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 rounded-2xl bg-purple-500/12 border border-purple-500/20 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-purple-500/20 transition-all duration-300">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold mb-2 text-white">{{ $t('teaser.features.speed') }}</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">{{ $t('teaser.features.speed_desc') }}</p>
                        </div>
                    </div>
                </div>
            </main>

            <!-- ===== FOOTER ===== -->
            <footer class="border-t border-white/5 py-8">
                <div
                    class="container mx-auto px-6 max-w-7xl flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div
                            class="w-5 h-5 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span>&copy; 2026 Khidmh Pro. All rights reserved.</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        {{ $page.props.locale === 'ar' ? 'جاري التطوير' : 'Under Development' }}
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s, transform 0.4s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@keyframes pulse {

    0%,
    100% {
        opacity: 0.6;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.05);
    }
}
</style>
