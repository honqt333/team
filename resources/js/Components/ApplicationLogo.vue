<template>
    <div class="flex items-center gap-3">
        <img src="/images/logo.png" alt="Khidma Pro" class="w-10 h-10 object-contain">
        <div class="flex flex-col">
            <span class="font-bold text-xl leading-none text-gray-900 dark:text-white font-cairo">
                {{ $i18n.locale === 'ar' ? 'خدمة برو' : 'Khidma Pro' }}
            </span>
            <span class="text-[0.65rem] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-medium">
                {{ $i18n.locale === 'ar' ? 'حلول الخدمات' : 'Service Solutions' }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.font-cairo {
    font-family: 'Cairo', system-ui, sans-serif;
}
</style>
