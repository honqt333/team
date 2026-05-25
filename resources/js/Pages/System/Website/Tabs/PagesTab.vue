<script setup>
defineProps({ form: Object, addItem: Function, removeItem: Function });
</script>

<template>
  <div class="space-y-10">
    <div>
      <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">الوثائق القانونية</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">الشروط والأحكام <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_page_terms_ar" rows="6" class="input-enterprise"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Terms & Conditions <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_page_terms_en" rows="6" class="input-enterprise text-left" dir="ltr"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">سياسة الخصوصية <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_page_privacy_ar" rows="6" class="input-enterprise"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Privacy Policy <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_page_privacy_en" rows="6" class="input-enterprise text-left" dir="ltr"></textarea></div>
      </div>
    </div>

    <div class="pt-8 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إنشاء صفحات فرعية</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">صفحات ثابتة إضافية مثل "نبذة عنا" أو "سياسة الاستبدال"</p>
        </div>
        <button @click="addItem('website_custom_pages', { title_ar: '', title_en: '', slug: '', content_ar: '', content_en: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة صفحة جديدة</button>
      </div>
      <div class="space-y-6">
        <div v-for="(page, index) in form.settings.website_custom_pages" :key="index" class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/30 relative group">
          <button @click="removeItem('website_custom_pages', index)" class="absolute top-4 left-4 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">رابط الصفحة (Slug)</label>
              <div class="flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 sm:text-sm" dir="ltr">/page/</span>
                <input v-model="page.slug" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-l-md sm:text-sm border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700 dark:text-white text-left font-mono" dir="ltr" placeholder="about-us" />
              </div>
            </div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">عنوان الصفحة <span class="badge-lang">AR</span></label><input v-model="page.title_ar" class="input-enterprise" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Page Title <span class="badge-lang">EN</span></label><input v-model="page.title_en" class="input-enterprise text-left" dir="ltr" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">المحتوى <span class="badge-lang">AR</span></label><textarea v-model="page.content_ar" rows="5" class="input-enterprise"></textarea></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Content <span class="badge-lang">EN</span></label><textarea v-model="page.content_en" rows="5" class="input-enterprise text-left" dir="ltr"></textarea></div>
          </div>
        </div>
        <div v-if="!form.settings.website_custom_pages.length" class="text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400">لا توجد صفحات فرعية</div>
      </div>
    </div>
  </div>
</template>
