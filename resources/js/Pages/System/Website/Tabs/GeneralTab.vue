<script setup>
import { ref } from 'vue';

defineProps({ form: Object });

const logoInput = ref(null);
const faviconInput = ref(null);
</script>

<template>
  <div>
    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-6">الهوية البصرية والعلامة التجارية</h3>
    <div class="grid grid-cols-1 gap-y-8 gap-x-6 sm:grid-cols-6">

      <div class="sm:col-span-6 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">شعار المنصة الرئيسي</label>
        <div class="flex flex-col sm:flex-row gap-6 items-start">
          <div class="w-40 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900">
            <img v-if="form.settings.website_logo" :src="typeof form.settings.website_logo === 'string' ? form.settings.website_logo : URL.createObjectURL(form.settings.website_logo)" class="max-h-full object-contain" />
            <svg v-else class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="space-y-3 flex-1 w-full">
            <input type="file" @input="form.settings.website_logo = $event.target.files[0]" class="hidden" ref="logoInput" accept="image/*" />
            <button @click="logoInput.click()" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">تصفح الملفات</button>
            <p class="text-xs text-gray-500">مستحسن: PNG بخلفية شفافة (أقصى حجم 2MB)</p>
            <input v-model="form.settings.website_logo" type="text" placeholder="رابط URL الشعار (بديل)" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 bg-white dark:bg-gray-700 dark:text-white" dir="ltr" />
          </div>
        </div>
      </div>

      <div class="sm:col-span-3">
        <label class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          <span>اسم الموقع</span> <span class="badge-lang">AR</span>
        </label>
        <input v-model="form.settings.website_title_ar" type="text" class="input-enterprise" />
      </div>
      <div class="sm:col-span-3">
        <label class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" dir="ltr">
          <span>Site Title</span> <span class="badge-lang">EN</span>
        </label>
        <input v-model="form.settings.website_title_en" type="text" class="input-enterprise text-left" dir="ltr" />
      </div>

      <div class="sm:col-span-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">أيقونة المتصفح (Favicon)</label>
        <div class="flex gap-3">
          <div class="w-10 h-10 border border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900">
            <img v-if="form.settings.website_favicon" :src="typeof form.settings.website_favicon === 'string' ? form.settings.website_favicon : URL.createObjectURL(form.settings.website_favicon)" class="w-full h-full object-contain" />
          </div>
          <input type="file" @input="form.settings.website_favicon = $event.target.files[0]" class="hidden" ref="faviconInput" accept="image/*" />
          <button @click="faviconInput.click()" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 flex-1 transition-colors">تحديث الأيقونة</button>
        </div>
      </div>

      <div class="sm:col-span-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">لون العلامة التجارية</label>
        <div class="flex gap-2">
          <input v-model="form.settings.website_primary_color" type="color" class="h-10 w-12 p-0.5 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer bg-white" />
          <input v-model="form.settings.website_primary_color" type="text" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 bg-white dark:bg-gray-700 dark:text-white font-mono" dir="ltr" />
        </div>
      </div>

    </div>
  </div>
</template>
