<script setup>
defineProps({ form: Object, addItem: Function, removeItem: Function, tab: String });
</script>

<template>
  <div class="space-y-8">
    <!-- Hero Section -->
    <div v-if="tab === 'landing'">
      <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4">محتوى الواجهة الرئيسية (Hero Section)</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">العنوان التسويقي <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_title_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Hero Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_title_en" class="input-enterprise text-left" dir="ltr" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">النص التعريفي <span class="badge-lang">AR</span></label><textarea v-model="form.settings.landing_hero_subtitle_ar" rows="3" class="input-enterprise"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Hero Subtitle <span class="badge-lang">EN</span></label><textarea v-model="form.settings.landing_hero_subtitle_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">زر الإجراء (CTA) <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_cta_text_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">CTA Button <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_cta_text_en" class="input-enterprise text-left" dir="ltr" /></div>
      </div>
      <!-- Typed Animation Section -->
      <div class="col-span-full mt-6 pt-6 border-t border-dashed border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg mb-6">
          <div>
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">النص المتحرك (Typed Animation)</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء النص المتحرك في الواجهة الرئيسية تحت العنوان.</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.settings.landing_hero_typed_enabled" true-value="1" false-value="0" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
          </label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">النص الأول (قبل المتحرك) <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_typed_prefix_ar" class="input-enterprise" placeholder="أدر" /></div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Prefix Text <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_typed_prefix_en" class="input-enterprise text-left" dir="ltr" placeholder="Elevate" /></div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">النص الأخير (بعد المتحرك) <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_hero_typed_suffix_ar" class="input-enterprise" placeholder="بذكاء حقيقي" /></div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Suffix Text <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_hero_typed_suffix_en" class="input-enterprise text-left" dir="ltr" placeholder="With Real Intelligence" /></div>
          <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">الكلمات المتحركة <span class="badge-lang">AR</span></label><textarea v-model="form.settings.landing_hero_typed_phrases_ar" rows="3" class="input-enterprise" placeholder="مركزك الآن|أرباحك اليوم|فريقك معك|مستقبلك هنا" /><p class="text-xs text-gray-500 mt-1">كل كلمة على سطر منفصل أو استخدم (|) للفصل</p></div>
          <div class="sm:col-span-2"><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Typed Phrases <span class="badge-lang">EN</span></label><textarea v-model="form.settings.landing_hero_typed_phrases_en" rows="3" class="input-enterprise text-left" dir="ltr" placeholder="Your Workshop|Your Revenue|Your Team|Your Future" /><p class="text-xs text-gray-500 mt-1">One phrase per line or use (|) separator</p></div>
        </div>
      </div>
      <div class="col-span-full grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700">
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">نص زر تسجيل الدخول <span class="badge-lang">AR</span></label><input v-model="form.settings.website_login_button_text_ar" class="input-enterprise" /></div>
          <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Login Button <span class="badge-lang">EN</span></label><input v-model="form.settings.website_login_button_text_en" class="input-enterprise text-left" dir="ltr" /></div>
        </div>
    </div>

    <!-- Features Section -->
    <div v-if="tab === 'features'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم المميزات</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء هذا القسم من الواجهة الرئيسية.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_features_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 pb-6 border-b border-gray-200 dark:border-gray-700">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">عنوان القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_features_title_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Section Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_features_title_en" class="input-enterprise text-left" dir="ltr" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">وصف القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_features_subtitle_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Section Subtitle <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_features_subtitle_en" class="input-enterprise text-left" dir="ltr" /></div>
      </div>
      <div class="flex items-center justify-between mt-6">
        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">قائمة المميزات</h3>
        <button @click="addItem('landing_features_list', { title_ar: '', title_en: '', desc_ar: '', desc_en: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة ميزة</button>
      </div>
      <div class="space-y-4 mt-4">
        <div v-for="(feature, index) in form.settings.landing_features_list" :key="index" class="p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/30 flex gap-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">الميزة <span class="badge-lang">AR</span></label><input v-model="feature.title_ar" class="input-enterprise" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Feature <span class="badge-lang">EN</span></label><input v-model="feature.title_en" class="input-enterprise text-left" dir="ltr" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">التفاصيل <span class="badge-lang">AR</span></label><textarea v-model="feature.desc_ar" rows="2" class="input-enterprise"></textarea></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Details <span class="badge-lang">EN</span></label><textarea v-model="feature.desc_en" rows="2" class="input-enterprise text-left" dir="ltr"></textarea></div>
          </div>
          <button @click="removeItem('landing_features_list', index)" class="text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
        <div v-if="!form.settings.landing_features_list.length" class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400">لا توجد ميزات مضافة</div>
      </div>
    </div>

    <!-- Pricing Section -->
    <div v-if="tab === 'pricing'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم الباقات والأسعار</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء خطط الأسعار من الواجهة الرئيسية.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_pricing_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white pb-4 mt-6">إعدادات قسم الباقات</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">عنوان القسم <span class="badge-lang">AR</span></label><input v-model="form.settings.landing_pricing_title_ar" class="input-enterprise" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Title <span class="badge-lang">EN</span></label><input v-model="form.settings.landing_pricing_title_en" class="input-enterprise text-left" dir="ltr" /></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1">النص التعريفي <span class="badge-lang">AR</span></label><textarea v-model="form.settings.landing_pricing_subtitle_ar" rows="2" class="input-enterprise"></textarea></div>
        <div><label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex justify-between mb-1" dir="ltr">Subtitle <span class="badge-lang">EN</span></label><textarea v-model="form.settings.landing_pricing_subtitle_en" rows="2" class="input-enterprise text-left" dir="ltr"></textarea></div>
      </div>
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-800/40 flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        الباقات والأسعار الفعلية يتم جلبها آلياً من نظام الاشتراكات.
      </p>
    </div>

    <!-- Stats Section -->
    <div v-if="tab === 'stats'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم مؤشرات الأداء</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء الأرقام والإحصائيات من الواجهة الرئيسية.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_stats_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div class="flex items-center justify-between mt-6 mb-6">
        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إدارة المؤشرات</h3>
        <button @click="addItem('landing_stats_list', { label_ar: '', label_en: '', value: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة مؤشر</button>
      </div>
      <div class="space-y-3">
        <div v-for="(stat, index) in form.settings.landing_stats_list" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex gap-4 items-center bg-gray-50 dark:bg-gray-800/30">
          <div class="w-32"><label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">الرقم/القيمة</label><input v-model="stat.value" placeholder="100+" class="input-enterprise font-mono text-center" dir="ltr" /></div>
          <div class="flex-1"><label class="text-xs text-gray-500 dark:text-gray-400 block mb-1 flex justify-between">الوصف <span class="badge-lang">AR</span></label><input v-model="stat.label_ar" class="input-enterprise" /></div>
          <div class="flex-1"><label class="text-xs text-gray-500 dark:text-gray-400 block mb-1 flex justify-between" dir="ltr">Label <span class="badge-lang">EN</span></label><input v-model="stat.label_en" class="input-enterprise text-left" dir="ltr" /></div>
          <button @click="removeItem('landing_stats_list', index)" class="text-gray-400 hover:text-red-600 mt-5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
        <div v-if="!form.settings.landing_stats_list.length" class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400">لا توجد مؤشرات مضافة</div>
      </div>
    </div>

    <!-- Testimonials Section -->
    <div v-if="tab === 'testimonials'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم عملائنا (شركاء النجاح)</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء شعارات الشركات والورش الموثوقة.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_testimonials_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div class="flex items-center justify-between mt-6 mb-6">
        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إدارة شعارات العملاء</h3>
        <button @click="addItem('landing_testimonials_list', { name: '', logo_url: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة عميل</button>
      </div>
      <div class="space-y-3">
        <div v-for="(client, index) in form.settings.landing_testimonials_list" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex gap-4 items-center bg-gray-50 dark:bg-gray-800/30">
          <div class="flex-1"><label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم العميل/الشركة</label><input v-model="client.name" class="input-enterprise" /></div>
          <div class="flex-1"><label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">رابط الشعار (صورة)</label><input v-model="client.logo_url" class="input-enterprise text-left font-mono" dir="ltr" placeholder="https://" /></div>
          <button @click="removeItem('landing_testimonials_list', index)" class="text-gray-400 hover:text-red-600 mt-5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
    </div>

    <!-- FAQ Section -->
    <div v-if="tab === 'faq'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل قسم الأسئلة الشائعة</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء قسم الأسئلة المتكررة من الواجهة الرئيسية.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_faq_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div class="flex items-center justify-between mt-6 mb-6">
        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white">إدارة الأسئلة الشائعة</h3>
        <button @click="addItem('landing_faq_list', { q_ar: '', a_ar: '', q_en: '', a_en: '' })" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">إضافة سؤال</button>
      </div>
      <div class="space-y-4">
        <div v-for="(faq, index) in form.settings.landing_faq_list" :key="index" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/30 relative group">
          <button @click="removeItem('landing_faq_list', index)" class="absolute top-4 left-4 text-gray-400 hover:text-red-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">السؤال <span class="badge-lang">AR</span></label><input v-model="faq.q_ar" class="input-enterprise" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Question <span class="badge-lang">EN</span></label><input v-model="faq.q_en" class="input-enterprise text-left" dir="ltr" /></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between">الإجابة <span class="badge-lang">AR</span></label><textarea v-model="faq.a_ar" rows="3" class="input-enterprise"></textarea></div>
            <div><label class="text-xs text-gray-500 dark:text-gray-400 mb-1 flex justify-between" dir="ltr">Answer <span class="badge-lang">EN</span></label><textarea v-model="faq.a_en" rows="3" class="input-enterprise text-left" dir="ltr"></textarea></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Form Toggle -->
    <div v-if="tab === 'contact'" class="pt-6 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div>
          <h4 class="text-sm font-bold text-gray-900 dark:text-white">تفعيل نموذج التواصل</h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">إظهار أو إخفاء قسم "تواصل معنا" من الواجهة الرئيسية.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.landing_contact_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div class="mt-4 p-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg">
        <h3 class="font-bold text-blue-800 dark:text-blue-300 mb-2">إعدادات بريد الاستقبال</h3>
        <p class="text-sm text-blue-700 dark:text-blue-400">يتم إرسال رسائل الزوار القادمة من نموذج التواصل إلى البريد الإلكتروني المحدد في تبويب (SEO وبيانات التواصل).</p>
      </div>
    </div>
  </div>
</template>
