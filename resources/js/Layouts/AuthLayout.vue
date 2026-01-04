<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');
</script>

<template>
    <div class="min-h-screen flex" :dir="isRTL ? 'rtl' : 'ltr'">
        <!-- Form Side -->
        <div class="w-full lg:w-1/2 flex flex-col">
            <!-- Mobile Header (Logo) -->
            <div class="lg:hidden bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-xl mb-4">
                    <img src="/images/logo.png" alt="Khidma Pro" class="w-12 h-12 object-contain">
                </div>
                <h1 class="text-2xl font-bold text-white font-cairo">
                    {{ isRTL ? 'خدمة برو' : 'Khidma Pro' }}
                </h1>
                <p class="text-violet-200 text-sm mt-1">
                    {{ isRTL ? 'نظام إدارة مراكز الخدمة' : 'Service Center Management' }}
                </p>
            </div>
            
            <!-- Form Content -->
            <div class="flex-1 flex items-center justify-center p-6 sm:p-12 bg-white dark:bg-gray-900">
                <div class="w-full max-w-md relative">
                    <!-- Language Toggle -->
                    <div class="absolute -top-8 left-0">
                        <button 
                            @click="locale = locale === 'ar' ? 'en' : 'ar'"
                            class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                        >
                            {{ locale === 'ar' ? 'English' : 'العربية' }}
                        </button>
                    </div>
                    
                    <slot />
                </div>
            </div>
        </div>
        
        <!-- Branding Side (Desktop Only) -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 items-center justify-center p-12 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full translate-x-1/3 translate-y-1/3"></div>
            </div>
            
            <!-- Logo & Text -->
            <div class="relative z-10 text-center">
                <div class="inline-flex items-center justify-center w-32 h-32 bg-white rounded-full shadow-2xl mb-8">
                    <img src="/images/logo.png" alt="Khidma Pro" class="w-20 h-20 object-contain">
                </div>
                <h1 class="text-4xl font-bold text-white font-cairo mb-4">
                    {{ isRTL ? 'خدمة برو' : 'Khidma Pro' }}
                </h1>
                <p class="text-xl text-violet-200">
                    {{ isRTL ? 'نظام إدارة مراكز الخدمة السحابي' : 'Cloud Service Center Management' }}
                </p>
                
                <!-- Features List -->
                <div class="mt-12 space-y-4" :class="isRTL ? 'text-right' : 'text-left'">
                    <div class="flex items-center gap-3 text-white/90">
                        <svg class="w-6 h-6 text-violet-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ isRTL ? 'إدارة أوامر العمل بسهولة' : 'Easy Work Order Management' }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-white/90">
                        <svg class="w-6 h-6 text-violet-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ isRTL ? 'تتبع المخزون والقطع' : 'Inventory & Parts Tracking' }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-white/90">
                        <svg class="w-6 h-6 text-violet-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ isRTL ? 'تقارير مالية متكاملة' : 'Integrated Financial Reports' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-cairo {
    font-family: 'Cairo', system-ui, sans-serif;
}
</style>
