<script setup>
defineProps({ form: Object });
</script>

<template>
  <div class="space-y-12">

    <!-- WhatsApp Floating Button -->
    <div class="border border-green-100 dark:border-green-900/50 bg-green-50/30 dark:bg-green-900/10 rounded-xl p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-bold text-green-900 dark:text-green-300 flex items-center gap-2">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.067 2.877 1.215 3.076.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.615 11.615 0 0012.03 0C5.399 0 .007 5.391 0 12.02c0 2.119.554 4.188 1.606 6.006L0 24l6.135-1.61a11.604 11.604 0 005.891 1.607h.005c6.632 0 12.023-5.391 12.027-12.021a11.615 11.615 0 00-3.644-8.283z"/></svg>
            زر الواتساب العائم
          </h3>
          <p class="text-xs text-green-700 dark:text-green-400">تفعيل زر دردشة سريع يظهر أسفل الصفحة.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.website_whatsapp_floating_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
        </label>
      </div>
      <div :class="{'opacity-50 pointer-events-none': form.settings.website_whatsapp_floating_enabled != '1'}" class="grid grid-cols-1 gap-4 transition-opacity">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">رقم الواتساب المستهدف</label>
        <input v-model="form.settings.website_whatsapp_floating_number" class="input-enterprise text-left font-mono" dir="ltr" placeholder="+966" />
        <p class="text-[10px] text-gray-500 dark:text-gray-400">ملاحظة: تأكد من إضافة رمز الدولة (مثال: 9665xxxxxxxx).</p>
      </div>
    </div>

    <!-- Chatbot Integration -->
    <div class="border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/30 dark:bg-indigo-900/10 rounded-xl p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            تكامل الشات بوت (Chatbot)
          </h3>
          <p class="text-xs text-indigo-700 dark:text-indigo-400">ربط أنظمة الدردشة مثل Tawk.to, Intercom, Zendesk.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.settings.website_chatbot_enabled" true-value="1" false-value="0" class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
      </div>
      <div :class="{'opacity-50 pointer-events-none': form.settings.website_chatbot_enabled != '1'}" class="grid grid-cols-1 gap-4 transition-opacity">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">كود التكامل (Widget Script)</label>
        <textarea v-model="form.settings.website_chatbot_script" rows="8" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Paste your script code here -->"></textarea>
        <p class="text-[10px] text-gray-500 dark:text-gray-400">تحذير: قم بلصق الكود البرمجي كما هو من مزود الخدمة.</p>
      </div>
    </div>

    <!-- Custom Scripts -->
    <div class="border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-800/30 rounded-xl p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
        أكواد إضافية (Custom Scripts)
      </h3>
      <div class="space-y-6">
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">سكريبتات رأس الصفحة (Header Scripts)</label>
          <textarea v-model="form.settings.website_scripts_header" rows="5" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Google Analytics, Facebook Pixel, etc. -->"></textarea>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">تظهر هذه الأكواد داخل وسم &lt;head&gt;.</p>
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">سكريبتات تذييل الصفحة (Footer Scripts)</label>
          <textarea v-model="form.settings.website_scripts_footer" rows="5" class="input-enterprise font-mono text-left" dir="ltr" placeholder="<!-- Tracking pixels, custom widgets, etc. -->"></textarea>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">تظهر هذه الأكواد قبل إغلاق وسم &lt;/body&gt;.</p>
        </div>
      </div>
    </div>

  </div>
</template>
