# توحيد ملفات الترجمة

## الوضع الحالي
يوجد مصدران للترجمات:
1. `/lang/ar/*.php` و `/lang/en/*.php` - ترجمات Laravel للـ Backend
2. `/resources/js/i18n/lang/ar.json` و `en.json` - ترجمات Vue.js للـ Frontend

## المشاكل
- [ ] تكرار الترجمات في مكانين
- [ ] صعوبة الصيانة والمزامنة
- [ ] احتمال عدم التطابق بين الترجمات

## الحل المقترح

### الخيار 1: توحيد في JSON (مُوصى به)
1. نقل كل الترجمات إلى `/resources/js/i18n/lang/*.json`
2. إنشاء Artisan Command لتحويل JSON إلى PHP للـ Backend
3. أو استخدام Laravel JSON translations مباشرة

### الخيار 2: توحيد في PHP
1. الإبقاء على `/lang/*.php`
2. إنشاء build step لتحويل PHP إلى JSON للـ Frontend

## خطوات التنفيذ (الخيار 1)

### المرحلة 1: دمج الترجمات
- [ ] مراجعة كل ملفات PHP في `/lang/ar/` و `/lang/en/`
- [ ] دمج الترجمات الناقصة في ملفات JSON
- [ ] التأكد من تطابق المفاتيح

### المرحلة 2: تحديث Backend
- [ ] تغيير طريقة تحميل الترجمات في Laravel لاستخدام JSON
- [ ] أو إنشاء ServiceProvider يحول JSON إلى PHP translations

### المرحلة 3: حذف الملفات المكررة
- [ ] حذف ملفات PHP المكررة
- [ ] تحديث أي imports أو references

### المرحلة 4: التوثيق
- [ ] توثيق الهيكل الجديد
- [ ] تحديث workflows للمطورين

## الملفات المتأثرة
```
lang/
├── ar/
│   ├── auth.php
│   ├── messages.php
│   ├── pagination.php
│   ├── passwords.php
│   ├── permissions.php
│   ├── purchasing.php
│   └── validation.php
└── en/
    └── (نفس الملفات)

resources/js/i18n/lang/
├── ar.json
└── en.json
```

## الأولوية
متوسطة - يمكن تنفيذها بعد استقرار الميزات الأساسية

## الوقت المتوقع
4-6 ساعات
