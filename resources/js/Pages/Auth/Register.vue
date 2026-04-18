<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed, watch } from 'vue';

const { locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');
const page = usePage();

const form = useForm({
    company_name: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    promo_code: '',
    terms: false,
});

const otpForm = useForm({
    phone: '',
    otp: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const showTermsModal = ref(false);
const showPrivacyModal = ref(false);

// Phone verification state
const phoneVerificationEnabled = computed(() => page.props.phone_verification_enabled ?? false);
const codeSent = ref(false);
const verifiedPhone = ref(page.props.verified_phone ?? '');

// Phone is verified only if: session says verified AND current phone matches verified phone
const phoneVerified = computed(() => {
    if (!page.props.phone_verified) return false;
    if (!form.phone) return false;
    return formatPhone(form.phone) === verifiedPhone.value;
});

// Watch for phone changes to reset code sent state
watch(() => form.phone, (newPhone) => {
    if (verifiedPhone.value && formatPhone(newPhone) !== verifiedPhone.value) {
        codeSent.value = false;
    }
});

const formatPhone = (phone) => {
    phone = phone.replace(/[\s\-]/g, '');
    if (phone.startsWith('0')) {
        return '+966' + phone.substring(1);
    } else if (phone.startsWith('5')) {
        return '+966' + phone;
    } else if (phone.startsWith('966')) {
        return '+' + phone;
    } else if (!phone.startsWith('+')) {
        return '+' + phone;
    }
    return phone;
};

// Cooldown timer
const cooldownRemaining = ref(0);
let cooldownInterval = null;

const startCooldown = (seconds) => {
    cooldownRemaining.value = seconds;
    if (cooldownInterval) clearInterval(cooldownInterval);
    cooldownInterval = setInterval(() => {
        cooldownRemaining.value--;
        if (cooldownRemaining.value <= 0) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
    }, 1000);
};

const sendOtp = () => {
    otpForm.phone = form.phone;
    otpForm.post(route('phone.send-otp'), {
        preserveScroll: true,
        onSuccess: (page) => {
            codeSent.value = true;
            // Start 60-second cooldown
            startCooldown(60);
        },
    });
};

const verifyOtp = () => {
    otpForm.phone = form.phone;
    otpForm.post(route('phone.verify-otp'), {
        preserveScroll: true,
        onSuccess: () => {
            // Update verifiedPhone - phoneVerified is computed based on this
            verifiedPhone.value = formatPhone(form.phone);
            // Force page.props update to reflect verified state
            page.props.phone_verified = true;
            page.props.verified_phone = verifiedPhone.value;
            cooldownRemaining.value = 0;
            if (cooldownInterval) clearInterval(cooldownInterval);
        },
    });
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'إنشاء حساب' : 'Create Account'" />

        <!-- Title -->
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6 font-cairo">
            {{ isRTL ? 'إنشاء حساب' : 'Create Account' }}
        </h2>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Company Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ isRTL ? 'الاسم التجاري' : 'Business Name' }} <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    v-model="form.company_name"
                    required
                    autofocus
                    :placeholder="isRTL ? 'اسم الشركة أو المركز' : 'Company or Center Name'"
                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                    :class="form.errors.company_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                />
                <InputError class="mt-1" :message="form.errors.company_name" />
            </div>

            <!-- First & Last Name -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ isRTL ? 'الاسم الأول' : 'First Name' }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.first_name"
                        required
                        :placeholder="isRTL ? 'الاسم الأول' : 'First Name'"
                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                        :class="form.errors.first_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                    />
                    <InputError class="mt-1" :message="form.errors.first_name" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ isRTL ? 'اسم الأخير' : 'Last Name' }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.last_name"
                        required
                        :placeholder="isRTL ? 'اسم الأخير' : 'Last Name'"
                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                        :class="form.errors.last_name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                    />
                    <InputError class="mt-1" :message="form.errors.last_name" />
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ isRTL ? 'بريد العمل' : 'Work Email' }} <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="email"
                    placeholder="example@company.com"
                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                    :class="form.errors.email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Phone with OTP Verification -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ isRTL ? 'هاتف العمل' : 'Work Phone' }} <span class="text-red-500">*</span>
                    <span v-if="phoneVerified" class="text-green-500 text-xs ms-2">✓ {{ isRTL ? 'تم التحقق' : 'Verified' }}</span>
                </label>
                <div class="flex gap-2">
                    <div class="flex items-center gap-1 px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        <span class="text-lg">🇸🇦</span>
                        <span class="text-sm">+966</span>
                    </div>
                    <input
                        type="tel"
                        v-model="form.phone"
                        required
                        :disabled="phoneVerified"
                        placeholder="5xxxxxxxx"
                        class="flex-1 px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 disabled:opacity-50"
                        :class="form.errors.phone ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                    />
                    <!-- Send OTP Button - Only show if verification enabled and not verified -->
                    <button
                        v-if="phoneVerificationEnabled && !phoneVerified && !codeSent"
                        type="button"
                        @click="sendOtp"
                        :disabled="otpForm.processing || !form.phone"
                        class="px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-medium disabled:opacity-50"
                    >
                        {{ isRTL ? 'تحقق' : 'Verify' }}
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.phone || otpForm.errors.phone" />
                
                <!-- OTP Input (show after code sent) -->
                <div v-if="codeSent && !phoneVerified" class="mt-3 p-4 bg-violet-50 dark:bg-violet-900/20 rounded-xl border border-violet-200 dark:border-violet-800">
                    <p class="text-sm text-violet-700 dark:text-violet-300 mb-3">
                        {{ isRTL ? 'تم إرسال رمز التحقق إلى هاتفك' : 'Verification code sent to your phone' }}
                    </p>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            v-model="otpForm.otp"
                            maxlength="6"
                            :placeholder="isRTL ? 'أدخل الرمز المكون من 6 أرقام' : 'Enter 6-digit code'"
                            class="flex-1 px-4 py-2.5 border border-violet-300 dark:border-violet-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center tracking-widest font-mono"
                        />
                        <button
                            type="button"
                            @click="verifyOtp"
                            :disabled="otpForm.processing || otpForm.otp.length !== 6"
                            class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium disabled:opacity-50"
                        >
                            {{ isRTL ? 'تأكيد' : 'Confirm' }}
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="sendOtp"
                        :disabled="otpForm.processing || cooldownRemaining > 0"
                        class="mt-2 text-sm disabled:opacity-50"
                        :class="cooldownRemaining > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-violet-600 hover:underline'"
                    >
                        <span v-if="cooldownRemaining > 0">
                            {{ isRTL ? `إعادة الإرسال بعد ${cooldownRemaining} ثانية` : `Resend in ${cooldownRemaining}s` }}
                        </span>
                        <span v-else>
                            {{ isRTL ? 'إعادة إرسال الرمز' : 'Resend code' }}
                        </span>
                    </button>
                    <InputError class="mt-1" :message="otpForm.errors.otp" />
                </div>
            </div>

            <!-- Password -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ isRTL ? 'كلمة المرور' : 'Password' }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••"
                            class="w-full px-4 py-2.5 pe-10 border rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                            :class="form.errors.password ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                        />
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ isRTL ? 'تأكيد كلمة المرور' : 'Confirm Password' }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showConfirmPassword ? 'text' : 'password'"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••"
                            class="w-full px-4 py-2.5 pe-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                        />
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                            <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                    <InputError class="mt-1" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <!-- Promo Code -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ isRTL ? 'الرمز الترويجي' : 'Promo Code' }}
                </label>
                <input
                    type="text"
                    v-model="form.promo_code"
                    :placeholder="isRTL ? 'أدخل الرمز إن وجد' : 'Enter code if you have one'"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                />
                <InputError class="mt-1" :message="form.errors.promo_code" />
            </div>

            <!-- Terms Checkbox -->
            <div class="flex items-start gap-2">
                <input
                    type="checkbox"
                    id="terms"
                    v-model="form.terms"
                    required
                    class="mt-1 w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500"
                />
                <label for="terms" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ isRTL ? 'قرأت وأوافق على' : 'I have read and agree to the' }}
                    <button type="button" @click="showTermsModal = true" class="text-violet-600 hover:underline">{{ isRTL ? 'سياسة الاستخدام' : 'Terms of Service' }}</button>
                    {{ isRTL ? 'و' : 'and' }}
                    <button type="button" @click="showPrivacyModal = true" class="text-violet-600 hover:underline">{{ isRTL ? 'سياسة الخصوصية' : 'Privacy Policy' }}</button>
                </label>
            </div>
            <InputError class="mt-1" :message="form.errors.terms" />

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing || (phoneVerificationEnabled && !phoneVerified)"
                class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isRTL ? 'جاري الإرسال...' : 'Submitting...' }}
                </span>
                <span v-else-if="phoneVerificationEnabled && !phoneVerified">{{ isRTL ? 'يرجى التحقق من رقم الهاتف أولاً' : 'Please verify phone first' }}</span>
                <span v-else>{{ isRTL ? 'إرسال' : 'Submit' }}</span>
            </button>

            <!-- Links -->
            <div class="flex items-center justify-between text-sm pt-2">
                <Link
                    :href="route('login')"
                    class="font-medium text-violet-600 hover:text-violet-500 dark:text-violet-400"
                >
                    {{ isRTL ? 'تسجيل الدخول' : 'Sign In' }}
                </Link>
                <Link
                    :href="route('password.request')"
                    class="font-medium text-gray-600 hover:text-gray-500 dark:text-gray-400"
                >
                    {{ isRTL ? 'هل نسيت كلمة المرور؟' : 'Forgot Password?' }}
                </Link>
            </div>
        </form>

        <!-- Terms Modal -->
        <Teleport to="body">
            <div v-if="showTermsModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showTermsModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">{{ isRTL ? 'سياسة الاستخدام' : 'Terms of Service' }}</h3>
                            <button @click="showTermsModal = false" class="text-white/80 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto max-h-[60vh] text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                            <p class="mb-4">{{ isRTL ? 'مرحباً بك في خدمة برو. باستخدامك لهذه الخدمة، فإنك توافق على الالتزام بهذه الشروط والأحكام.' : 'Welcome to Khidmh Pro. By using this service, you agree to be bound by these terms and conditions.' }}</p>
                            <p class="mb-4">{{ isRTL ? 'يرجى قراءة هذه الشروط بعناية قبل استخدام النظام. إذا كنت لا توافق على جميع الشروط، فلا يجوز لك استخدام الخدمة.' : 'Please read these terms carefully before using the system. If you do not agree to all terms, you may not use the service.' }}</p>
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ isRTL ? '(سيتم تحديث هذا المحتوى من لوحة تحكم النظام)' : '(This content will be updated from the system admin panel)' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Privacy Modal -->
        <Teleport to="body">
            <div v-if="showPrivacyModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showPrivacyModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">{{ isRTL ? 'سياسة الخصوصية' : 'Privacy Policy' }}</h3>
                            <button @click="showPrivacyModal = false" class="text-white/80 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto max-h-[60vh] text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                            <h4 class="font-bold mb-2">{{ isRTL ? 'سياسة الخصوصية' : 'Privacy Policy' }}</h4>
                            <p class="mb-4">{{ isRTL ? 'نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. توضح هذه السياسة كيفية جمع واستخدام وحماية معلوماتك.' : 'We respect your privacy and are committed to protecting your personal data. This policy explains how we collect, use, and protect your information.' }}</p>
                            <p class="mb-4">{{ isRTL ? 'نستخدم بياناتك لتقديم الخدمة وتحسينها. لن نشارك معلوماتك مع أطراف ثالثة دون موافقتك.' : 'We use your data to provide and improve the service. We will not share your information with third parties without your consent.' }}</p>
                            <p class="text-gray-500 dark:text-gray-400 italic">{{ isRTL ? '(سيتم تحديث هذا المحتوى من لوحة تحكم النظام)' : '(This content will be updated from the system admin panel)' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthLayout>
</template>

<style scoped>
.font-cairo {
    font-family: 'Cairo', system-ui, sans-serif;
}
</style>
