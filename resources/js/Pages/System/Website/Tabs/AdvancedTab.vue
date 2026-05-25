<script setup>
defineProps({ form: Object, tab: String });
</script>

<template>
  <div class="space-y-10">

    <!-- Maintenance Mode -->
    <div v-if="tab === 'maintenance'">
      <div class="flex items-start justify-between border-b border-gray-200 dark:border-gray-700 pb-6 mb-8">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">حالة النظام (وضعية الصيانة)</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">منع الوصول إلى الموقع العام وعرض شاشة الصيانة المخصصة.</p>
        </div>
        <div class="flex items-center">
          <button @click="form.settings.website_maintenance_mode = form.settings.website_maintenance_mode == '1' ? '0' : '1'" type="button" :class="[form.settings.website_maintenance_mode == '1' ? 'bg-red-600' : 'bg-gray-200 dark:bg-gray-700', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']">
            <span class="sr-only">Toggle Maintenance</span>
            <span aria-hidden="true" :class="[form.settings.website_maintenance_mode == '1' ? '-translate-x-5 rtl:translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"></span>
          </button>
        </div>
      </div>
      <div :class="{'opacity-50 pointer-events-none': form.settings.website_maintenance_mode != '1'}" class="grid grid-cols-1 sm:grid-cols-2 gap-6 transition-opacity">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">عنوان رسالة الصيانة <span class="badge-lang">AR</span></label><input v-model="form.settings.website_maintenance_title_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Maintenance Title <span class="badge-lang">EN</span></label><input v-model="form.settings.website_maintenance_title_en" class="input-enterprise text-left" dir="ltr" /></div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">الرسالة التوضيحية <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_maintenance_message_ar" rows="3" class="input-enterprise"></textarea></div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Message <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_maintenance_message_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
      </div>
    </div>

    <!-- Footer Info -->
    <div v-if="tab === 'footer'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">معلومات التذييل</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">نبذة قصيرة (About) <span class="badge-lang">AR</span></label><textarea v-model="form.settings.website_footer_about_ar" rows="3" class="input-enterprise"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Short About <span class="badge-lang">EN</span></label><textarea v-model="form.settings.website_footer_about_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">حقوق النشر <span class="badge-lang">AR</span></label><input v-model="form.settings.website_copyright_text_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Copyrights <span class="badge-lang">EN</span></label><input v-model="form.settings.website_copyright_text_en" class="input-enterprise text-left" dir="ltr" /></div>
      </div>
    </div>

    <!-- Announcement Bar -->
    <div v-if="tab === 'announcement'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">شريط الإعلانات العلوي</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">شريط يظهر في أعلى الموقع للإعلانات والعروض</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.website_announcement_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div :class="{'opacity-50 pointer-events-none': form.settings.website_announcement_enabled != '1'}" class="space-y-6 mt-6 transition-opacity">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">نص الإعلان <span class="badge-lang">AR</span></label><input v-model="form.settings.website_announcement_text_ar" class="input-enterprise" placeholder="مثال: خصم 20% لفترة محدودة!" /></div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Announcement Text <span class="badge-lang">EN</span></label><input v-model="form.settings.website_announcement_text_en" class="input-enterprise text-left" dir="ltr" placeholder="Example: 20% Discount!" /></div>
        </div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">رابط الإعلان (اختياري)</label><input v-model="form.settings.website_announcement_url" class="input-enterprise text-left" dir="ltr" placeholder="https://..." /></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">لون الخلفية</label>
            <div class="flex gap-2">
              <input type="color" v-model="form.settings.website_announcement_bg_color" class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white" />
              <input v-model="form.settings.website_announcement_bg_color" class="input-enterprise font-mono" />
            </div>
          </div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">لون النص</label>
            <div class="flex gap-2">
              <input type="color" v-model="form.settings.website_announcement_text_color" class="h-10 w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white" />
              <input v-model="form.settings.website_announcement_text_color" class="input-enterprise font-mono" />
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
