<script setup>
defineProps({ form: Object, addItem: Function, removeItem: Function, tab: String });
</script>

<template>
  <div class="space-y-10">
    <!-- Header Menu -->
    <div v-if="tab === 'menus'">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">قائمة التنقل الرئيسية (Header)</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">روابط تظهر في شريط التنقل العلوي للموقع</p>
        </div>
        <button @click="addItem('website_header_menu', { label_ar: '', label_en: '', url: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs py-1.5">إضافة عنصر</button>
      </div>
      <div class="space-y-3">
        <div v-for="(item, index) in form.settings.website_header_menu" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">الاسم (AR)</label><input v-model="item.label_ar" class="input-enterprise" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Label (EN)</label><input v-model="item.label_en" class="input-enterprise text-left" dir="ltr" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">المسار (URL)</label><input v-model="item.url" class="input-enterprise text-left font-mono" dir="ltr" /></div>
          </div>
          <button @click="removeItem('website_header_menu', index)" class="text-gray-400 hover:text-red-600 mt-6"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
        <div v-if="!form.settings.website_header_menu.length" class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400">القائمة فارغة - أضف أول عنصر</div>
      </div>
    </div>

    <!-- Footer Menu -->
    <div v-if="tab === 'menus'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">روابط التذييل (Footer)</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">روابط تظهر في أسفل الموقع</p>
        </div>
        <button @click="addItem('website_footer_menu', { label_ar: '', label_en: '', url: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs py-1.5">إضافة عنصر</button>
      </div>
      <div class="space-y-3">
        <div v-for="(item, index) in form.settings.website_footer_menu" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50 flex gap-4 items-start group">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">الاسم (AR)</label><input v-model="item.label_ar" class="input-enterprise" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Label (EN)</label><input v-model="item.label_en" class="input-enterprise text-left" dir="ltr" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">المسار (URL)</label><input v-model="item.url" class="input-enterprise text-left font-mono" dir="ltr" /></div>
          </div>
          <button @click="removeItem('website_footer_menu', index)" class="text-gray-400 hover:text-red-600 mt-6"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
    </div>

    <!-- Banners -->
    <div v-if="tab === 'banners'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إدارة اللوحات الإعلانية (Sliders)</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">بانرات عرض تظهر في الواجهة الرئيسية</p>
        </div>
        <button @click="addItem('landing_banners_list', { title_ar: '', title_en: '', image: '', link: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة لوحة جديدة</button>
      </div>
      <div class="space-y-6">
        <div v-for="(banner, index) in form.settings.landing_banners_list" :key="index" class="p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/30 dark:bg-gray-800/30 flex flex-col sm:flex-row gap-6 relative group">
          <button @click="removeItem('landing_banners_list', index)" class="absolute top-3 left-3 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
          <div class="w-full sm:w-48 flex-shrink-0">
            <div class="w-full h-28 bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center overflow-hidden mb-2">
              <img v-if="banner.image" :src="typeof banner.image === 'string' ? banner.image : URL.createObjectURL(banner.image)" class="w-full h-full object-cover" />
              <svg v-else class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="relative">
              <input type="file" @input="banner.image = $event.target.files[0]" class="hidden" :ref="'bannerInput' + index" accept="image/*" />
              <button @click="$refs['bannerInput' + index]?.[0]?.click()" type="button" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium hover:text-indigo-800 w-full text-center">تغيير الصورة</button>
            </div>
          </div>
          <div class="flex-1 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">العنوان <span class="badge-lang">AR</span></label><input v-model="banner.title_ar" class="input-enterprise" /></div>
              <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Title <span class="badge-lang">EN</span></label><input v-model="banner.title_en" class="input-enterprise text-left" dir="ltr" /></div>
              <div class="md:col-span-2"><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">رابط الاستهداف (URL)</label><input v-model="banner.link" class="input-enterprise text-left font-mono" dir="ltr" placeholder="https://" /></div>
            </div>
          </div>
        </div>
        <div v-if="!form.settings.landing_banners_list.length" class="text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400">لا توجد بانرات مضافة</div>
      </div>
    </div>
  </div>
</template>
