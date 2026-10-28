# مراجعة Carag V2 — 2026-10-27

## الملخص التنفيذي

هذه مراجعة مستمرة للحالة الحالية `HEAD 43465eb7`، هدفها فحص المشروع صفحةً وميزةً، لا الاكتفاء بفحوص آلية. حتى الآن جرى جرد 190 ملف Vue و648 مساراً، وفحص مجموعات عروض الأسعار/الطباعة، المخزون والمشتريات، الفواتير، HR/بوابة الموظف، العملاء/المركبات، مسارات بوابة العميل والتسجيل وOTP، تحليلات النظام، إعدادات المخزون، المواعيد ولوحة الورشة، وتدفق الدخول و2FA. وثّقت **69 نتيجة** بدرجات مختلفة مع أدلة ومسارات معالجة. لم أعدّل كود التطبيق.

أعلى المخاطر المثبتة حالياً تشمل تجاوز سياق المستأجر في المخزون، إكمال اشتراك بمرجع دفع غير مطابق، إلغاء أي عضو للاشتراك بما قد يعلّق المنشأة، كشف بيانات شخصية من نقاط فحص بلا صلاحيات قراءة، عرض مسودات الرواتب للموظف، وتجاوز صلاحية ترحيل سندات الاستلام. نظام 2FA للموظفين فيه عطل إعداد، و2FA مدير النظام لا يُفرض عند الدخول رغم تفعيله. توجد أيضاً أعطال وظيفية مشروطة مثل 500 عند فشل مزود إرسال رمز 2FA أو بلوغ حد OTP. هذه النتائج لا تثبت تعطل الموقع بالكامل؛ الصفحتان العامة وتسجيل الدخول ظهرتا محلياً. فحص الواجهات المحمية بصرياً وتفاعلياً ما زال غير مكتمل لغياب جلسة اختبار.

## نطاق الفحص ونتائجه

- جردت **190 صفحة Vue** تحت `resources/js/Pages`، و**149 Controller**، و**121 ملف اختبار PHP**.
- استخرجت **151 اسم صفحة Inertia ثابتاً** من استدعاءات PHP وراجعت وجود ملفاتها: لم أجد أسماء صفحات مفقودة في الحالة الحالية.
- راجعت manifest البناء: لم تظهر إحالات إلى ملفات أصول مفقودة.
- اختبارات الواجهة: **29/29 ناجحة** (14 ملف اختبار).
- ESLint: اكتمل بلا أخطاء، لكنه أبلغ **1,780 تحذيراً**.
- PHPStan: اكتمل بنجاح، **لا أخطاء**، باستخدام `--debug` وSQLite مؤقتاً.
- اختبارات Laravel الكاملة: **929 ناجحاً، 2 فاشلان، 5 متجاوزة** (3960 assertion، 751.17 ثانية). الفشلان كلاهما في `Error403HomeLinkTest` لأن `resources/js/Pages/Errors/403.vue` غير موجود. الاختبار يطلب هذه الصفحة ويصف سلوكها، لكن لم أجد مرجعاً لها في معالج الاستثناءات أو مسارات Inertia الحالية؛ لذلك هذا فشل اختبار مؤكد، أما أثره للمستخدم فيتوقف على مسار عرض 403 الفعلي.
- Pint `--test`: فشل في الملفات التالية بسبب التنسيق:
  - `app/Http/Controllers/App/Quotes/QuoteController.php`
  - `app/Http/Controllers/App/QuoteApprovalController.php`
  - `app/Http/Controllers/Public/PublicQuoteController.php`
- لم أستبدل ملفات البناء أو أغيّر البيئة؛ التشغيل الافتراضي كان يحاول الاتصال بـ MySQL المحلي، لذلك استخدمت إعداد اختبار SQLite لتشغيل الفحوص التي تسمح بذلك.

## ترتيب المخاطر المثبتة في المراجعة الحالية

| الأولوية | الخلل | الوحدة | درجة الثقة |
|---|---|---|---|
| P1 | تغيير أرصدة مخزون خارج مستأجر المستخدم عبر IDs مباشرة | المخزون | مرتفعة من مسار الكود |
| P1 | ترحيل GRN بصلاحية إنشاء/عرض فقط، دون فحص صلاحية الترحيل | المشتريات | مرتفعة من مسار الكود |
| P1 | رابط الموظف/الحضور قد يلوث حساب راتب مركز آخر داخل المستأجر | HR | مرتفعة، مشروطة بسياسة عزل المراكز |
| P1 | المستخدم غير المؤكد مع فرض 2FA قد يتلقى 500 بسبب اسم مسار إعداد غير موجود | الدخول/2FA | مؤكدة بنيوياً؛ لم تُختبر بجلسة مستخدم |
| P1 | 2FA مدير النظام لا يُفرض عند تسجيل الدخول حتى إذا كان مفعّلاً | لوحة النظام/الدخول | مؤكدة من تفرع login ومسارات challenge المنفصلة غير المستخدمة |
| P1 | نقاط فحص الجوال واللوحة تكشف PII بلا صلاحية قراءة الوحدة | العملاء/المركبات | مرتفعة من المسارات والسياسات الحالية |
| P1 | عقد الموظف يمكن إنشاؤه/تعديله عبر موظف من مركز آخر داخل المستأجر | HR/العقود | مرتفعة من ربط المسار والسياسات الحالية |
| P1 | دعم/مدير نظام غير مميز يستطيع تعديل الباقات والرموز والاشتراكات | لوحة النظام/الفوترة | مؤكدة من مجموعة المسارات وغياب قيد الدور |
| P1 | دعم النظام يستطيع ترقية نفسه إلى `super_admin` وإنشاء مديرين | لوحة النظام/الهوية | مؤكدة من المسارات والمتحكم والنموذج |
| P1 | دعم النظام يستطيع إرسال بريد/SMS جماعي للمستأجرين | لوحة النظام/الإعلانات | مؤكدة من المسار وسلوك النشر |
| P1 | أي مستخدم تطبيق يستطيع تغيير مفتاح إعداد فحوص المركبات على مستوى النظام كله | الخدمات/الفحوص | مؤكدة من endpoint عام وSetting مشترك يقرأه تدفق أمر العمل |
| P1 | قائمة التكاملات تكشف مفاتيح API وكلمات المرور في بيانات Inertia | النظام/التكاملات | مؤكدة من serialization والنموذج |
| P1 | أي مستخدم تطبيق يستطيع تغيير إعدادات المنشأة والضريبة والطباعة دون صلاحية إدارة | إعدادات المنشأة | مؤكدة من route/controller |
| P1 | مستخدم مركز يستطيع عرض وتعديل إعدادات مركز آخر في المستأجر | الفروع/إعدادات المراكز | مؤكدة من CenterPolicy وربط المسار |
| P1 | أي مستخدم تطبيق يستطيع إنشاء/اعتماد معاملات مالية بقيم يرسلها العميل | مالية المنشأة | مؤكدة من routes/controller وخط إصدار الفواتير |
| P1 | إعدادات HR والرواتب قابلة للتغيير دون صلاحية HR إدارية | الموارد البشرية | مؤكدة من routes ومتحكمات CRUD |
| P1 | مستخدم مفوض بإدارة المستخدمين يستطيع إنشاء/تعديل/حذف مستخدم tenant `super_admin` | المستخدمون/الأدوار | مؤكدة من المتحكم والسياسة؛ تتطلب صلاحية USERS المقابلة |
| P1 | محرر الأدوار يستطيع منح دوره الحالي أي صلاحيات ورفع امتيازاته ذاتياً | المستخدمون/الأدوار | مؤكدة من المتحكم والواجهة؛ تتطلب USERS_UPDATE |
| P1 | أي عضو tenant مصادق عليه يستطيع إلغاء الاشتراك وتعليق حساب المنشأة | الفوترة الذاتية | مؤكدة من routes والمتحكم؛ لا يفحص دوراً أو صلاحية مالك |
| P1 | callback يقبل دفعة ناجحة غير مطابقة للمبلغ/المرجع ويستطيع تفعيل اشتراك غير مدفوع بالكامل | الفوترة/بوابة الدفع | مؤكدة من callback وخدمة الإكمال؛ يلزم معرّف دفعة ناجحة لدى البوابة |
| P1 | موظف مستأجر يستطيع تعطيل/تفعيل فحوص المركبات عالمياً لكل المستأجرين | إعدادات الفحوصات | مؤكدة من endpoint واستخدام المفتاح العام؛ لا يوجد تفويض إدارة |
| P1 | تقرير المدفوعات وتصديره يكشفان بيانات مالية لمستخدم بلا صلاحية قراءة المدفوعات | التقارير/المدفوعات | مؤكدة من controller وPaymentPolicy والمسارات |
| P1 | رفع شعار الموقع يضع امتداد الملف المرسل على القرص العام رغم التحقق من MIME فقط | إعدادات الموقع/رفع الملفات | مؤكدة من مسار التخزين؛ تنفيذ PHP يعتمد على إعداد الخادم |
| P1 | معالج ZATCA يعرض الامتثال والإنتاج نشطين دون اتصال فعلي بالهيئة | ZATCA | مؤكدة من نتائج ثابتة وتوليد CSID محلي |
| P1 | حذف بند تحويل مخزون لا يتحقق من تبعيته للتحويل المصرح | المخزون/التحويلات | مؤكدة من endpoint وعلاقة الطفل |
| P1 | استلام تحويل مخزون يقبل كمية أكبر من المرسلة ويزيد الرصيد | المخزون/التحويلات | مؤكدة من التحقق والخدمة |
| P1 | بوابة الموظف تعرض عناصر رواتب مسيرات بحالة `draft` | HR/بوابة الموظف | مرتفعة من مسار إنشاء المسير ومسارات العرض |
| P1 | ربط الفاتورة وسياسة العرض يتجاوزان نطاق المركز | الفواتير | مرتفعة من binding والسياسة وقائمة المركز |
| P1 | معرفات عناصر متداخلة تسمح بالتعديل عبر أمر أب مختلف | أوامر العمل | مرتفعة من المسارات والمت controllers |
| P1 | تعديل/حذف أجزاء عبر مسار قديم يتجاوز عكس حركة المخزون | أوامر العمل/المخزون | مرتفعة من اختلاف controller عن الخدمة |
| P1 | إنشاء الفاتورة الآجلة على أمر مكتمل محجوب بسياسة تعديل أمر العمل | الفواتير/أوامر العمل | مؤكدة من زر الواجهة والسياسة والمتحكم |
| P1 | إنشاء مرتجع شراء ببنود من فاتورة أخرى أو بكميات تتجاوز المتبقي | المشتريات/المخزون | مرتفعة من التحقق والمعاملة الحالية |
| P2 | تسجيل الدفع على فاتورة صادرة يرفضه `InvoicePolicy::update` | الفواتير | مرتفعة من مسار الكود |
| P2 | نموذج الشراء المباشر يقبل مجموع دفعات أولية أكبر من إجمالي الفاتورة | المشتريات/المدفوعات | مرتفعة من قواعد التحقق والخدمة |
| P2 | إنشاء اشتراك إداري يتجاهل دورة الفوترة وسعرها المختار | النظام/الاشتراكات | مؤكدة من نموذج الإنشاء والمتحكم |
| P2 | مسارات index/show/create/edit للمعاملات مسجلة بلا methods مقابلة | مالية المنشأة | مؤكدة من route:list والمتحكم |
| P2 | استنفاد حد تحقق OTP يسبب 500 بدلاً من رسالة انتظار/429 | بوابة العميل | مرتفعة من الخدمة والمتحكم والاختبارات |
| P2 | عناصر GRN/PO غير مقيدة بما يتبع الأمر/المستأجر | المشتريات | مرتفعة من مسار الكود |
| P2 | عناصر الرواتب/دفعات HR تُدار بتفويض غير مطابق أو لا يتحقق من علاقة الأب | HR | مرتفعة من مسار الكود |
| P2 | غياب القفل وإعادة فحص الرصيد قد يسمحان بزيادة التحصيل تحت التزامن | الفواتير/المدفوعات | مخاطرة مثبتة من مسار المعاملة، مشروطة بالتزامن |
| P2 | رصيد إجازة سنوية ثابت يخصم كل أنواع الإجازات | بوابة الموظف | مؤكدة من حساب المتحكم |
| P2 | توقيع SVG عام وخيار إظهار التوقيع يتجاهل إعداد الإخفاء | عروض الأسعار | مرتفعة من مسار الكود |
| P2 | مسار إيصال التحويل البنكي يقبل دورة شهرية لكنه ينشئ اشتراكاً سنوياً دائماً | الفوترة الذاتية | مؤكدة لمسار backend؛ الواجهة الحالية تعرض السنوي فقط |
| P2 | مستخدم المركز العادي يستطيع تعديل كتالوج المركبات وفحوصها | إعدادات المركبات | مؤكدة من المتحكمات والمسارات؛ نطاقه محدود بسياق المركز |
| P2 | أي عضو في المستأجر يستطيع تعديل تصنيفات المعاملات المالية | إعدادات المنشأة/المعاملات | مؤكدة من المتحكم وTenantScoped؛ لا يوجد تفويض إدارة |
| P2 | إضافة خدمة لأمر عمل تقبل service/department من مستأجر آخر | أوامر العمل | مؤكدة من التحقق والربط؛ أثرها تكامل بيانات وعرض خاطئ |
| P2 | تصدير تقرير المدفوعات يمرر نصوص المستخدم إلى CSV دون تحييد صيغ الجداول | التقارير/المدفوعات | مؤكدة من notes غير المقيدة بصيغة آمنة ومسار التصدير؛ التفعيل عند فتح الملف |
| P2 | قائمة تقرير المدفوعات تكشف أسماء وهواتف عملاء من مراكز أخرى للمستخدم المركزّي | التقارير/المدفوعات | مؤكدة من query قائمة العملاء غير المقيدة بـcenter_id والنموذج TenantScoped فقط |
| P3 | فتح الإشعار قبل اكتمال طلب تعليمه كمقروء قد يتركه غير مقروء | الإشعارات | مؤكدة من عدم انتظار fetch قبل التنقل الكامل إلى action_url |
| P2 | معدل إلغاء الاشتراكات يحسب مقاماً لا يمثل الاشتراكات النشطة عند بداية الفترة | تحليلات النظام | مؤكدة من صيغة الاستعلام؛ اختبارات analytics لا تغطي churn |
| P2 | احتفاظ المستأجرين وصحة الحساب تاريخياً يعاد احتسابهما من status الحالي | تحليلات النظام | مؤكدة من استعلامات الحالة الحالية دون سجل زمني |
| P2 | MRR الباقات يستخدم السعر الشهري المنشور لا الإيراد الفعلي لكل اشتراك | تحليلات النظام | مؤكدة من تجميع topPlans والحمولة المعروضة |
| P1 | أدوار الدعم/المسؤول التشغيلي تستطيع تنزيل تقارير النظام الشاملة بلا صلاحية تقارير | لوحة النظام/التقارير | مؤكدة من middleware المجموعة ومسارات التصدير ومحتوى الملفات |
| P2 | إنشاء/تعديل/حذف وحدات وفئات المخزون يتجاوز صلاحية إدارة الإعدادات | المخزون/الإعدادات | مؤكدة من اختلاف فحوص authorization بين الدوال |
| P2 | إنشاء موعد من مركز آخر ممكن باستخدام معرّفات مستأجر صالحة | المواعيد | مؤكدة من استخدام customer.center_id وغياب تحقق المركز النشط |
| P2 | زر «لم يحضر» يبقى ظاهراً بعد تسجيل الوصول ويرفضه الخادم | المواعيد | مؤكدة من شرط Vue وانتقالات الخدمة |
| P2 | تعطل إرسال رمز 2FA قد يعيد 500 ويترك المستخدم بلا شاشة دخول عملية | الدخول/2FA | مؤكدة من ترتيب الاستدعاءات والتخزين؛ مشروطة بفشل البريد/SMS |
| P2 | سجل التطبيق يحتفظ برقم الهاتف ورمز OTP الخام لمحاولات تحقق التسجيل | التسجيل/الخصوصية | مؤكدة من بيانات Log::info في endpoint عام |
| P2 | التسجيل يعرض ويقبل رمزاً ترويجياً لكنه يتجاهل تطبيقه | التسجيل/الاشتراكات | مؤكدة من واجهة التسجيل وتعليق TODO داخل الإنشاء |
| P3 | لوحة الورشة لا تكسر تعادل موعد التسليم بتاريخ بدء العمل كما يذكر التعليق | الورشة | مؤكدة من comparator واختبارات الفرز الحالية |

الترتيب أعلاه لا يعني أن كل الخلل يسبب تعطل الموقع كله؛ معظمها أعطال مسارات أو صلاحيات/سلامة بيانات محددة. العطل العام لم يُثبت بعد، والصفحة العامة وتسجيل الدخول ظهرتا في المتصفح المحلي.

## النتائج التفصيلية

### 1) بيانات توقيع SVG على القرص العام

**الأثر:** يمكن لطرف يحمل رابط عرض سعر عام صالح إرسال توقيع بصيغة SVG يحتوي محتوى نشط، ثم حفظه كملف `.svg` في التخزين العام. قد يتيح فتح رابط الملف مباشرةً عرض محتوى نشط على نطاق التطبيق. كما لا يوجد حد لحجم سلسلة التوقيع في مساري الموافقة العامة أو الداخلية، ما يرفع احتمال استهلاك الذاكرة/التخزين بطلبات كبيرة. يوجد تحديد معدل عام (`10` طلبات/دقيقة لكل IP) لكنه لا يحل مشكلة المحتوى أو الحجم.

**الدليل:**
- `app/Http/Controllers/Public/PublicQuoteController.php:150-166` يقبل URI ذات `image/*;base64`، يختار الامتداد من النوع، ويفك Base64 دون وضع صارم، ثم يحفظ على `Storage::disk('public')`.
- `app/Http/Controllers/App/QuoteApprovalController.php:38-56` يكرر السلوك ذاته.
- `app/Http/Controllers/App/Quotes/QuoteController.php:597-627` يقبل `required|string` فقط، ثم يحفظ الملف العام.
- `app/Models/Quote.php:219-225` يُصدر URL للملف من قرص `public`.
- الاختبارات في `tests/Feature/QuoteParityFeaturesTest.php:198-230,298-328` تغطي صورة PNG صغيرة فقط؛ لا تغطي SVG ضاراً أو حجماً كبيراً أو نوع محتوى مضللاً.

**المعالجة المقترحة:** استخدام خدمة واحدة مشتركة للتحقق من التوقيع في المسارات الثلاثة، وضع حد صغير للحجم قبل فك Base64، استخدام فك صارم والتحقق من بيانات الصورة الفعلية، ورفض SVG أو تنظيفه عبر محلل موثوق. لا تُحفظ ملفات التوقيع في قرص عام إلا عند حاجة واضحة؛ وإذا بقيت عامة فيجب تقديمها كتنزيل أو من نطاق منفصل مع نوع محتوى آمن.

### 2) خيار `show: false` يُستبدل بـ `true`

**الأثر:** إعداد إخفاء توقيع العميل لا ينعكس في طباعة العرض؛ قد يظهر التوقيع على المستند رغم تعطيل ظهوره في إعداداته.

**الدليل:**
- `resources/js/Pages/Quotes/Print.vue:245-251`
- `resources/js/Pages/Quotes/PublicShow.vue:733-739`

في كلا الموضعين، تُنسخ إعدادات التوقيع ثم يفرض الكود `show: true`. قوالب الطباعة تتعمد احترام `show !== false` في `resources/js/Components/Print/PrintTemplates/TemplateDefaultA4.vue:1782-1789` و`TemplateThermal80.vue:519-524`، لكن قيمة الإعداد تُمحى قبل وصولها للقالب.

**المعالجة المقترحة:** تحديث رابط التوقيع فقط مع الحفاظ على `show` المحفوظ، وتحديد سلوك واضح إذا لم يوجد إعداد توقيع عميل.

### 3) اختبار 403 يشير إلى ملف غير موجود

**النتيجة:** فشل اختباران من مجموعة Laravel لأنهما يحاولان قراءة `resources/js/Pages/Errors/403.vue`، وهو غير موجود. الاختباران هما `tests/Feature/Frontend/Error403HomeLinkTest.php:26,59`. يصف الاختبار صفحة 403 مخصصة، لكن لم يظهر استدعاء حالي لـ `Errors/403` في `app/` أو `bootstrap/` أو `routes/`؛ لذا لا أستنتج أن كل استجابة 403 في التطبيق معطلة. ينبغي إما استعادة الصفحة وربطها بمعالج 403 إن كانت مقصودة، أو حذف/تحديث اختبار العقد إذا كانت صفحة قديمة غير مستخدمة.

### 4) التحذيرات والجودة

- ESLint اكتمل بلا أخطاء لكن لديه 1,780 تحذيراً، منها متغيرات غير مستخدمة ومخالفات `eqeqeq`؛ ينبغي تقسيمها حسب الصفحة والوحدة وإزالة التحذيرات تدريجياً.
- اختبارات `AppSelect` تصدر تحذيراً بأن خاصية `options` غير معرّفة على المكوّن، ولا تتحقق من عرض الخيارات؛ لم أجد استخداماً فعلياً واسعاً للمكوّن في صفحات التطبيق، لذلك صنّفته كدين جودة منخفض الأولوية وليس عطل مستخدم مثبتاً.
- Pint يشير إلى مخالفات تنسيق في ثلاثة ملفات من تغييرات عروض الأسعار؛ هذا لا يثبت عطل تشغيل، لكنه يجعل بوابة التنسيق تفشل.

## ملاحظات سلامة المراجعة

كانت مساحة العمل تحتوي تعديلات غير مثبتة على Git في بداية الفحص، بما فيها تغييرات عروض الأسعار والطباعة وملفات البناء وملفات التوثيق، إضافة إلى مجلد تقارير غير متتبع. لذلك فالمراجعة تصف **حالة مساحة العمل** ولا تنسب كل تغيير إلى commit منشور أو إصدار إنتاجي. لم أعدّل هذه الملفات.

هذا التقرير يمثل المرحلة الأولى من التدقيق: الجرد، التحقق الآلي، ومراجعة تدفق عروض الأسعار/الطباعة الذي تغير حالياً. يلزم استكمال المراجعة اليدوية لوحدات العملاء، أوامر العمل، الفواتير، المخزون، الموارد البشرية، المشتريات، النظام، بوابة الموظف، والتكاملات، صفحةً صفحةً، مع ربط كل صفحة بالمسارات والصلاحيات وحالات الفشل ذات الصلة.

## نتائج المرحلة الثانية — المشتريات والمخزون والرواتب

### 5) **حرج — إمكان تعديل رصيد مخزون مستأجر آخر عبر أرقام موارد مباشرة**

**الأثر:** مستخدم يملك صلاحية `inventory.moves.create` في مركز/مستأجر يمكنه إرسال `warehouse_id` و`part_id` صالحين لكن تابعين لمستأجر آخر إلى نقاط الاستلام أو التسوية اليدوية. الطلب يتحقق من وجود السجلين فقط؛ خدمة المخزون تتجاوز جميع النطاقات العامة ثم تحدّث رصيد المخزن/القطعة مباشرة بحسب الرقمين. هذا يسمح بتغيير أرصدة مخزون خارج سياق المستخدم، وقد يسجل حركة جديدة بقيم سياق المستأجر الحالي مع مرجع لموارد المستأجر الآخر.

**الدليل:**
- `app/Http/Controllers/App/InventoryMoveController.php:88-107,115-137`: `exists:warehouses,id` و`exists:parts,id`، ثم استدعاء الخدمة بعد فحص صلاحية عامة.
- `app/Policies/InventoryMovePolicy.php:22-37`: صلاحية الإنشاء لا تتحقق من ملكية المستودع أو القطعة.
- `app/Services/Inventory/InventoryService.php:40-53,113-124`: `withoutGlobalScopes()` ثم بحث/تحديث الرصيد بواسطة `warehouse_id` و`part_id` فقط.
- `app/Models/InventoryBalance.php:79-106`: مسار `getOrCreate` نفسه يتجاوز النطاقات ويستخدم المفتاح الطبيعي دون فحص أن المستودع والقطعة من المستأجر/المركز المخوّل.
- `app/Http/Controllers/App/WorkOrderPartsController.php:31-44,92-98` يقبل `part_id` و`warehouse_id` بقاعدة `exists` فقط ثم ينشئ جزء أمر العمل؛ `WorkOrderPartsService` يمرر تلك المعرفات إلى `InventoryService::issue()`، الذي يبحث عن الرصيد ويعدله عبر `withoutGlobalScopes()` (`app/Services/Inventory/InventoryService.php:113-153`). بذلك يمتد الخطر أيضاً إلى إصدار قطعة لمستودع مستأجر آخر من أمر عمل يملكه المستأجر الحالي.
- لم أجد اختبار Feature يغطي إرسال IDs من مستأجر آخر إلى نقاط استلام/تسوية المخزون أو إصدار قطعة من أمر عمل؛ الموجود يغطي الخدمة، عزل القراءة، وحقن ترتيب SQL.

**الإجراء:** رفض IDs غير التابعة للسياق المصرّح به في المتحكم، ثم إضافة حراسة متكررة في خدمة المخزون تتأكد من تطابق المستودع والقطعة والمستأجر/المركز قبل فتح معاملة الرصيد. السماح الصريح بمخازن الفروع يجب أن يكون قاعدة واضحة ومختبرة.

### 6) **عالٍ — ترحيل سند الاستلام يتجاوز صلاحية ترحيل المخزون**

**الأثر:** يمكن لمستخدم يملك `purchasing.pos.view` إنشاء سند استلام، وإرسال `post_now=true` لترحيله فوراً. في هذا المسار ينادي المتحكم خدمة الترحيل مباشرةً ولا يمر عبر `authorize('post', $grn)`، مع أن سياسة `post` تشترط `inventory.moves.create`. الترحيل ينشئ حركات مخزون ويحدّث كميات أمر الشراء ويحاول إنشاء فاتورة مشتريات؛ أي أنه ينفذ عمليات كتابة مالية ومخزنية بصلاحية مشاهدة فقط.

**الدليل:**
- `app/Policies/GoodsReceivedNotePolicy.php:23-42`: `create` يتطلب `POS_VIEW` فقط، بينما `post` يتطلب `INVENTORY_MOVES_CREATE`.
- `app/Http/Controllers/App/GoodsReceivedNotesController.php:44-64`: `store()` يجيز الإنشاء فقط، ثم فرع `post_now` يستدعي `postGoodsReceivedNote()` مباشرة.
- `app/Services/Purchasing/PurchasingService.php:229-299`: تنفيذ الترحيل يُنشئ حركات مخزون، يزيد `qty_received`، ويستدعي إنشاء الفاتورة.
- نقطة الإدخال: `routes/web.php:805-807`.

**الإجراء:** عدم تنفيذ الترحيل من `store()` دون تحقق سياسة `post`؛ الأفضل حفظ السند كمسودة ثم استخدام إجراء ترحيل منفصل يفرض الصلاحية نفسها. اختبر مستخدماً يملك `POS_VIEW` وحدها مقابل مستخدم يملك `INVENTORY_MOVES_CREATE`.

### 7) **عالٍ — عناصر سند الاستلام غير مرتبطة بأمر الشراء المقصود**

**الأثر:** عند إنشاء سند استلام لأمر شراء، تقبل المدخلات أي `purchase_order_item_id` موجود وأي `part_id` موجود. الخدمة تحمّل العنصر بحسب رقمه فقط، ولا تتحقق أن العنصر يتبع أمر الشراء الموجود في المسار، ولا أن `part_id` يطابق قطعة ذلك العنصر. عند الترحيل تُستلم القطعة المحددة في السند إلى المخزن، بينما تُزاد كمية الاستلام على عنصر أمر الشراء الذي تم تمريره؛ وهذا يفصل أثر المخزون عن أثر الفاتورة/الكمية المستلمة.

**الدليل:**
- `app/Http/Controllers/App/GoodsReceivedNotesController.php:48-60`: قواعد `exists` مستقلة للـ PO item والقطعة.
- `app/Services/Purchasing/PurchasingService.php:194-218`: يجلب `PurchaseOrderItem::findOrFail()` دون مطابقة `purchase_order_id` مع `$po->id`، ويحفظ `part_id` المرسل منفصلاً.
- `app/Services/Purchasing/PurchasingService.php:262-280`: يضيف المخزون وفق `grnItem.part_id` ثم يزيد `qty_received` لعنصر الشراء المرتبط.

**الإجراء:** استخرج عناصر السند من علاقة `$po->items()`، ارفض أي عنصر خارجها، واستنتج `part_id` من عنصر الشراء بدلاً من الثقة بحقل مستقل من الطلب. افحص أيضاً تطابق المستودع مع المستأجر/المركز.

### 8) **متوسط إلى عالٍ — حذف بند راتب لا يتحقق من تبعيته لمسير الرواتب المحدد**

**الأثر:** مسار الحذف يصرّح بتحديث مسير الرواتب الأب ويتأكد أن هذا المسير مسودة، لكنه يحذف `PayrollItem` الوارد من المسار مباشرةً. لا يتحقق أن البند تابع للمسير الأب ولا أن المسير المرتبط بالبند مسودة. مستخدم مخوّل بتحديث مسير مسودة يستطيع تمرير بند من مسير آخر في مركزه، بما في ذلك مسير معتمد أو مدفوع، وحذفه.

**الدليل:**
- `routes/web.php:906`: المسار يحتوي `{payrollRun}` و`{payrollItem}`.
- `app/Http/Controllers/App/HR/PayrollController.php:147-160`: يفحص الأب فقط ثم ينفذ `$payrollItem->delete()` مباشرة.
- لا يوجد اختبار Feature يثبت أن البند يجب أن يكون تابعاً للمسير وأنه لا يُحذف بعد اعتماد/صرف المسير.

**الإجراء:** حمّل البند عبر `$payrollRun->items()->findOrFail(...)`، وامنع التعديل إذا كان المسير المرتبط غير مسودة، ثم أضف اختبارات لتمرير بند من مسير مختلف ومن مسير مدفوع.

## مراجعة تغطية الصفحات حسب المجموعة

الجرد الحالي يشمل 190 ملف Vue تحت `resources/js/Pages` و648 مسار Laravel. نتائج اختبارات الـ Feature ليست بديلاً عن فتح كل صفحة وفحص أفعالها؛ الجدول أدناه يسجل مدى وجود اختبارات وظيفة مرتبطة بكل مجموعة، ويبين أين توجد فجوة واضحة في تدفقات الكتابة:

| المجموعة / الصفحات | تغطية اختبارات موجودة | حالة المراجعة اليدوية |
|---|---|---|
| تسجيل الدخول والتحقق، 7 صفحات | `AuthenticationTest`, `PasswordResetTest`, `EmailVerificationTest`; لم أعثر على اختبارات تدفق 2FA/OTP التسجيل | راجعت التسجيل والدخول والتحدي وإعداد 2FA ودعوات تعيين كلمة المرور؛ route setup المفقود وتجاوز 2FA مدير النظام وفشل إرسال الرمز وتسريب OTP في السجل موثقة؛ التفاعل وبقية الصفحات مفتوحان |
| العملاء والمركبات، 5 صفحات | `CustomersCrudTest`, `VehiclesCrudTest`, `VehicleBalanceNPlusOneTest` | تمت مراجعة CRUD والاستيراد والدمج وفحوص التكرار؛ ثغرة كشف PII بلا صلاحية في الفحوص مضافة كنتيجة 15 |
| المواعيد وبوابة العميل، 8 صفحات | `AppointmentTest`, `CustomerPortal*Test`, رسائل المواعيد | راجعت عزل OTP والمواعيد وجدولة الموظفين والرسائل وإعادة المحاولة؛ ظهر خطأ 500 عند حد تحقق الهاتف، وإنشاء موعد من مركز مختلف، وزر no-show غير المتوافق مع حالة check-in؛ بقي العرض التفاعلي مفتوحاً |
| أوامر العمل والورشة، 7 صفحات | `WorkOrdersCrudTest`, `WorkOrderInventoryTest`, status/labor/board tests | تمت مراجعة المسارات الأساسية والطفل/المخزون ولوحة الورشة وعزلها وفرزها؛ ظهرت فجوات child binding واتساق حركة القطع، وفرز التعادل غير مكتمل؛ العرض البصري والتفاصيل مفتوحة |
| الخدمات والأقسام، صفحتان | توجد تغطية متفرقة؛ لا اختبار مباشر لتفويض مفتاح فحوص المركبات أو تقييد عناصر الحزم | راجعت صفحة الخدمات وحزمها والأقسام وعمليات التبديل/الحفظ؛ إعداد عام قابل للتغيير من مستخدم تطبيق وربط مراجع غير مقيدة بالمستأجر/المركز موثقان؛ التفاعل البصري مفتوح |
| لوحة التطبيق، صفحة رئيسية مع مكونات تبويب | لم أجد اختباراً مباشراً لتجميع مؤشرات Dashboard | راجعت استعلامات المراكز والمدفوعات والمخزون والخدمات وأفعال التنقل والتحديث؛ لا نتيجة جديدة موثقة في هذا المرور؛ فتح التبويبات والبيانات المصادق عليها بصرياً ما زال غير متحقق |
| عروض الأسعار، 4 صفحات | `QuotesTest`, `QuoteParityFeaturesTest` | راجعت تدفق التوقيع والطباعة؛ به نتيجتان أعلاه |
| الفواتير والمدفوعات، 5 صفحات | تحويل/حالة/ربط دفع/تقارير وفوترة تلقائية | فحصت الإصدار والتحصيل والآجل والمرتجعات؛ أخطاء التفويض والحذف والسباق موثقة؛ التسويات وبقية تفاعل الواجهات ما زالت مفتوحة |
| المخزون، 13 صفحة | `InventoryServiceTest`, `PartsCrudTest`, `SecurityRegressionTest` | راجعت الحركات والتحويلات والإعدادات؛ ثغرة العزل وزيادة كمية الاستلام وحذف بند تحويل آخر وفجوات تفويض وحدات/فئات المخزون موثقة |
| المشتريات، 13 صفحة | توجد اختبارات عزل مورد/مشتريات عامة و`PurchaseDebitNoteTest`؛ لا توجد تغطية مباشرة لإنشاء/ترحيل PO/GRN | راجعت PO/GRN والفواتير والمرتجعات؛ ظهرت فجوات صلاحية وربط وتجاوز كميات ودفعات أعلاه |
| الموارد البشرية والبوابة، 18 صفحة | سياسة/عزل مستأجر لبعض الكيانات و`EmployeeDocumentSecurityTest` | راجعت إعدادات HR والرواتب والبوابة والعقود؛ غياب صلاحيات إعداد HR وظهور المسودات ومخاطر عزل الموظف/المركز ورصيد الإجازة وتسريب جسم طلب إنشاء الموظف إلى السجلات موثقة؛ بقية المسارات والتفاعل البصري مفتوحة |
| الإعدادات والفروع والمستخدمون، 32 صفحة | اختبارات إعدادات موقع/توقيعات/تفضيلات/صلاحيات متفرقة | راجعت معاملات المنشأة/المراكز وإعدادات الشركة والفوترة الذاتية ومعالج ZATCA والموقع العام وإدارة مستخدمي المستأجر؛ صلاحيات مالية وإعدادات غير محمية ونجاح إنتاجي وهمي ومخاطر رفع وتجاوز حماية super_admin وإلغاء الاشتراك من مستخدم عادي موثقة؛ صفحات كتالوج المركبات وتصنيفات المعاملات تفتقد فحص صلاحية الإدارة؛ بقية النماذج ومطابقة الصلاحيات والتفاعل البصري مفتوحة |
| النظام، 62 صفحة | اختبارات لوحات/نسخ احتياطي/خطط/تكاملات/تحليلات متفرقة | راجعت الباقات/الرموز والاشتراكات ومستخدمي النظام والإعلانات والتكاملات ولوحة التحليلات وواجهات إعداد 2FA؛ ثغرات الدور وكشف الأسرار وأخطاء التحليلات وعدم فرض 2FA عند دخول المدير موثقة، وبقية صفحات النظام وتفاعلاتها مفتوحة |
| التقارير/الترجمة/المساعدة/إشعارات وخدمات أخرى، 16 صفحة تقريباً | تغطية متفاوتة: تقارير المدفوعات، الترجمة، مركز المساعدة، الإشعارات | راجعت صفحة تقارير المدفوعات ومرشحاتها وجدولها وتصدير CSV، وصفحة تقارير النظام وتنزيلاتها، وصفحتي تحليلات النظام؛ غياب صلاحية قراءة المدفوعات وتسريب قائمة العملاء عبر المراكز وحقن CSV وخطأ churn والتقديرات التاريخية موثقة. راجعت مركز المساعدة وإشعارات المستخدم وعزلها؛ ظهر سباق عند فتح إشعار يملك action_url بين طلب تعليمه مقروءاً والتنقل. بقية التقارير/الترجمة والعرض البصري والتفاعلي لم تكتمل |

> أعداد المجموعات تقريبية حيث إن `Pages/` يحتوي صفحات Inertia ومكوّنات/نوافذ مستقلة معاً. المراجعة الشاملة لم تكتمل بعد؛ سأضيف تدقيق بقية المجموعات إلى هذا التقرير مع الأدلة، ولا أعتبر المجموعات غير المراجعة سليمة لمجرد عدم ظهور أخطاء فيها.

## ملحق جرد كل ملفات Vue تحت Pages وربطها بخادم Laravel

هذا الملحق يحقق جرداً ملفاً ملفاً، لكنه لا يدعي أن فتح كل واجهة بصرياً قد تم. `صفحة Inertia` تعني وجود استدعاء PHP ثابت بالاسم؛ `ملف مساعد` يعني أنه ليس هدف Inertia ثابتاً، وغالباً يكون مكوّناً/نافذة/جزء طباعة. المسارات مأخوذة من `php artisan route:list --json` (648 مساراً).

| الملف | النوع الثابت | Controller::method | المسارات المرتبطة |
|---|---|---|---|
| `resources/js/Pages/App/Profile/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ProfileController::index` | `GET|HEAD app/profile` |
| `resources/js/Pages/App/Security/TwoFactorChallenge.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\TwoFactorAuthenticatedSessionController::challenge` | `GET|HEAD app/2fa/challenge` |
| `resources/js/Pages/App/Security/TwoFactorSetup.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\TwoFactorController::setup` | **لا يوجد GET route مسجل؛ موضح في النتيجة 11** |
| `resources/js/Pages/Appointments/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\AppointmentController::index` | `GET|HEAD app/appointments` |
| `resources/js/Pages/Auth/ConfirmPassword.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\ConfirmablePasswordController::show` | `GET|HEAD confirm-password` |
| `resources/js/Pages/Auth/ForgotPassword.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\PasswordResetLinkController::create` | `GET|HEAD forgot-password` |
| `resources/js/Pages/Auth/Login.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\AuthenticatedSessionController::create` | `GET|HEAD admin/login`<br>`GET|HEAD login` |
| `resources/js/Pages/Auth/Register.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\RegisteredUserController::create` | `GET|HEAD register` |
| `resources/js/Pages/Auth/ResetPassword.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\NewPasswordController::create` | `GET|HEAD reset-password/{token}` |
| `resources/js/Pages/Auth/SetPassword.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\SetPasswordController::show` | `GET|HEAD invitations/accept/{user}` |
| `resources/js/Pages/Auth/VerifyEmail.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Auth\EmailVerificationPromptController::__invoke` | لم يطابق route:list آلياً |
| `resources/js/Pages/Communications/AppointmentMessages/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\AppointmentMessageDeliveryController::index` | `GET|HEAD app/communications/appointment-messages` |
| `resources/js/Pages/Customers/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CustomerController::index` | `GET|HEAD app/customers` |
| `resources/js/Pages/Customers/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CustomerController::print` | `GET|HEAD app/customers/print` |
| `resources/js/Pages/Customers/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CustomerController::show` | `GET|HEAD app/customers/{customer}` |
| `resources/js/Pages/Dashboard.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\DashboardController::index` | `GET|HEAD dashboard` |
| `resources/js/Pages/Departments/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\DepartmentController::index` | `GET|HEAD app/departments` |
| `resources/js/Pages/EmployeePortal/Attendance.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::attendance` | `GET|HEAD app/my/attendance` |
| `resources/js/Pages/EmployeePortal/Dashboard.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::dashboard` | `GET|HEAD app/my` |
| `resources/js/Pages/EmployeePortal/Leaves.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::leaves` | `GET|HEAD app/my/leaves` |
| `resources/js/Pages/EmployeePortal/PayslipShow.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::showPayslip` | `GET|HEAD app/my/payslips/{payslip}` |
| `resources/js/Pages/EmployeePortal/Payslips.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::payslips` | `GET|HEAD app/my/payslips` |
| `resources/js/Pages/EmployeePortal/Profile.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\EmployeePortalController::profile` | `GET|HEAD app/my/profile` |
| `resources/js/Pages/HR/Attendance/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\AttendanceController::index` | `GET|HEAD app/hr/attendance` |
| `resources/js/Pages/HR/Attendance/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\AttendanceController::print` | `GET|HEAD app/hr/attendance/print` |
| `resources/js/Pages/HR/Employees/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\EmployeeController::index` | `GET|HEAD app/hr/employees` |
| `resources/js/Pages/HR/Employees/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\EmployeeController::print` | `GET|HEAD app/hr/employees/print` |
| `resources/js/Pages/HR/Employees/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\EmployeeController::show` | `GET|HEAD app/hr/employees/{employee}` |
| `resources/js/Pages/HR/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\HRController::index` | `GET|HEAD app/hr` |
| `resources/js/Pages/HR/Payroll/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\PayrollController::index` | `GET|HEAD app/hr/payroll` |
| `resources/js/Pages/HR/Payroll/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\PayrollController::print` | `GET|HEAD app/hr/payroll/{payrollRun}/print` |
| `resources/js/Pages/HR/Payroll/PrintPaySlip.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\PayrollController::printPaySlip` | `GET|HEAD app/hr/payroll-items/{payrollItem}/print` |
| `resources/js/Pages/HR/Payroll/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\PayrollController::show` | `GET|HEAD app/hr/payroll/{payrollRun}` |
| `resources/js/Pages/HR/Payroll/Tabs/OtherPaymentsTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/HR/Settings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HR\SettingsController::index` | `GET|HEAD app/hr/settings` |
| `resources/js/Pages/Help/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\HelpController::index` | `GET|HEAD app/help` |
| `resources/js/Pages/Inventory/Hub.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventorySettingsController::hub` | `GET|HEAD app/inventory` |
| `resources/js/Pages/Inventory/Moves/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventoryMoveController::index` | `GET|HEAD app/inventory/moves` |
| `resources/js/Pages/Inventory/Parts/CreateModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Inventory/Parts/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PartsController::index` | `GET|HEAD app/inventory/parts` |
| `resources/js/Pages/Inventory/Parts/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PartsController::show` | `GET|HEAD app/inventory/parts/{part}` |
| `resources/js/Pages/Inventory/Parts/StockModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Inventory/Parts/WarehouseStockModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Inventory/Settings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventorySettingsController::index` | `GET|HEAD app/inventory/settings` |
| `resources/js/Pages/Inventory/Stock/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventoryBalanceController::index` | `GET|HEAD app/inventory/stock` |
| `resources/js/Pages/Inventory/Transfers/CreateModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Inventory/Transfers/Form.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventoryTransfersController::create` | `GET|HEAD app/inventory/transfers/create` |
| `resources/js/Pages/Inventory/Transfers/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventoryTransfersController::index` | `GET|HEAD app/inventory/transfers` |
| `resources/js/Pages/Inventory/Transfers/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InventoryTransfersController::show` | `GET|HEAD app/inventory/transfers/{transfer}` |
| `resources/js/Pages/Invoices/Hub.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InvoicesController::hub` | `GET|HEAD app/invoices` |
| `resources/js/Pages/Invoices/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InvoicesController::print` | `GET|HEAD app/invoices/{invoice}/print` |
| `resources/js/Pages/Invoices/Purchasing/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InvoicesController::purchasesIndex` | `GET|HEAD app/invoices/purchases` |
| `resources/js/Pages/Invoices/Sales/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InvoicesController::salesIndex` | `GET|HEAD app/invoices/sales` |
| `resources/js/Pages/Invoices/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\InvoicesController::show` | `GET|HEAD app/invoices/{invoice}` |
| `resources/js/Pages/Notifications/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\NotificationController::index` | `GET|HEAD app/notifications` |
| `resources/js/Pages/Profile/Edit.vue` | Inertia فعلي في PHP | `App\Http\Controllers\ProfileController::edit` | لم يطابق route:list آلياً |
| `resources/js/Pages/Profile/Partials/DeleteUserForm.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Profile/Partials/UpdatePasswordForm.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Public/LandingPreview.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\PublicLandingController::preview` | `GET|HEAD /`<br>`GET|HEAD landing-preview` |
| `resources/js/Pages/Public/Portal/Appointments/Confirmation.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalAppointmentController::showConfirmation` | `GET|HEAD {tenant}/portal/appointment/{id}` |
| `resources/js/Pages/Public/Portal/Appointments/New.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalAppointmentController::showNew` | `GET|HEAD {tenant}/portal/appointments/new` |
| `resources/js/Pages/Public/Portal/Appointments/Reschedule.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalAppointmentActionsController::showReschedule` | `GET|HEAD {tenant}/portal/appointment/{id}/edit` |
| `resources/js/Pages/Public/Portal/Dashboard.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalController::dashboard` | `GET|HEAD {tenant}/portal/dashboard` |
| `resources/js/Pages/Public/Portal/Login.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalAuthController::showLogin` | `GET|HEAD {tenant}/portal/login` |
| `resources/js/Pages/Public/Portal/Verify.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\CustomerPortalAuthController::showVerify` | `GET|HEAD {tenant}/portal/verify` |
| `resources/js/Pages/Public/Site/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\TenantSiteController::show` | `GET|HEAD {tenant}` |
| `resources/js/Pages/Purchasing/GRN/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\GoodsReceivedNotesController::show` | `GET|HEAD app/purchasing/grn/{goodsReceivedNote}` |
| `resources/js/Pages/Purchasing/Hub.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchasingHubController::index` | `GET|HEAD app/purchasing` |
| `resources/js/Pages/Purchasing/Invoices/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchasingInvoicesController::purchasesIndex`<br>`App\Http\Controllers\App\PurchaseInvoicesController::index` | `GET|HEAD app/purchasing/purchases` |
| `resources/js/Pages/Purchasing/Invoices/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseInvoicesController::print` | `GET|HEAD app/invoices/purchases/{purchaseInvoice}/print` |
| `resources/js/Pages/Purchasing/Invoices/ReturnPrint.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseReturnsController::printReturn` | `GET|HEAD app/invoices/purchases/returns/{purchaseReturnInvoice}/print` |
| `resources/js/Pages/Purchasing/Invoices/ReturnShow.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseReturnsController::showReturn` | `GET|HEAD app/invoices/purchases/returns/{purchaseReturnInvoice}` |
| `resources/js/Pages/Purchasing/Invoices/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseInvoicesController::show` | `GET|HEAD app/invoices/purchases/{purchaseInvoice}` |
| `resources/js/Pages/Purchasing/Orders/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseOrdersController::index` | `GET|HEAD app/purchasing/orders` |
| `resources/js/Pages/Purchasing/Orders/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchaseOrdersController::show` | `GET|HEAD app/purchasing/orders/{purchaseOrder}` |
| `resources/js/Pages/Purchasing/Sales/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PurchasingInvoicesController::salesIndex` | `GET|HEAD app/purchasing/sales` |
| `resources/js/Pages/Purchasing/Suppliers/CreateModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Purchasing/Suppliers/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\SuppliersController::index` | `GET|HEAD app/purchasing/suppliers` |
| `resources/js/Pages/Purchasing/Suppliers/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\SuppliersController::show` | `GET|HEAD app/purchasing/suppliers/{supplier}` |
| `resources/js/Pages/Quotes/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\Quotes\QuoteController::index` | `GET|HEAD app/quotes` |
| `resources/js/Pages/Quotes/Print.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\Quotes\QuotePrintController::print` | `GET|HEAD app/quotes/{quote}/print` |
| `resources/js/Pages/Quotes/PublicShow.vue` | Inertia فعلي في PHP | `App\Http\Controllers\Public\PublicQuoteController::show` | `GET|HEAD view/quote/{uuid}` |
| `resources/js/Pages/Quotes/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\Quotes\QuoteController::show` | `GET|HEAD app/quotes/{quote}` |
| `resources/js/Pages/Reports/Hub.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ReportsController::hub` | `GET|HEAD app/reports` |
| `resources/js/Pages/Reports/Payments.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ReportsController::payments` | `GET|HEAD app/reports/payments` |
| `resources/js/Pages/Services/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ServiceController::index` | `GET|HEAD app/services` |
| `resources/js/Pages/Settings/Billing/Components/PaymentMethodsList.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Billing/Components/ReceiptsList.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Billing/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\BillingController::index` | `GET|HEAD app/settings/billing` |
| `resources/js/Pages/Settings/Branches/BranchFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Branches/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\BranchesController::index` | `GET|HEAD app/settings/branches` |
| `resources/js/Pages/Settings/Centers/ServiceBays.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ServiceBayController::index` | `GET|HEAD app/service-bays`<br>`GET|HEAD app/settings/service-bays` |
| `resources/js/Pages/Settings/Centers/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CenterSettingsController::index` | `GET|HEAD app/settings/centers/{center}` |
| `resources/js/Pages/Settings/Company/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CompanyProfileController::index` | `GET|HEAD app/settings/company` |
| `resources/js/Pages/Settings/Company/Modals/CompanyTransactionModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Company/SubscriptionFailed.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CompanySubscriptionController::callback` | `GET|POST|HEAD app/settings/billing/callback/{payment}`<br>`GET|POST|HEAD app/settings/company/subscription/callback/{payment}` |
| `resources/js/Pages/Settings/Company/SubscriptionSuccess.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\CompanySubscriptionController::callback` | `GET|POST|HEAD app/settings/billing/callback/{payment}`<br>`GET|POST|HEAD app/settings/company/subscription/callback/{payment}` |
| `resources/js/Pages/Settings/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\SettingsController::index` | `GET|HEAD app/settings` |
| `resources/js/Pages/Settings/Integrations.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\SettingsController::integrations` | `GET|HEAD app/settings/integrations` |
| `resources/js/Pages/Settings/Partials/UserManagementTabs.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Print/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\SystemSettingsController::printSettings` | `GET|HEAD app/settings/print` |
| `resources/js/Pages/Settings/Print/Modals/SignatureModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Print/Modals/TermEditModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Print/Modals/TermsListModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Print/Modals/TermsModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Print/Signatures/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\PrintSettings\Signatures\IndexSignaturesController::__invoke` | لم يطابق route:list آلياً |
| `resources/js/Pages/Settings/Roles/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\RoleController::index` | `GET|HEAD app/settings/roles` |
| `resources/js/Pages/Settings/Roles/RoleFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/System/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\VehicleConditionItemController::index`<br>`App\Http\Controllers\App\SystemSettingsController::index`<br>`App\Http\Controllers\App\IncomeCategoryController::index`<br>`App\Http\Controllers\App\VehicleModelController::index`<br>`App\Http\Controllers\App\VehicleColorController::index`<br>`App\Http\Controllers\App\VehicleMakeController::index` | `GET|HEAD app/settings/condition-items`<br>`GET|HEAD app/settings/system`<br>`GET|HEAD app/settings/income-categories`<br>`GET|HEAD app/settings/models`<br>`GET|HEAD app/settings/colors`<br>`GET|HEAD app/settings/makes` |
| `resources/js/Pages/Settings/System/Modals/ColorFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/System/Modals/ConditionItemFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/System/Modals/IncomeCategoryFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/System/Modals/MakeFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/System/Modals/ModelFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Users/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\UserController::index` | `GET|HEAD app/settings/users` |
| `resources/js/Pages/Settings/Users/UserFormModal.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Settings/Website.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\TenantWebsiteSettingsController::edit`<br>`App\Http\Controllers\App\SettingsController::website` | `GET|HEAD app/settings/website/edit` |
| `resources/js/Pages/Settings/Zatca/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\ZatcaOnboardingController::index` | `GET|HEAD app/settings/zatca` |
| `resources/js/Pages/System/AdminUsers/ActivityLog.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AdminUsersController::activityLog` | `GET|HEAD system/activity-log` |
| `resources/js/Pages/System/AdminUsers/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AdminUsersController::index` | `GET|HEAD system/admin-users` |
| `resources/js/Pages/System/Analytics/Kpis.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AnalyticsController::kpis` | `GET|HEAD system/analytics/kpis` |
| `resources/js/Pages/System/Analytics/Reports.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Announcements/Create.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AnnouncementsController::create` | `GET|HEAD system/announcements/create` |
| `resources/js/Pages/System/Announcements/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AnnouncementsController::index` | `GET|HEAD system/announcements` |
| `resources/js/Pages/System/Announcements/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AnnouncementsController::show` | `GET|HEAD system/announcements/{announcement}` |
| `resources/js/Pages/System/Backups/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\BackupController::index` | `GET|HEAD system/backups` |
| `resources/js/Pages/System/BankTransferReceipts/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\BankTransferReceiptsController::index` | `GET|HEAD system/bank-transfer-receipts` |
| `resources/js/Pages/System/BankTransferReceipts/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\BankTransferReceiptsController::show` | `GET|HEAD system/bank-transfer-receipts/{receipt}` |
| `resources/js/Pages/System/Communication/Templates/Edit.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\CommunicationTemplatesController::edit` | `GET|HEAD system/communication/templates/{template}/edit` |
| `resources/js/Pages/System/Communication/Templates/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\CommunicationTemplatesController::index` | `GET|HEAD system/communication/templates` |
| `resources/js/Pages/System/ContactMessages/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\ContactMessageController::index` | `GET|HEAD system/settings/contact-messages` |
| `resources/js/Pages/System/Credits/SmsBalances.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SmsCreditsController::balances` | `GET|HEAD system/sms/balances` |
| `resources/js/Pages/System/Credits/SmsPackages.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SmsCreditsController::packages` | `GET|HEAD system/sms/packages` |
| `resources/js/Pages/System/Credits/SmsPurchases.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SmsCreditsController::purchases` | `GET|HEAD system/sms/purchases` |
| `resources/js/Pages/System/Credits/SmsUsage.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SmsCreditsController::usage` | `GET|HEAD system/sms/usage` |
| `resources/js/Pages/System/Credits/WhatsappBalances.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\WhatsappCreditsController::balances` | `GET|HEAD system/whatsapp/balances` |
| `resources/js/Pages/System/Credits/WhatsappPackages.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\WhatsappCreditsController::packages` | `GET|HEAD system/whatsapp/packages` |
| `resources/js/Pages/System/Credits/WhatsappPurchases.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\WhatsappCreditsController::purchases` | `GET|HEAD system/whatsapp/purchases` |
| `resources/js/Pages/System/Credits/WhatsappUsage.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\WhatsappCreditsController::usage` | `GET|HEAD system/whatsapp/usage` |
| `resources/js/Pages/System/Dashboard.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SystemDashboardController::index` | `GET|HEAD system` |
| `resources/js/Pages/System/Developer/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\DeveloperController::index` | `GET|HEAD system/developer` |
| `resources/js/Pages/System/Help/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\HelpSettingsController::index` | `GET|HEAD system/settings/help` |
| `resources/js/Pages/System/Integrations/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\IntegrationsController::index` | `GET|HEAD system/integrations` |
| `resources/js/Pages/System/Integrations/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\IntegrationsController::show` | `GET|HEAD system/integrations/{integration}` |
| `resources/js/Pages/System/Invoices/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SubscriptionInvoicesController::index` | `GET|HEAD system/invoices` |
| `resources/js/Pages/System/Invoices/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SubscriptionInvoicesController::show` | `GET|HEAD system/invoices/{invoice}` |
| `resources/js/Pages/System/Notifications/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\AdminNotificationController::index` | `GET|HEAD system/notifications` |
| `resources/js/Pages/System/Payment/Checkout.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PaymentController::checkout` | `GET|HEAD system/payment/checkout/{subscription}` |
| `resources/js/Pages/System/Payment/Failed.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PaymentController::failed` | `GET|HEAD system/payment/failed/{payment}` |
| `resources/js/Pages/System/Payment/Success.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PaymentController::success` | `GET|HEAD system/payment/success/{payment}` |
| `resources/js/Pages/System/Plans/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PlansController::index` | `GET|HEAD system/plans` |
| `resources/js/Pages/System/Profile/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\ProfileController::index` | `GET|HEAD system/profile` |
| `resources/js/Pages/System/PromoCodes/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PromoCodesController::index` | `GET|HEAD system/promo-codes` |
| `resources/js/Pages/System/Security/TwoFactorChallenge.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\TwoFactorController::challenge` | `GET|HEAD 2fa/challenge` |
| `resources/js/Pages/System/Security/TwoFactorSetup.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\TwoFactorController::setup` | `GET|HEAD system/security/2fa` |
| `resources/js/Pages/System/Settings/CompanySettings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\CompanySettingsController::index` | `GET|HEAD system/settings/company` |
| `resources/js/Pages/System/Settings/DunningLog.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\DunningController::log` | `GET|HEAD system/billing/dunning-log` |
| `resources/js/Pages/System/Settings/DunningSettings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\DunningController::settings` | `GET|HEAD system/settings/dunning` |
| `resources/js/Pages/System/Settings/GeneralSettings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\GeneralSettingsController::index` | `GET|HEAD system/settings/general` |
| `resources/js/Pages/System/Settings/PaymentSettings.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\PaymentSettingsController::index` | `GET|HEAD system/settings/payment` |
| `resources/js/Pages/System/Settings/TenantDunning.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\DunningController::tenantConfig` | `GET|HEAD system/tenants/{tenant}/dunning` |
| `resources/js/Pages/System/Subscriptions/Create.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SubscriptionsController::create` | `GET|HEAD system/subscriptions/create` |
| `resources/js/Pages/System/Subscriptions/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SubscriptionsController::index` | `GET|HEAD system/subscriptions` |
| `resources/js/Pages/System/Subscriptions/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\SubscriptionsController::show` | `GET|HEAD system/subscriptions/{subscription}` |
| `resources/js/Pages/System/Tenants/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\TenantsController::index` | `GET|HEAD app/tenants`<br>`GET|HEAD system/tenants` |
| `resources/js/Pages/System/Tenants/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\TenantsController::show` | `GET|HEAD app/tenants/{tenant}`<br>`GET|HEAD system/tenants/{tenant}` |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantAnalyticsCards.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantHeader.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantHealthScore.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantModals.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantQuickActions.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Tenants/ShowComponents/TenantTabs.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\System\WebsiteSettingsController::index` | `GET|HEAD system/settings/website` |
| `resources/js/Pages/System/Website/Tabs/AdvancedTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/GeneralTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/IntegrationsTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/LandingTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/ListsTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/PagesTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/System/Website/Tabs/SeoSocialTab.vue` | لا يظهر كهدف ثابت لـ Inertia | — | ملف مساعد أو اسم ديناميكي؛ يحتاج تأكيداً يدوياً |
| `resources/js/Pages/Vehicles/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\VehicleController::index` | `GET|HEAD app/vehicles` |
| `resources/js/Pages/Vehicles/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\VehicleController::show` | `GET|HEAD app/vehicles/{vehicle}` |
| `resources/js/Pages/WorkOrders/Index.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderController::index` | `GET|HEAD app/work-orders` |
| `resources/js/Pages/WorkOrders/Print/Condition.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderPrintController::printCondition` | `GET|HEAD app/work-orders/{workOrder}/print/condition` |
| `resources/js/Pages/WorkOrders/Print/Payments.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderPrintController::printPayments` | `GET|HEAD app/work-orders/{workOrder}/print/payments` |
| `resources/js/Pages/WorkOrders/Print/Proforma.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderPrintController::printProforma` | `GET|HEAD app/work-orders/{workOrder}/print/proforma` |
| `resources/js/Pages/WorkOrders/Print/Services.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderPrintController::printServices` | `GET|HEAD app/work-orders/{workOrder}/print/services` |
| `resources/js/Pages/WorkOrders/Show.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkOrders\WorkOrderController::show` | `GET|HEAD app/work-orders/{workOrder}` |
| `resources/js/Pages/Workshop/Board.vue` | Inertia فعلي في PHP | `App\Http\Controllers\App\WorkshopBoardController::index` | `GET|HEAD app/workshop` |

### 9) **عالٍ — مسارات تحصيل الفواتير ترفض الفاتورة الصادرة**

**الأثر:** تُصدر خدمة إنشاء الفاتورة فاتورة أمر العمل بحالة `valid`. لكن تسجيل الدفعة والدفع الكامل يطلبان قدرة `update`، وسياسة تحديث الفاتورة لا تسمح إلا بحالة `draft`. توجد في السياسة قدرة منفصلة `recordPayment` مخصصة لهذا الغرض، لكنها غير مستخدمة من المتحكم. النتيجة: محاولة الدفع عبر هذين المسارين على الفاتورة الصادرة تُرفض، مع أن الفاتورة غير مدفوعة.

**الدليل:**
- `app/Services/InvoiceService.php:253-281`: `issueInvoice()` ينقل الحالة من `draft` إلى `valid`.
- `app/Http/Controllers/App/PaymentsController.php:23-26,71-74`: `store()` و`payFull()` يطلبان `authorize('update', $invoice)`.
- `app/Policies/InvoicePolicy.php:45-53`: `update` يتطلب `status === 'draft'`.
- `app/Policies/InvoicePolicy.php:72-83`: توجد قدرة `recordPayment` تسمح بالدفع حسب صلاحية `purchasing.payments.manage` والحالة غير المدفوعة.
- الاختبارات الحالية تغطي `PaymentService` وحسابات الحالة، ولا تغطي HTTP endpoints في `PaymentsController`.

**الإجراء:** استخدم قدرة `recordPayment` في مساري التسجيل والدفع الكامل، وأضف اختبار Feature لفاتورة `valid` غير مدفوعة، وفاتورة مدفوعة، ومستخدم بلا صلاحية الدفع. مسار `destroy()` يمنع حذف دفعات الفواتير الصادرة عملياً: يطلب `update` على الفاتورة (المتاح فقط للمسودات) رغم وجود `PaymentPolicy::delete`. كما أن التحصيل المتزامن لا يقفل الفاتورة ولا يعيد التحقق من الرصيد (النتيجة 23).

### 10) **عالٍ — إدخالات أمر الشراء تسمح بمراجع من مستأجر آخر**

**الأثر:** قواعد إنشاء أمر الشراء تتحقق من أن المورد والمستودع والقطع موجودة في قاعدة البيانات فقط. ثم الخدمة تربط هذه المعرفات بأمر شراء يحمل `tenant_id` و`center_id` للمستخدم الحالي. لا توجد مطابقة للمستأجر/المركز بين أمر الشراء والموارد المرتبطة. يستطيع مستخدم مخوّل إنشاء أمر شراء بربطه بمورد أو مستودع أو قطعة من مستأجر آخر، ويستمر الربط عند إنشاء عناصر الأمر.

**الدليل:**
- `app/Http/Controllers/App/PurchaseOrdersController.php:87-106`: قواعد `exists:...` غير مقيدة بالمستأجر/المركز ثم تُملأ هوية الأمر من المستخدم.
- `app/Services/Purchasing/PurchasingService.php:34-52`: تُحفظ معرفات المورد والمستودع كما وصلت.
- `app/Services/Purchasing/PurchasingService.php:68-92`: يجلب القطعة بـ `Part::find()`، لكنه ينشئ عنصر الأمر باستخدام `part_id` المرسل حتى لو لم تعد القطعة متاحة عبر نطاق المستخدم.

**الإجراء:** استعمل قواعد `Rule::exists` مقيدة بمستأجر/مركز الطلب، وتحقق داخل الخدمة من تبعية كل علاقة للطلب. راعِ مشاركة الكتالوج بين الفروع فقط حيث يسمح بها النظام، ولا تسمح أبداً بربط موارد من مستأجر مختلف.

## تحديث حالة الشجرة والمراجعة

في بداية المرحلة الأولى كانت تغييرات عروض الأسعار والطباعة غير مثبتة؛ أصبحت حالياً ضمن `HEAD 43465eb7`، وأعدت التحقق من بقاء نتائج التوقيع والطباعة على هذا الرأس. الملفات غير المثبتة في المساحة الآن تخص التوثيق وملفات البناء وتقارير التدقيق، لا تغييرات التطبيق التي راجعتها.

تمت مطابقة **151 صفحة Inertia** مع ملفاتها وطرق Controllers والمسارات حيث أمكن، وأُضيف ملحق ملفي لـ **190 ملف Vue** و648 مسار Laravel. وجود مطابقة ساكنة لا يثبت أن الصفحة تعمل بصرياً أو أن أفعالها ناجحة؛ لذلك فحص الواجهات واختبار التفاعل ما زال مطلوباً لكل مجموعة، كما يوضح جدول التغطية أعلاه.

### 11) **عالٍ — مسارات 2FA مفقودة أو غير محمية كما ينبغي**

**الأثر:** توجد مشكلتان متعاكستان عند فرض 2FA على المستأجر:

1. طلب صفحة محمية داخل مجموعة `/app/*` لمستخدم لم يكمل الإعداد يصل إلى `EnsureTwoFactorEnabled`، الذي يحاول التحويل إلى `app.security.2fa.setup`. هذا الاسم غير موجود في جدول المسارات الحالي، فيفشل إنشاء الرابط ويُرجح أن يرجع الطلب 500 بدلاً من عرض شاشة الإعداد. توجد دالة `setup()` وصفحة Vue، لكن لا يوجد GET route يربطهما.
2. المسار الفعلي للوحة الرئيسية `/dashboard`، ومجموعة `/app/profile` و`/app/my/*`، لا تمر بوسيط `EnsureTwoFactorEnabled`. المستخدم غير المؤكد يستطيع الوصول إليها بعد تسجيل الدخول؛ ويعرض Dashboard إحصاءات التشغيل والماليات. تسجيل الدخول لا يرسل لتحدي 2FA إلا المستخدم الذي سبق أن أكد الإعداد.

وهذا يجعل تطبيق 2FA الإلزامي معطلاً في واجهات، ومتجاوزاً في واجهات أخرى.

**الدليل:**
- `app/Http/Middleware/EnsureTwoFactorEnabled.php:25-40`: يحاول `redirect()->route('app.security.2fa.setup')` عند تفعيل فرض 2FA وعدم التأكيد.
- `php artisan route:list --name=app.security.2fa.setup`: لا توجد مطابقة. وبالمقابل `app/Http/Controllers/App/TwoFactorController.php:28-35` يملك دالة `setup()` تعرض `App/Security/TwoFactorSetup`، لكن لا يوجد لها GET route في `routes/web.php`.
- `routes/web.php:299-301`: `/dashboard` يطبق `auth`, `verified`, `tenant.active`, `center.context` فقط.
- `routes/web.php:308-329`: المجموعة الخارجية `/app` وبوابة الموظف `/app/my/*` لا تضيف وسيط 2FA. المسارات الأساسية الأخرى تبدأ في المجموعة المحمية عند `routes/web.php:334`.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php:54-61`: تحدي الدخول مشروط بوجود `two_factor_confirmed_at` مسبقاً.
- `app/Http/Controllers/App/DashboardController.php:20-48` يبدأ بجلب إحصاءات وبيانات مالية.
- لا يوجد اختبار Feature لحالة المستأجر الذي يفرض 2FA والمستخدم غير المؤكد.

**الإجراء:** سجّل GET route باسم `app.security.2fa.setup` إلى Controller الإعداد مع استثناءه المقصود من وسيط الفرض، وطبّق الحراسة على dashboard والمسارات الحساسة التي يجب أن تخضع لها. اختبر مستخدماً غير مؤكد مع enforcement مطلوب، ومستخدماً أكد 2FA، وموظفاً حسب السياسة المقصودة.

## تحقق المتصفح المحلي

في جلسة المتصفح الحالية بتاريخ 2026-10-27، ظهرت الصفحة العامة `/` وصفحتا `/login` و`/register` بمحتواهما. راجعت حقول صفحة التسجيل بصرياً دون إرسال بيانات؛ يظهر حقل Promo Code الموثق في النتيجة 64. الانتقال إلى `/dashboard` كضيف أعاد صفحة الدخول. هذا دليل على أن نقطة الدخول العامة وتسجيل الدخول تُعرضان حالياً في بيئة التطوير؛ لا يثبت سلامة الصفحات بعد المصادقة، إذ لم تكن جلسة دخول متاحة للمراجعة. كما أن `/app/dashboard` يعيد 404 لأن المسار الفعلي مسمى `dashboard` وعلى `/dashboard`.

يتسق فشل `Error403HomeLinkTest` مع انجراف اختبار قديم: يتوقع ملفاً غير موجود `resources/js/Pages/Errors/403.vue`، كما يتوقع النص `route('app.dashboard')` بينما اسم المسار الفعلي هو `dashboard`. لم أجد أن النظام الحالي يربط صفحة 403 المفقودة بمعالج استثناء؛ لذا الفشل المؤكد حتى الآن هو فشل اختبار عقد قديم، لا تعطل عام في صفحات التطبيق.

### 12) **عالٍ — قيد قسم الإدارة في تعديل الموظف لا يُنفذ بعد الحفظ**

**الأثر:** إجراء الحماية يقارن القسم الجديد مع `getOriginal()` بعد استدعاء `save()`. بعد الحفظ تكون قيمة الأصل قد تزامنت مع القيمة الجديدة، فيفشل شرط التغيير؛ لذلك يستطيع مستخدم يملك صلاحية تعديل الموظف إسناده لقسم الإدارة رغم أن الكود يهدف لحصر ذلك على `super_admin`.

**الدليل:** `app/Http/Controllers/App/HR/EmployeeController.php:316-322` يحفظ الموظف قبل المقارنة، و`checkManagementRestriction()` يرفض غير المدير العام في الأسطر 365-382.

**الإجراء:** احفظ `originalDepartmentId` قبل `fill/save`، نفّذ الفحص قبل الحفظ، واقيد القسم أيضاً بمستأجر/مركز الموظف. أضف اختباراً لمستخدم عادي عند النقل لقسم الإدارة.

### 13) **عالٍ — الحضور يمكن ربطه بموظف من مركز آخر ويؤثر في راتبه**

**الأثر:** إنشاء سجل الحضور يكتفي بوجود `employee_id` في قاعدة البيانات، لكنه يوسم السجل بمركز المستخدم الحالي. لاحقاً يحسب الرواتب الخصومات والساعات الإضافية باستعلامات على `employee_id` والتاريخ دون تقييد المركز. بذلك يمكن لسجل صُنع في مركز A التأثير على راتب موظف مركز B داخل المستأجر نفسه. كما لا يوجد تحقق صريح أن الموظف يتبع للمستأجر الحالي.

**الدليل:** `AttendanceController.php:157-175` يتحقق من `exists` العام ثم يسجل `current_center_id`؛ `Attendance` يستخدم `TenantScoped` فقط (`app/Models/HR/Attendance.php:15-18`)، وحساب الرواتب في `PayrollController.php:225-229,276-278` لا يقيّد `center_id`.

**الإجراء:** قيد الموظف بالمستأجر ومراكز المستخدم المسموحة، وتحقق من اتساق `tenant_id/center_id` داخل الخدمة، ثم اجعل استعلام الرواتب يقرأ سجلات المركز الصحيح (أو عرف صراحة سياسة مشاركة المركز). اختبر إنشاء حضور بموظف من مركز مختلف وتأثيره على مسير الرواتب.

### 14) **متوسط — صلاحيات المدفوعات الإضافية مربوطة بصلاحيات الموظف العامة**

**الأثر:** عمليات عرض/إنشاء/تعديل/حذف/اعتماد/صرف الدفعات الإضافية تفوض باستخدام `Employee` abilities (`viewAny` أو `update`) بدلاً من صلاحيات الدفعات. هذا يجعل التفويض غير مطابق لمسميات الصلاحيات المخصصة للرواتب، وقد يمنع أدواراً مخولة بالدفعات أو يتيح إدارة دفعات لمن يملك تعديل الموظفين فقط.

**الدليل:** `app/Http/Controllers/App/HR/OtherPaymentsController.php:17-20,38-59,69-124`؛ المسارات منفصلة في `routes/web.php:893-899`. لم أجد Policy مستقلة لـ `OtherPayment`.

**الإجراء:** أنشئ policy/abilities للدفعات الإضافية واربط كل فعل بصلاحية مناسبة، مع تحقق من ملكية الدفعة للموظف/المركز؛ اختبر أدواراً تحمل إدارة الموظفين فقط أو إدارة الرواتب فقط.

### 15) **عالٍ — نقاط التحقق السريع تكشف بيانات دون صلاحية عرض**

**الأثر:** أي مستخدم مسجل يستطيع استدعاء نقطتي فحص الجوال ولوحة المركبة داخل `/app` من دون استدعاء `CustomerPolicy::viewAny` أو `VehiclePolicy::viewAny`. عند وجود تطابق، يعيد endpoint بيانات تعريفية أكثر من مجرد نتيجة وجود: بيانات الاتصال والرقم الضريبي والعنوان للعميل، واسم العميل ورقم هاتفه ورقم الهيكل ومعلومات المركبة لنقطة اللوحة. هذا يسمح بتعداد أرقام/لوحات داخل بيانات المستأجر لحساب يفتقد صلاحية قراءة الوحدة. لم يثبت هذا تسريباً بين المستأجرين؛ النطاق هنا tenant الحالي.

**الدليل:**
- `routes/web.php:341-342` يسجل `/app/customers/check-phone` داخل مجموعة التطبيق المحمية بالمصادقة؛ `CustomerController::checkPhone()` في `app/Http/Controllers/App/CustomerController.php:321-382` لا يستدعي `authorize()` ويرجع حقول العميل في 367-378.
- `VehicleController::checkPlate()` في `app/Http/Controllers/App/VehicleController.php:350-387` لا يستدعي `authorize()`، ويبحث داخل tenant الحالي في 363-367، ثم يعيد بيانات العميل ورقم VIN وبيانات المركبة في 369-382.
- `CustomerPolicy::viewAny()` و`VehiclePolicy::viewAny()` يفرضان صلاحيات مستقلة للقراءة (`app/Policies/CustomerPolicy.php:16-19` و`app/Policies/VehiclePolicy.php:13-16`) لا يطبقها هذان المساران.
- لم يظهر rate limit خاص بهذين المسارين في تعريف الطريق، ولا تغطية اختبارية لصلاحيات المستخدم لهذه الاستجابات.

**الإجراء:** طبّق صلاحية قراءة الوحدة، أو اجعل الاستجابة الحد الأدنى اللازم لتفادي التكرار (مثل `exists` فقط من دون بيانات شخصية) مع تحديد معدل الطلبات. أضف اختباراً لمستخدم مصادق بلا صلاحيات العملاء/المركبات، واختباراً يثبت عدم إعادة PII.

### مراجعة العملاء والمركبات — بقية المسارات المفحوصة

- CRUD الأساسي يستدعي Policies على `index/show/store/update/destroy`، وقواعد رقم الهاتف في Form Requests مقيدة بالمستأجر والمركز.
- استيراد العملاء يقيد العملية بصلاحية إنشاء العميل، لكن تحقق الملف يعتمد امتداده الأصلي فقط، ولا يفرض حداً محلياً واضحاً لحجم الملف؛ كما يعيد نص الاستثناء الخام للعميل في الاستجابة 500 (`app/Http/Controllers/App/CustomerImportExportController.php:52-86`). صنفته مشكلة متوسطة في حدود مقاومة إساءة الاستخدام وتسريب تفاصيل الخطأ، لا كتعطل مثبت.
- الدمج ينفذ معاملة واحدة، ويتحقق من صلاحية تحديث الطرفين في المتحكم؛ ودمج المركبات يشترط تطابق المستأجر والعميل والصانع قبل نقل السجلات. لم أجد في هذا المرور خللاً مؤكداً في سلامة الدمج.
- هذه مراجعة لخادم المجموعة ومسارات القراءة/الكتابة الأساسية؛ لم يُفحص تفاعل كل modal وكل حالة طرفية في المتصفح لعدم توفر جلسة مستخدم.

### مراجعة أولية لبوابة العميل والمواعيد

أظهر فحص تعريف المسارات أن صفحات الحجز والإلغاء وإعادة الجدولة تقع خلف `customer.portal.session`، وأن إرسال OTP والتحقق والحجز والتوافر والإلغاء وإعادة الجدولة مرتبطة بمحددات معدل مسماة (`routes/web.php:196-281`). كما أن تسجيل الدخول يعيد رسالة موحدة سواء وُجد رقم الهاتف أم لا، ويجدد session ID بعد نجاح OTP (`CustomerPortalAuthController.php:115-135,176-198`). هذه مؤشرات حماية جيدة في الكود، لكنها لا تكفي لإغلاق تدقيق البوابة: ما زال مطلوباً فحص OTP service واستهلاك الرمز، ربط الموعد بالمستأجر والعميل عند كل فعل، ومراجعة التفاعل الفعلي للحالات والصفحات بعد توفر جلسة عميل اختبارية.

### 16) **متوسط — تجاوز حد محاولات OTP في البوابة يتحول إلى HTTP 500**

**الأثر:** خدمة OTP ترمي `OtpRateLimitedException` عندما يتجاوز رقم العميل 10 محاولات تحقق خلال 10 دقائق. متحكم `verifyOtp()` لا يمسك هذا الاستثناء، رغم أن المتحكم نفسه يمسكه في `requestOtp()`. لذلك عند بلوغ الحد عبر نوافذ/عناوين IP متعددة، يصل الاستثناء إلى معالج Laravel كخطأ خادم بدلاً من عرض رسالة انتظار مفهومة أو استجابة 429. يسبب ذلك شاشة خطأ للمستخدم عند تفعيل الحماية نفسها، وقد يُسجّل كـ 500 تشغيلي.

**الدليل:**
- `app/Services/CustomerPortal/CustomerPortalOtpService.php:467-479` يرمي `OtpRateLimitedException` بعد تجاوز حد الهاتف/المستأجر.
- `app/Http/Controllers/Public/CustomerPortalAuthController.php:155-173` يستدعي `verify()` مباشرة ولا يحتوي `try/catch`؛ وفي المقابل `requestOtp()` يمسك الاستثناء عند الأسطر 97-113.
- `tests/Feature/CustomerPortalOtpTest.php:458-495` يثبت أن الخدمة ترمي الاستثناء عند استنفاد الحد، بينما `CustomerPortalHttpTest` يغطي حالة حد طلب الرمز لا حد التحقق من خلال HTTP.

**الإجراء:** امسك الاستثناء في `verifyOtp()` واعرض الخطأ مع `retryAfterSeconds` أو أرجع 429 متسقة مع واجهة Inertia. أضف اختبار HTTP يضبط حد (tenant, phone) ويتأكد من عدم ظهور 500.

### نتيجة التحقق التفصيلي لبوابة OTP والمواعيد

لم يظهر في هذا المرور خلل واضح في عزل موعد العميل: قراءات وتعديلات وإعادة الجدولة تقيد الموعد بـ `tenant_id` و`customer_id`، ويعاد قفل الموعد والتحقق من المركز والخدمات والفترة والفتحة المتاحة داخل معاملة قبل تغيير الموعد (`CustomerPortalAppointmentActionService.php:188-275,357-435`). خدمة OTP تخزّن hash للرمز، تحدد المحاولات، تمنع إعادة استخدام الرمز ذرياً، وتفرض حدوداً لكل مستأجر/هاتف ولكل IP. الاستثناء الموثق في النتيجة 16 هو استجابة فشل UX/HTTP عند بلوغ الحد، وليس تجاوزاً للحماية.

### 17) **عالٍ — بوابة الموظف تعرض تفاصيل مسيرات الرواتب قبل اعتمادها**

**الأثر:** قائمة الكشوف والتفاصيل الشخصية تجلب عناصر الرواتب بحسب `employee_id` فقط، ولا تشترط أن تكون حالة المسير `approved` أو `paid`. إنشاء المسير يولد عناصر الرواتب مباشرةً وهو في الحالة `draft`؛ لذا يستطيع الموظف المرتبط بحسابه الاطلاع على راتب/بدلات/خصومات أولية قبل اعتمادها أو صرفها. يظهر ذلك في لوحة الموظف أيضاً عبر أحدث ثلاثة عناصر.

**الدليل:**
- `app/Http/Controllers/App/HR/PayrollController.php:163-219` ينشئ `PayrollItem` لكل موظف أثناء معالجة المسير دون تغيير حالة المسير من مسودة.
- `app/Http/Controllers/App/EmployeePortalController.php:72-76,230-244` يعرض العناصر الحديثة والقائمة دون قيد حالة المسير.
- `EmployeePortalController::showPayslip()` عند الأسطر 250-265 يتحقق من تطابق الموظف فقط ثم يحمّل `payrollRun` ويعرض العنصر، ولا يرفض `draft`.
- `PayrollItem` يعرّف accessor للحالة من المسير (`app/Models/HR/PayrollItem.php:80-83`)، لكن المتحكم لا يستخدمه كشرط.

**الإجراء:** لا تعرض عنصر المسير إلا بعد الحالة المعتمدة التي تحددها سياسة الرواتب (غالباً `paid` أو على الأقل `approved`)، وطبّق القيد نفسه على dashboard/list/show. اختبر أن مسودة راتب لا تظهر، وأن الكشف المعتمد/المصروف يظهر للموظف المقصود فقط.

### 18) **متوسط — رصيد الإجازة السنوية في لوحة الموظف يحسب كل أنواع الإجازات كأنها سنوية**

**الأثر:** `usedLeaves` يجمع أيام كل الإجازات المعتمدة للموظف في السنة، ثم يطرحها من رصيد سنوي ثابت قدره 21. هذا يخصم المرضية وغير المدفوعة وغيرها من رصيد السنوية، وقد ينتج رصيداً سالباً. التعليق في الكود يصف الحساب بأنه مبسط، لكن القيمة المعروضة للمستخدم لا تمثل حساباً صحيحاً لأنواع الإجازات.

**الدليل:** `app/Http/Controllers/App/EmployeePortalController.php:60-64,87-91`؛ أنواع الإجازات المقبولة تشمل أكثر من `annual` عند الأسطر 200-205، لذا الجمع لا يقتصر على السنوية.

**الإجراء:** اربط الرصيد بسياسة/إعدادات الإجازات لكل موظف، واجمع الأيام المعتمدة من نوع `annual` فقط مع معالجة الترحيل والاستحقاق النسبي إن كان مدعوماً. إن لم توجد سياسة حساب بعد، سمّ القيمة بوضوح كإجمالي أيام مستخدمة بدلاً من رصيد سنوي.

### 19) **منخفض إلى متوسط — مدخل شهر غير صالح في سجل الحضور قد ينتج 500**

**الأثر:** صفحة حضور الموظف تقرأ `month` من query string ثم تمرره مباشرة إلى `Carbon::parse()` بلا تحقق من صيغة `YYYY-MM`. إدخال قيمة غير قابلة للتحليل قد يرمي استثناءً ويحوّل طلب صفحة الحضور إلى 500؛ لا يؤثر هذا على سجلات موظفين آخرين لأن الاستعلام يظل مقيداً بمعرف الموظف.

**الدليل:** `app/Http/Controllers/App/EmployeePortalController.php:128-137` يقرأ الشهر دون `validate()` أو `date_format` قبل التحليل.

**الإجراء:** تحقق من `month` بصيغة سنة-شهر وحدد النطاق المسموح قبل استخدام Carbon، وأعد خطأ تحقق 422/رسالة مناسبة بدلاً من 500.

### 20) **عالٍ — مسارات عناصر أمر العمل لا تربط العنصر بالأمر الأب المصرّح عليه**

**الأثر:** عدة endpoints تستدعي `authorize('update', $work_order)` على أمر من المسار، ثم تعمل على `WorkOrderItem`, `WorkOrderItemNote`, أو `WorkOrderItemPart` المربوط بمعرّف مستقل من دون فحص `work_order_id` أو `work_order_item_id`. لا يوجد `scopeBindings()` على هذه المسارات، والنطاق العام لهذه العناصر يضمن المستأجر/المركز فقط، لا تبعيتها للأمر الأب. إذا كان للمستخدم أمر مفتوح وآخر مغلق في المركز نفسه، يمكن تمرير الأمر المفتوح مع item من المغلق وتعديل/حذف البند أو إدارته، متجاوزاً منع تعديل الأمر المغلق. كذلك يمكن إضافة ملاحظة على عنصر من أمر آخر مع `work_order_id` للأمر الأول، أو حذف ملاحظة لا تخص العنصر/الأمر الموجودين في الرابط.

**الدليل:**
- `routes/web.php:397-400,444-454,463-469` يعرّف المسارات بأمر أب وعناصر فرعية كمعرفات منفصلة.
- `app/Http/Controllers/App/WorkOrders/WorkOrderItemController.php:156-208,214-239`: يفوض على `$work_order` ثم يحدث/يلغي `$item` دون مقارنة `$item->work_order_id`.
- `WorkOrderTechnicianController.php:22-54,60-127`: يحدّث حالة البند ويربط/يفصل فنيين دون فحص ملكية البند للأمر.
- `WorkOrderNotesController.php:22-62,94-105`: لا يتحقق أن البند أو الملاحظة يتبعان الأمر/البند في URL.
- `WorkOrderPartsController.php` ضمن namespace `App\\WorkOrders`، الأسطر 64-109، يفوض على أمر المسار ثم يعدل/يحذف `$part` دون مطابقة `work_order_id`/`work_order_item_id`.
- على النقيض، `WorkOrderItemQualityService::assertSameContext()` و`WorkOrderLaborEntryController::ensureSameTenantAndCenter()` يفحصان ارتباط العنصر/المدخل بالأمر، ما يؤكد وجود حراسة مقصودة يمكن تعميمها.
- لم أجد اختبارات HTTP سلبية تمرر عنصراً من أمر آخر إلى هذه المسارات.

**الإجراء:** فعّل scoped bindings حيث تتطابق أسماء العلاقات، وعلى كل حال حمّل كل طفل عبر علاقة الأمر (`$work_order->items()` ثم علاقة الملاحظات/القطع)، وتحقق من تطابق parent IDs قبل أي كتابة. اختبر استخدام معرّف أمر مفتوح مع عنصر أمر مغلق في المركز نفسه، وأمر من مركز آخر.

### 21) **عالٍ — مسارات أجزاء أمر العمل القديمة تحذف/تعدل السطر دون عكس حركة المخزون**

**الأثر:** ما زالت مسارات nested منفصلة للأجزاء مسجلة وقابلة للاستدعاء، رغم أن شيفرة Vue الحالية تستخدم المسارات الأعلى التي تمر عبر الخدمة. هذه المسارات nested تكتب مباشرةً على سجل الجزء: `updatePart()` ينفذ `$part->update($validated)`، و`deletePart()` ينفذ soft-delete مباشرة. إذا كان الجزء من المستودع فقد تم إصدار المخزون عند الإضافة؛ تغيير الكمية/المصدر أو حذف السطر عبر هذه المسارات لا يمر بخدمة `WorkOrderPartsService` التي تعدل الكمية وتعيد الحركة وتحتفظ بأثر عكسي. النتيجة الممكنة فرق بين رصيد المخزون وحالة/كمية الجزء الظاهرة في أمر العمل.

**الدليل:**
- المسارات النشطة في `routes/web.php:463-465` تصل إلى `app/Http/Controllers/App/WorkOrders/WorkOrderPartsController.php`.
- `WorkOrderPartsController::updatePart/deletePart` في `app/Http/Controllers/App/WorkOrders/WorkOrderPartsController.php:64-109` يعدل/يحذف مباشرةً دون استدعاء الخدمة.
- `WorkOrderPartsService::updatePart()` و`removePart()` في `app/Services/Inventory/WorkOrderPartsService.php:68-112,161-173` يطبقان دلتا المخزون والعكس الناعم مع حفظ سجل الحركة.
- لم أجد استخداماً لهذه المسارات nested في صفحات Vue الحالية؛ صفحات أمر العمل تستدعي endpoints الأخرى في `app/Http/Controllers/App/WorkOrderPartsController.php` التي تستخدم الخدمة، لكن المسارات nested تبقى قابلة للاستدعاء مباشرةً. اختبارات الخدمة تغطي العكس/الكمية (`tests/Unit/Services/Inventory/WorkOrderPartsServiceTest.php:137-179`) لكن لا تغطي nested HTTP endpoints.

**الإجراء:** احذف المسارات القديمة إن لم تعد مستخدمة، أو اجعل كل تعديل/حذف يمر عبر `WorkOrderPartsService` مع تحقق ملكية الجزء للأمر والبند. اختبر رصيد المخزون قبل وبعد التعديل والحذف عبر كل مسار مسجل.

### 22) **عالٍ — زر إنشاء الفاتورة الآجلة لأمر مكتمل يرفضه التفويض دائماً**

**الأثر:** صفحة أمر العمل تعرض زر إنشاء فاتورة آجلة تحديداً عندما تكون الحالة `done`، ولا توجد فاتورة، والرصيد موجب. عند إرسال الزر، يفحص المتحكم صلاحية `update` على أمر العمل. سياسة أمر العمل تمنع التحديث لكل حالة غير قابلة للتحرير، وتستثني فقط مسارات الدفع؛ مسار إنشاء الفاتورة ليس من بينها. لذلك يُرفض طلب الفاتورة الآجلة قبل الوصول إلى إنشاء الفاتورة.

**الدليل:**
- `resources/js/Components/WorkOrders/WorkOrderHeader.vue:307-313` يجعل الزر ظاهراً عند `status === 'done' && !invoice && balance > 0.01`.
- `resources/js/Pages/WorkOrders/Show.vue:1274-1282` يرسل `POST app.work-orders.invoice`.
- `app/Http/Controllers/App/InvoicesController.php:59-80` يبدأ بـ `$this->authorize('update', $workOrder)` قبل التحقق والإنشاء.
- `app/Policies/WorkOrderPolicy.php:45-66` يرفض `update` عندما `canBeEdited()` false؛ الاستثناء الوحيد المحدد هو مسار الدفع.
- نقطة POST الوحيدة لهذا الإجراء مسجلة في `routes/web.php:998`. لم أجد بديلاً لإنشاء الفاتورة الآجلة من شاشة أمر العمل.

**الإجراء:** أنشئ ability مستقلة مثل `createInvoice` تتحقق من صلاحية الفوترة وملكية المستأجر/المركز وحالة أمر العمل، واستخدمها من المتحكم بدلاً من صلاحية تعديل أمر مكتمل. أضف اختبار HTTP للحالة نفسها التي يظهر فيها الزر.

### 23) **متوسط إلى عالٍ — التحصيل المتزامن قد يتجاوز رصيد الفاتورة أو يحفظ إجمالي دفع قديم**

**الأثر:** الحد الأعلى للمبلغ يتحقق في المتحكم من `invoice->balance` قبل دخول الخدمة. `PaymentService::recordPayment()` يفتح معاملة، لكنه لا يقفل الفاتورة ولا يعيد حساب الرصيد/التحقق منه داخل المعاملة قبل إنشاء السجل. إذا وصلت دفعتان متزامنتان على الرصيد نفسه (وخاصة pay-full)، يمكن قبول كليهما؛ وقد تحسب إحدى المعاملتين `total_paid` قبل أن ترى دفعة المعاملة الأخرى ثم تحفظ قيمة قديمة على الفاتورة. هذا خطر اتساق مالي مشروط بالتزامن، وليس نتيجة مرصودة في بيئة تشغيل فعلية.

**الدليل:**
- `app/Http/Controllers/App/PaymentsController.php:23-44,71-85` يقارن المبلغ برصيد النموذج المحمّل مسبقاً ثم يستدعي الخدمة.
- `app/Services/PaymentService.php:23-45,84-90` ينشئ الدفع ويعيد حساب الحالة في معاملة، دون `lockForUpdate()` على الفاتورة أو فحص جديد للرصيد.
- `app/Models/Invoice.php:131-156` يجمع المدفوعات ثم يحفظ `total_paid` والحالة؛ العملية ليست ذرية بالنسبة لإنشاء الدفعات المنافسة.
- اختبارات الدفع الحالية تتحقق من التسلسل العادي والحسابات، ولم أجد اختبار تزامن/إعادة تحقق من رصيد متغير داخل معاملة.

**الإجراء:** اقفل الفاتورة داخل معاملة الدفع، أعد تحميلها وحساب رصيدها بعد القفل، ارفض الزيادة، ثم أنشئ الدفع وأعد الحساب قبل الالتزام. أضف اختباراً تنافسياً أو اختبار خدمة يثبت عدم تجاوز الرصيد عند طلبين متزامنين.

### 24) **عالٍ — مستخدم مركز يمكنه فتح فاتورة مركز آخر عبر معرفها**

**الأثر:** الفاتورة تستخدم `CenterScoped`، لكن الربط الضمني لمعلمات المسار يزيل هذا النطاق صراحةً ويبحث داخل المستأجر فقط. ثم سياسة `view` لا تتحقق إلا من صلاحية مشاهدة الفواتير وتطابق المستأجر، ولا تقارن مركز الفاتورة بمركز المستخدم. النتيجة أن مستخدم مركز لديه صلاحية الفواتير ويعرف/يخمن معرف فاتورة مركز آخر يمكنه فتح تفاصيلها وطباعتها؛ أما قائمة الفواتير فتقيّد النتائج بالمركز الحالي، ما يجعل فحص المعرف المباشر يتجاوز سلوك القائمة.

**الدليل:**
- `app/Models/Invoice.php:17-20,186-191`: النموذج يستخدم `CenterScoped`، لكن `resolveRouteBinding()` ينفذ `withoutGlobalScope('center_scoped')` ثم يقيّد بـ `tenant_id` فقط.
- `app/Policies/InvoicePolicy.php:27-31`: `view()` يطلب `INVOICES_VIEW` ويطابق tenant فقط، بلا تحقق `center_id`.
- `app/Http/Controllers/App/InvoicesController.php:35-53,102-131`: صفحتا العرض والطباعة تعتمد كلتاهما على هذا التفويض.
- `InvoicesController::salesIndex()` يقيّد القائمة بـ `center_id` الحالي عند `app/Http/Controllers/App/InvoicesController.php:179-185`.
- `PaymentPolicy` نفسه يتحقق من tenant والمركز (`app/Policies/PaymentPolicy.php:18-25`)، ما يبرز عدم الاتساق في حدود الفاتورة.

**الإجراء:** إن كان عزل الفروع هو المطلوب، احذف تجاوز center scope من binding أو تحقق من المركز في سياسة `view` و`recordPayment`، مع قواعد صريحة لدور من يملك رؤية tenant-wide. أضف اختباراً لمستخدم مركز A يحاول عرض وطباعة فاتورة مركز B داخل المستأجر نفسه.


### 25) **عالٍ — مرتجع الشراء يقبل بنود فاتورة أخرى وكميات زائدة**

**الأثر:** عند إرسال مرتجع لفاتورة شراء، يمكن تمرير `purchase_invoice_line_id` تابعاً لفاتورة أخرى، حتى لو كانت من مستأجر آخر، لأن التحقق يقتصر على وجود السطر. كذلك لا يُحسب مجموع الكميات المرتجعة سابقاً ولا يُقارن بالكمية الأصلية. المعاملة تنشئ المرتجع وتخصم الكمية من المخزون باستخدام السطر المقبول، ما قد يربط مرتجعاً بالفاتورة A بينما يسحب أصنافاً/كميات تخص فاتورة B أو يجعل المخزون سالباً ويحرّف أرصدة المورد.

**الدليل:**
- `app/Http/Controllers/App/PurchaseReturnsController.php:32-47`: قاعدة `exists:purchase_invoice_lines,id` لا تربط السطر بـ`$purchaseInvoice` ولا بمستأجر/مركز المستخدم.
- `app/Http/Controllers/App/PurchaseReturnsController.php:56-60`: تحميل السطر عبر `PurchaseInvoiceLine::findOrFail()` وقبول الكمية المرسلة دون حد أعلى.
- `app/Http/Controllers/App/PurchaseReturnsController.php:80-85,97-119`: يُربط السطر بمرتجع الفاتورة المحددة ثم تُصدر الكمية من المخزون.
- لم أجد قيداً فريداً أو تجميعاً للكميات المرتجعة السابقة في مسار الإنشاء.

**الإجراء:** تحقّق أن كل سطر يتبع الفاتورة المحددة والمستأجر/المركز المصرح، واقفل السطر داخل المعاملة، ثم ارفض الكمية التي تتجاوز `qty` ناقص مجموع المرتجعات السابقة. أضف اختبارات لعبور الفاتورة، وتجاوز الكمية، والطلبين المتزامنين.

### 26) **متوسط إلى عالٍ — دفعات نموذج الشراء المباشر قد تتجاوز قيمة الفاتورة**

**الأثر:** قواعد التحقق تضمن أن كل دفعة موجبة فقط، ولا تقيد مجموع `payments.*.amount` بإجمالي الفاتورة. الخدمة تجمع المبالغ، ثم تحفظ الرصيد كـ`max(0, total - totalPaid)` وتسجل كل الدفعات كما أُرسلت. يمكن بذلك أن تكون الدفعات المسجلة أكبر من قيمة الفاتورة، بينما يظهر الرصيد صفراً والحالة «مدفوعة»، فتختل التقارير والأرصدة المحاسبية.

**الدليل:**
- `app/Http/Controllers/App/PurchaseInvoicesController.php:85-90`: لا يوجد تحقق من مجموع الدفعات مقابل مجموع البنود/الفاتورة.
- `app/Services/Purchasing/PurchasingService.php:500-524`: تجمع الخدمة الدفعات في `$totalPaid` دون حد، ثم تصفّر الرصيد بـ`max(0, ...)` وتسجل الحالة مدفوعة.

**الإجراء:** بعد حساب إجمالي الفاتورة داخل الخدمة والمعاملة، ارفض إذا تجاوز مجموع الدفعات الإجمالي (مع سياسة صريحة للتقريب)، وأعد حساب الإجمالي من البنود الموثوقة بالخادم بدلاً من قبول أي مبالغ مشتقة من العميل. أضف اختباراً لمجموع دفعات يتجاوز الإجمالي.


### 27) **عالٍ — عقود الموظفين لا تتحقق من مركز الموظف المرتبط**

**الأثر:** مستخدم مخوّل بإدارة العقود في مركز واحد يستطيع، إذا عرف رقم موظف من مركز آخر ضمن المستأجر نفسه، إنشاء عقد له أو تعديل/حذف عقده. مسارات التطبيق تمر عبر `center.context`، لكن النموذج `Employee` و`EmployeeContract` يستخدمان `TenantScoped` فقط؛ ربط المسار والسياسة لا يطبّقان نطاق المركز. يتضمن العقد `salary_details` و`content`، فتتجاوز المشكلة فصل موارد HR بحسب المركز وتعرض/تغيّر بيانات تعاقدية ورواتب.

**الدليل:**
- `routes/web.php:334` يطبق `center.context` على مسارات `/app`، ومنها مسارات العقود عند `routes/web.php:884-887`.
- `app/Models/HR/Employee.php:14-16` و`app/Models/HR/EmployeeContract.php:13-15` يستخدمان `TenantScoped`؛ هذا النطاق يقيد `tenant_id` فقط في `app/Models/Concerns/TenantScoped.php:20-29`.
- `EmployeeContractsController::store()` يجيز `EmployeeContract::class` ثم ينشئ العقد من الموظف المرتبط دون فحص `center_id` في `app/Http/Controllers/App/HR/EmployeeContractsController.php:14-38`.
- `EmployeeContractPolicy::update/delete()` يفحصان الصلاحية والمستأجر فقط، ولا يتحققان من مركز عقد الموظف في `app/Policies/EmployeeContractPolicy.php:18-39`.

**الإجراء:** اربط إنشاء العقد بموظف من المركز الحالي، وافحص مركز الموظف عبر علاقة العقد في `view/update/delete`، أو صرّح بسياسة HR tenant-wide موحدة إن كان هذا هو المقصود. أضف اختبارات بمستخدم مركز A وموظف/عقد مركز B.


### 28) **عالٍ — أدوار النظام التشغيلية تستطيع تغيير الباقات والرموز والاشتراكات**

**الأثر:** كل مستخدم يمر من `system.admin` يستطيع الوصول إلى مسارات كتابة الباقات والرموز الترويجية والاشتراكات. هذا يشمل دور `support` الذي توثّق برمجياً بأنه للقراءة/الدعم ولا ينفذ عمليات مدمرة، ودور `admin` التشغيلي. تعديل سعر باقة أو رمز خصم يؤثر على تسجيلات واشتراكات المستأجرين كافة، كما تتيح عمليات الاشتراك تغيير حالة الاشتراك أو تمديده؛ هذا تجاوز صلاحيات دور النظام حتى لو لم يكن المستخدم مستأجراً عادياً.

**الدليل:**
- `routes/web.php:1014` يطبق على لوحة النظام `auth:web,admin` و`system.admin` فقط.
- `routes/web.php:1146-1166` يسجل عمليات كتابة الباقات والرموز والاشتراكات خارج أي `Route::middleware('super_admin')`، بينما مجموعات أخرى موصوفة صراحة بأنها عمليات حساسة ومحمية بـ`super_admin` (`routes/web.php:1069-1075,1129-1135`).
- `app/Http/Middleware/EnsureSuperAdmin.php:14-25` يعرّف الأدوار ويوضح أن `support` لا ينفذ عمليات مدمرة، لكن هذا القيد لا ينطبق على المسارات أعلاه.
- متحكمات الكتابة (`PlansController`, `PromoCodesController`, `SubscriptionsController`) لا تحتوي فحص `authorize()` أو تحقق دور بديل في طرق `store/update/destroy/cancel/activate/extend`.

**الإجراء:** ضع عمليات تغيير الأسعار/الخصومات والإنشاء/الإلغاء/التمديد ضمن صلاحية دقيقة؛ الحد الأدنى حماية العمليات المالية/التدميرية بـ`super_admin`، مع فصل صلاحيات القراءة عن التعديل في لوحة النظام. اختبر أن `support` و`admin` يتلقيان 403 على تلك العمليات وأن `super_admin` يستطيعها.


### 29) **متوسط إلى عالٍ — إنشاء اشتراك إداري يتجاهل دورة الفوترة والسعر المقابل**

**الأثر:** نموذج إنشاء الاشتراك يعرض اختيار دورة شهرية/سنوية، لكن المتحكم يحسب السعر دائماً من `price_yearly` ويحفظ `billing_cycle` دائماً `yearly`. اختيار «شهري» يُسجل اشتراكاً سنوياً بالسعر السنوي؛ كذلك حقل الرمز الترويجي يقبل أي رمز موجود فقط، ثم يخزّن اسمه دون التحقق من صلاحيته أو تطبيق الخصم أو تسجيل استخدامه. لذلك تتعارض البيانات والواجهة مع الاشتراك المالي المنشأ.

**الدليل:**
- `resources/js/Pages/System/Subscriptions/Create.vue:118-153,190-212` يحتوي اختيار دورة الفوترة والرمز الترويجي ويرسل النموذج. نموذج صفحة القائمة له الاختيارات نفسها عند `resources/js/Pages/System/Subscriptions/Index.vue:420-494,620-675`.
- `app/Http/Controllers/System/SubscriptionsController.php:101-114` يقبل `billing_cycle` والرمز، لكنه يضع `$price = (float) $plan->price_yearly`.
- `app/Http/Controllers/System/SubscriptionsController.php:138-150` يحفظ `billing_cycle` بقيمة ثابتة `yearly` والسعر السنوي، ولا يستدعي تحقق/تطبيق PromoCode أو تسجيل الاستخدام.

**الإجراء:** استخدم قيمة الدورة المرسلة لاختيار السعر وتاريخ الانتهاء، واحفظها، ثم تحقق من الرمز حسب الخطة والدورة والتواريخ وحدود الاستخدام وطبّق الخصم وسجل الاستخدام داخل معاملة. اختبر إنشاء اشتراك شهري وسنوي مع رمز صالح ورمز منتهي.


### 30) **حرج — حساب دعم يستطيع ترقية نفسه إلى `super_admin`**

**الأثر:** يستطيع حساب بدور `support` إنشاء حساب `super_admin` جديد مباشرة، أو تغيير دوره هو إلى `super_admin` عبر endpoint التحديث؛ ثم يسمح `EnsureSystemAdmin` للحساب المترقى بالوصول للوحة النظام. هذا تصعيد صلاحيات من دور موصوف بأنه دعم للقراءة إلى سيطرة كاملة على لوحة الإدارة، متجاوزاً حماية العمليات المفردة المقيدة بـ`super_admin`.

**الدليل:**
- `routes/web.php:1014,1258-1264`: مسارات إدارة المشرفين ضمن مجموعة `system.admin` العامة، ولا تقع ضمن مجموعة `super_admin`.
- `app/Http/Controllers/System/AdminUsersController.php:57-72,99-127`: `store` يقبل `role` من `super_admin|admin|support` و`update` يقبل تغيير الدور نفسه دون authorize أو قيد الدور الحالي.
- `app/Models/AdminUser.php:68-72`: دور `super_admin` يتجاوز قائمة permissions المخصصة.
- `app/Http/Middleware/EnsureSystemAdmin.php:54-69`: أي `AdminUser` موثق يمكنه دخول لوحة النظام، ولا يشترط أن يكون `super_admin`.
- وصف الأدوار في `app/Http/Middleware/EnsureSuperAdmin.php:14-25` يصرح أن الدعم لا ينفذ عمليات مدمرة؛ هذا القيد غائب هنا.

**الإجراء:** اجعل إنشاء/تعديل/حذف حسابات الإدارة بيد `super_admin` فقط، وامنع رفع الدور ذاتياً أو منح `super_admin` عبر طلب عام. أضف اختبارات 403 لحساب `support` عند إنشاء/ترقية حساب، واختبار منع تغيير دوره ذاتياً. اعتبر أي حساب دعم قادر على كتابة هذه المسارات بحاجة لمراجعة سجل النشاط.

### 31) **عالٍ — حساب الدعم يستطيع بث إعلان جماعي عبر البريد أو SMS**

**الأثر:** أي حساب نظام يمر من المجموعة العامة، بما فيه `support`، يستطيع إنشاء إعلان يستهدف كل المستأجرين أو فئة منهم، ثم نشره. النشر يستدعي إرسال البريد والرسائل النصية فعلياً بحسب القنوات المحددة؛ لا يوجد فحص دور أو صلاحية بالـ controller أو route. يسمح ذلك بإرسال تواصل جماعي غير معتمد واستهلاك أرصدة الرسائل، وليس مجرد كتابة مسودة.

**الدليل:**
- `routes/web.php:1014,1267-1274`: مسارات الإنشاء والنشر والإيقاف والحذف في مجموعة `system.admin` العامة.
- `app/Http/Controllers/System/AnnouncementsController.php:56-78`: الإنشاء يقبل `target` و`channels` من الطلب دون سياسة، ويحدد المنشئ عبر `auth()->id()`.
- `app/Http/Controllers/System/AnnouncementsController.php:91-103,112-126`: `publish()` يحدّث الحالة ثم يستدعي `sendToChannels()`، والذي يرسل لجميع المستأجرين المطابقين للقنوات المختارة.
- `EnsureSuperAdmin` يصف الدعم بأنه لا ينفذ إجراءات مدمرة (`app/Http/Middleware/EnsureSuperAdmin.php:14-25`)، لكن هذه المسارات غير موضوعة داخل تلك المجموعة.

**الإجراء:** افصل صلاحية إعداد الإعلان عن صلاحية البث، واجعل نشر القنوات الخارجية لدور مخول (على الأقل `super_admin` أو permission مخصص)، مع تسجيل تدقيق للقنوات والجمهور والمستخدم الذي أكد الإرسال. اختبر منع دعم النظام من نشر SMS/email جماعي والسماح للمخول.


### 32) **حرج — صفحة قائمة التكاملات ترسل أسرار المزودين للمتصفح**

**الأثر:** صفحة قائمة التكاملات تسلسل نماذج `Integration` كاملة إلى Inertia، بما فيها عمود `config` الذي يحتوي مفاتيح API وكلمات مرور SMTP/Twilio/WhatsApp. صفحة التفاصيل وحدها تطبق تنقيحاً يدوياً، لكن فتح صفحة القائمة يكشف الإعدادات الخام في props/payload للمستخدم المسجل في لوحة النظام، ومنهم الأدوار التشغيلية. كما أن `config` مخزن كـJSON عادي ومحوّل إلى array فقط، وليس مشفراً في الراحة؛ لذلك لا يحميه كشف قاعدة البيانات أو النسخ الاحتياطية.

**الدليل:**
- `app/Http/Controllers/System/IntegrationsController.php:24-42`: يجلب جميع نماذج التكامل ثم يمررها مباشرةً في `integrations` إلى Inertia دون `redactIntegration()`.
- `app/Http/Controllers/System/IntegrationsController.php:56-69,370-390`: التنقيح موجود في `show()` فقط، ما يؤكد أن الكاتب انتبه للكشف في التفاصيل ولم يطبقه على القائمة.
- `app/Models/Integration/Integration.php:22-30`: الحقل `config` له cast إلى `array` فقط.
- `database/migrations/2026_01_05_134151_create_integrations_table.php:12-22`: `config` عمود JSON موثق صراحةً بأنه يحوي مفاتيح API ونقاط الاتصال.
- `routes/web.php:1014,1244-1251`: صفحة القائمة متاحة لكل من يمر من `system.admin`، دون تقييد إضافي بدور أو صلاحية التكاملات.

**الإجراء:** لا ترسل `config` الخام من أي endpoint؛ أنشئ Resource/DTO بقائمة الحقول غير الحساسة، ونقّح أيضاً logs/response payloads. شفّر الأسرار عند التخزين مع ترحيل آمن ودوّر المفاتيح التي ربما عُرضت سابقاً؛ ثم اختبر أن قيم المفاتيح لا تظهر في HTML أو Inertia JSON على صفحة القائمة والتفاصيل.


### 33) **عالٍ — إعدادات المنشأة والضريبة والطباعة قابلة للتغيير دون صلاحية إدارة**

**الأثر:** أي مستخدم مصادق عليه وفعّال وله مركز حالي يمر من مجموعة `/app` يستطيع استدعاء تحديث بيانات المنشأة، وتعطيل VAT أو تغيير IBAN/البيانات القانونية، وتعديل إعدادات ZATCA/ترقيم المستندات/قوالب الطباعة، كما يستطيع استبدال شعار المنشأة أو حذفه. هذه تغييرات على مستوى المستأجر وليست محصورة بمستخدم إداري؛ لا توجد middleware `permission` على المسارات ولا `authorize()` في المتحكمات المعنية.

**الدليل:**
- `routes/web.php:334` يضع `auth`, `verified`, `tenant.active`, `center.context`, و2FA على المجموعة؛ لكن مسارات المنشأة والإعدادات في `routes/web.php:533-538,627-630` لا تضيف permission middleware.
- `app/Http/Controllers/App/CompanyProfileController.php:253-301,307-389` يغير شعار المنشأة وحقولها وإعداد VAT دون استدعاء `authorize()` أو فحص صلاحية في هذه الطرق. وتحديث ZATCA/الترقيم والبيانات ذات الصلة يمر عبر نفس `update()` عند الأسطر 307-320 ثم أقسام `switch`.
- `app/Http/Controllers/App/SystemSettingsController.php:94-155` يقبل تعديل `sms_2fa_enabled` وإعدادات طباعة المستأجر دون تحقق صلاحية داخل المتحكم.
- توثيق التجاوز `@bypass-authorization-scanner` في كلا المتحكمين يستند إلى middleware الدخول والسياق فقط، لا إلى دور إداري.

**الإجراء:** عرّف قدرات صريحة لإدارة بيانات المنشأة والضرائب/ZATCA والطباعة، وتحقق منها على كل endpoint؛ افصل قراءة الإعدادات عن تعديلها، ولا تعتمد على إخفاء الأزرار في Vue. اختبر مستخدماً موظفاً عادياً يحاول كل تغيير وتحقق من رفضه.

### 34) **عالٍ — مستخدم مركز يستطيع قراءة وتعديل إعدادات مركز آخر**

**الأثر:** `center.context` يثبت أن المستخدم معيّن للمركز الحالي، لكنه لا يقارن المركز الحالي بمركز `{center}` المطلوب. بعد ذلك تسمح `CenterPolicy::view/update` بأي مركز من المستأجر نفسه. يستطيع مستخدم مركز A معرفة/تخمين معرف مركز B ثم فتح إعداداته وتغيير بياناته وعنوانه وساعات عمله وشعاراته/ختمه، كما يحصل العرض على معاملات وفواتير مرتبطة بذلك المركز. سياسة المركز توثق صراحةً أن قيد الدور الإداري غير مطبق بعد.

**الدليل:**
- `routes/web.php:620-625` يربط إعداد المركز بمعرف مركز من المسار داخل مجموعة التطبيق المحمية بسياق مركز (`routes/web.php:334`).
- `EnsureCenterContext` يتحقق من تعيين المستخدم إلى `current_center_id` فقط (`app/Http/Middleware/EnsureCenterContext.php:22-58`)، ولا يفحص أن `{center}` المطلوب هو هذا المركز أو مركز آخر مسموح للمستخدم.
- `app/Policies/CenterPolicy.php:34-49`: `view()` و`update()` يكتفيان بتطابق tenant؛ و`update()` يحمل TODO لإضافة قيد الدور.
- `app/Http/Controllers/App/CenterSettingsController.php:27-30,133-136,261-312` يعتمد هذه السياسة للعرض والتعديل ورفع/حذف الشعار والختم. العرض يحمل معاملات وفواتير المركز المحدد في `app/Http/Controllers/App/CenterSettingsController.php:82-125`.

**الإجراء:** قيّد `view/update` بمركز المستخدم الحالي أو بصلاحية tenant-wide مقصودة ومحددة، وطبّق التحقق ذاته على تحميل معاملات/فواتير المركز. اختبر مستخدم A مع GET وPUT ورفع ختم على مركز B، واختبر الاستثناء المصرح لإدارة tenant-wide.


### 35) **عالٍ — اعتماد المعاملات المالية ومسارات عرضها متاح لأي مستخدم تطبيق ويثق بمجاميع العميل**

**الأثر:** أي مستخدم يمر من مجموعة `/app` يستطيع إنشاء معاملة إيراد/مصروف ثم اعتمادها، حتى لو لم يكن مخولاً بإدارة المالية. حقول `amount` و`tax_amount` و`total_amount` تأتي كلها من الطلب، ولا يوجد تحقق حسابي يفرض اتساق الإجمالي مع المبلغ والضريبة. عند اعتماد الإيراد أو المصروف، ينشئ الخادم فاتورة ودفعة/سداداً بناءً على تلك القيم؛ وبذلك يمكن لمستخدم غير مالي إنشاء قيود مالية مصطنعة بقيم متناقضة. التعديل/الحذف كذلك لا يطلبان صلاحية، مع منع بسيط للحالة المعتمدة فقط.

**الدليل:**
- `routes/web.php:541-543`: البحث وCRUD واعتماد معاملات المنشأة داخل مجموعة التطبيق العامة، دون middleware صلاحية خاص.
- `app/Http/Controllers/App/CompanyTransactionController.php:35-69`: `store()` يقبل المبلغ والضريبة والإجمالي من العميل وينشئ المعاملة `draft` بلا `$this->authorize()`.
- `app/Http/Controllers/App/CompanyTransactionController.php:72-120`: `update()` و`destroy()` لا يفحصان صلاحية المستخدم أو ملكية مركزه؛ يتحققان فقط من أن الحالة ليست `approved`.
- `app/Http/Controllers/App/CompanyTransactionController.php:123-220`: `approve()` ينشئ فاتورة/دفعة مبيعات أو مشتريات من القيم المخزنة بالمعاملة، ثم يثبت `approved` دون فحص صلاحية اعتماد مالي.
- لم أجد في المتحكم تحققاً يساوي `total_amount` بمجموع `amount` و`tax_amount` أو يشتق الضريبة/الإجمالي من قواعد موثوقة بالخادم.

**الإجراء:** أضف صلاحيات منفصلة للإنشاء والتعديل والحذف والاعتماد، واحصر الاعتماد على دور مالي مخول. احسب الضريبة والإجمالي بالخادم وفق إعدادات المستأجر ولا تثق بقيم الإجمالي المرسلة. استخدم قفلاً/تغيير حالة ذرياً عند الاعتماد لمنع إنشاء فواتير ودفعات مكررة بطلبين متزامنين. اختبر مستخدمًا بلا صلاحية، والتلاعب بالمجاميع، وطلبَي اعتماد متزامنين.


### 36) **متوسط — أربع مسارات معاملات منشأة تشير إلى methods غير موجودة**

**الأثر:** تسجيل `Route::resource` ينشئ تلقائياً مسارات `index`, `create`, `show`, و`edit` إضافةً إلى مسارات الكتابة. لكن `CompanyTransactionController` لا يعرّف سوى `store`, `update`, `destroy`, `approve`, و`searchContacts`. طلب أحد مسارات GET المسجلة التي تشير إلى index/show/create/edit يصل إلى method غير موجودة ويرجع خطأ خادماً بدلاً من صفحة/نموذج. واجهة المنشأة الحالية تستخدم نافذة داخل صفحة الشركة لعمليات الإنشاء والتحرير؛ لذلك الأثر المؤكد هو تعطل المسارات المباشرة/المحفوظة، ولم أجد رابط Vue يستدعيها حالياً.

**الدليل:**
- `routes/web.php:542` يستخدم `Route::resource('/settings/company/transactions', CompanyTransactionController::class)`. و`php artisan route:list --path=settings/company/transactions` على إعداد SQLite مؤقت أظهر GET للمسارات الأساسية و`/create` و`/{transaction}` و`/{transaction}/edit`.
- قائمة methods الفعلية في `app/Http/Controllers/App/CompanyTransactionController.php` هي `store`, `update`, `destroy`, `approve`, و`searchContacts`؛ لا توجد `index`, `create`, `show`, أو `edit`.
- `resources/js/Pages/Settings/Company/Modals/CompanyTransactionModal.vue:375-382` يرسل مباشرةً إلى POST/PUT ولا يعتمد على صفحات resource المنفصلة.

**الإجراء:** إن كانت مسارات GET مطلوبة فأضف methods/views الصحيحة؛ وإلا قيد تسجيل resource على `only(['store','update','destroy'])` واحتفظ بمسار الاعتماد الصريح، حتى لا تُعلن routes غير قابلة للتنفيذ. تحقق من عدم اعتماد عملاء خارجيين على المسارات قبل حذفها.


### 37) **حرج — معالج ZATCA يعلن نجاح الامتثال والإنتاج دون تنفيذ التكامل الحقيقي**

**الأثر:** خطوة `runComplianceChecks()` لا ترسل مستندات إلى ZATCA؛ بل تعيد أربعة اختبارات `PASSED` ثابتة وتغيّر حالة الإعداد إلى `compliance_passed`. خطوة طلب شهادة الإنتاج لا تستدعي endpoint الإنتاج، بل تولّد `PROD_CSID_*` و`PROD_SEC_*` عشوائياً، وتفعّل `STATUS_ACTIVE` وQR كأن الربط الإنتاجي تم. بهذا تعرض الواجهة نجاحاً زائفاً وقد تعتبر منظومة الفوترة أن المنشأة مرتبطة بالإنتاج بينما لا تملك شهادة حقيقية صادرة من الهيئة، ما يعرض الامتثال وإرسال الفواتير الفعلية للفشل.

**الدليل:**
- `app/Services/Zatca/ZatcaOnboardingService.php:233-261`: `runComplianceChecks()` يعرّف قائمة ثابتة من أربعة عناصر `PASSED`، بلا طلب HTTP أو تحقق من استجابة الهيئة.
- `app/Services/Zatca/ZatcaOnboardingService.php:266-299`: `requestProductionCsid()` لا يستخدم `ENDPOINTS` ولا `Http`; ينشئ المعرف/السر عبر `Str::random()` ثم يحفظ الحالة `STATUS_ACTIVE`.
- `app/Services/Zatca/ZatcaOnboardingService.php:16-24` يعرّف endpoint الإنتاج، لكن الاستعمال الوحيد لـHTTP في الخدمة هو طلب `/compliance` ضمن الخطوة الثانية (`:179-191`).
- `app/Http/Controllers/App/ZatcaOnboardingController.php:127-158` يعرض نجاح الخدمة مباشرة للمستخدم ولا يميّز وضع المحاكاة من ربط إنتاج فعلي.
- `tests/Feature/ZatcaOnboardingTest.php` يتحقق من أن POST محلياً ينتهي بحالة active وبوجود production CSID؛ لا يوجد اختبار يثبت استجابة حقيقية أو mock موثقاً لتبادل production CSID.

**الإجراء:** لا تعلن الحالة الإنتاجية نشطة ولا تفعّل إرسال الفواتير إلا بعد تبادل حقيقي موثق لشهادة الامتثال مع endpoint الإنتاج وتحقق استجابة الهيئة. إذا كان المعالج حالياً نموذجاً تجريبياً، فاقصره صراحةً على `local/testing`، واستخدم حالة/واجهة تجريبية لا يمكن التباسها بالإنتاج. نفّذ اختبارات HTTP باعتراض الاستجابات للتحقق من success/failure الحقيقيين، ومنع الشهادة المحاكاة من تفعيل الإنتاج.


### 38) **عالٍ — حذف بند التحويل لا يتحقق من تبعيته للتحويل المصرح**

**الأثر:** `removeItem()` يجيز تعديل التحويل الموجود في URL، ثم ينفذ حذفاً مباشراً على `InventoryTransferItem` المربوط بمعرّف مستقل دون مقارنة `inventory_transfer_id`. يستطيع المستخدم اختيار تحويل A في حالة draft المسموح تعديلها، وتمرير item من تحويل B؛ فيحذف بند B حتى لو كان B قد أُرسل. حذف بند أُرسل يقطع سجل التسوية بين حركة الإخراج وحركة الإدخال، وقد يمنع استلامه ويبقي المخزون ناقصاً في المصدر.

**الدليل:**
- `routes/web.php:765` يستقبل `{transfer}` و`{item}` كمعاملين منفصلين دون `scopeBindings()`.
- `app/Http/Controllers/App/InventoryTransfersController.php:150-166`: يفحص التفويض وحالة التحويل الأب `$transfer` فقط ثم يستدعي `$item->delete()`؛ لا يقارن `$item->inventory_transfer_id` بـ`$transfer->id`.
- `app/Models/InventoryTransferItem.php:18-20` لا يستخدم SoftDeletes، لذلك الحذف فعلي.
- `InventoryService::sendTransfer()` يربط حركة الإخراج بـitem id (`app/Services/Inventory/InventoryService.php:491-507`)، ثم `receiveTransfer()` يعتمد على items المتبقية (`:535-561`).

**الإجراء:** حمّل البند عبر `$transfer->items()->findOrFail($item->id)`، وتحقق أن الأب ما زال draft قبل الحذف. اختبر URL بتحويل A مفتوح مع item من B، وبند تحويل مرسل.

### 39) **عالٍ — كمية الاستلام يمكن أن تتجاوز كمية الإرسال**

**الأثر:** endpoint الاستلام يقبل أي `received_qtys.*` أكبر من أو يساوي الصفر دون حد أعلى. الخدمة تسجل تلك الكمية كحركة receipt كاملة وتزيد رصيد مستودع الوجهة، ثم تحوّل حالة التحويل إلى `received`. يمكن بذلك إنشاء مخزون غير حقيقي (مثل إرسال 2 واستلام 200)، مع بقاء حركة الإخراج من المصدر على الكمية الأصلية.

**الدليل:**
- `app/Http/Controllers/App/InventoryTransfersController.php:205-214`: التحقق يطبق `numeric|min:0` فقط، بلا مقارنة مع `qty_sent`.
- `app/Services/Inventory/InventoryService.php:535-560`: يأخذ القيمة مباشرةً ثم يمررها إلى `receipt()` ويسجلها كـ`qty_received`، والخدمة تزيد الرصيد دون أن تفحص كمية الإرسال.
- `app/Http/Controllers/App/InventoryTransfersController.php:209-213` يلتقط أخطاء `ValidationException` التي تطلقها الخدمة، لكن لا يوجد تحقق حدّي فيها أصلاً.
- `resources/js/Pages/Inventory/Transfers/Show.vue:189-191` يرسل `{}` حالياً في زر الاستلام ويستعمل الافتراضي المساوي للكمية المرسلة؛ لذلك الخلل في endpoint/API ويمكن استغلاله بطلب مباشر أو عميل معدل، وهو غير مكشوف كحقل إدخال في الواجهة الحالية.

**الإجراء:** داخل المعاملة أعد تحميل التحويل وبنوده مع القفل، واشترط `0 <= received_qty <= qty_sent` قبل أي حركة، واجعل كمية الاستلام غير قابلة للتغيير بعد تسجيلها أو صمم مساراً صريحاً للتسوية/العجز. أضف اختباراً لتجاوز الكمية والتحقق من أن الرصيد والحالة لا يتغيران.


### 40) **عالٍ — رفع شعار الموقع يحتفظ بامتداد يتحكم به العميل على القرص العام**

**الأثر:** التحقق يقبل الملف بحسب نوع محتواه كصورة، لكن اسم الملف النهائي يُبنى من `getClientOriginalExtension()` غير الموثوق، ثم يحفظ في `public` تحت مسار يخدمه الويب. يمكن لملف polyglot يبدأ بمحتوى صورة مقبول ويضم كوداً لاحقاً أن يمر بفحص MIME بينما يسجل باسم ينتهي بـ`.php`. إذا كان خادم الويب ينفذ PHP داخل مسار `public/storage` (وهذا يتوقف على إعداد Apache/Nginx الفعلي)، يصبح رفع شعار الموقع مسار تنفيذ كود من حساب مخول لتعديل الموقع. حتى مع تعطيل التنفيذ، يظل الامتداد غير الموثوق خطراً في محتوى عام وقد يسبب عرضاً/معالجة غير متوقعة.

**الدليل:**
- `app/Http/Controllers/App/TenantWebsiteSettingsController.php:215-216`: يقبل شعاراً من PNG/JPEG/WebP/SVG عبر قواعد محتوى الصورة، ولا يقيّد امتداد اسم الملف المرسل.
- `app/Http/Controllers/App/TenantWebsiteSettingsController.php:222-235`: يأخذ الامتداد من `$file->getClientOriginalExtension()` ثم يحفظه على `Storage::disk('public')` داخل `tenants/{tenantId}/website/`.
- `app/Models/TenantWebsite.php:80-84`: يعيد رابط الملف عبر `Storage::url()`، ما يجعله ضمن مساحة الملفات العامة.
- `routes/web.php:522-526` يقيد التعديل بصلاحية `settings.website.manage`، لكن لا يغيّر أثر الامتداد القابل للتحكم لمن يحمل تلك الصلاحية. لم أتحقق من إعداد خادم الإنتاج، لذا تنفيذ PHP مشروط به وليس مثبتاً على الخادم الحي.

**الإجراء:** لا تستخدم client original extension؛ حدد الامتداد من MIME موثوق بعد فك/إعادة ترميز الصورة إلى PNG/JPEG/WebP، أو خزّن الرفع في قرص خاص وخادمه بهيدر تنزيل. امنع SVG إن لم تكن هناك حاجة، أو نظّفه بمُعقّم SVG. أضف إعداد خادم صريحاً يمنع تنفيذ السكربتات تحت `/storage`، واختبر صورة polyglot باسم ينتهي بـ`.php` في بيئة معزولة دون نشرها.


### 41) **عالٍ — إعدادات HR والرواتب قابلة للتغيير دون صلاحية HR إدارية**

**الأثر:** أي مستخدم مصادق عليه داخل مجموعة التطبيق يستطيع استدعاء endpoints إنشاء/تعديل/حذف أنواع الموظفين، والمسميات، والبدلات والاستقطاعات، ولوائح HR، كما يستطيع تغيير قواعد الحضور والخصومات والعمل الإضافي. تعديل `amount`, `type`, أو `calculation_base` يؤثر في حسابات الرواتب اللاحقة، وتعديل اللوائح يغير السياسة المعروضة للموظفين. لا توجد حماية في المسارات أو `$this->authorize()` في هذه المتحكمات؛ العزل الموجود يحد السجلات للمستأجر لكنه لا يمنع مستخدماً غير مخول داخل المستأجر. كذلك `department_id` في المسمى الوظيفي يتحقق من وجود عالمي فقط ويمكن أن يقبل قسماً خارج مستأجره.

**الدليل:**
- `routes/web.php:825-855` يسجل كل عمليات HR settings تحت مجموعة `/app/hr` دون middleware `permission:*` خاص بإدارة HR.
- `app/Http/Controllers/App/HR/HRPayrollSettingsController.php:18-162`: إنشاء/تعديل/حذف البدلات والاستقطاعات بلا تفويض داخل المتحكم.
- `app/Http/Controllers/App/HR/HRRegulationsController.php:17-67`: CRUD اللوائح بلا تفويض.
- `app/Http/Controllers/App/HR/SettingsController.php:68-168`: CRUD أنواع الموظفين والمسميات الوظيفية وإعداد الحضور بلا authorize.
- `app/Http/Controllers/App/HR/SettingsController.php:112-141`: `department_id` يستخدم `exists:departments,id` من دون تقييد `tenant_id`.
- `app/Models/HR/Allowance.php`, `Deduction.php`, `HRRegulation.php` و`EmployeeType.php` تستخدم `TenantScoped`؛ هذا يمنع قراءة مستأجر آخر لكنه ليس صلاحية تعديل حسب الدور.

**الإجراء:** أضف صلاحيات منفصلة لعرض/إدارة إعدادات HR والرواتب واللوائح والحضور، وتحقق منها في كل route أو policy/controller، مع ضبط الأزرار وفق الصلاحية كتحسين UX فقط. قيّد القسم بالمستأجر، واختبر مستخدم HR عادي بلا صلاحية إدارة على create/update/delete لكل مجموعة مع بقاء صلاحياته التشغيلية الأخرى.


### 42) **عالٍ — مدير مستخدمين مفوض يستطيع تعديل أو حذف مدير المستأجر العام**

**الأثر:** قائمة المستخدمين تخفي حسابات دور `super_admin`، لكن نقاط التعديل والحذف لا تطبق الحماية نفسها. كما أن قائمة الأدوار المرسلة للنموذج تتضمن دور `super_admin`، ويقبل endpoint الإنشاء `role_id` ثم يربطه بالمستخدم مباشرة. لذلك يستطيع أي مستخدم مُفوّض يملك صلاحية إدارة المستخدمين المناسبة داخل المستأجر إنشاء حساب مدير مستأجر عام، أو تغيير بيانات/تعطيل/حذف الحساب المخفي للمدير العام. هذا تصعيد صلاحيات داخل المستأجر، وليس دليلاً على ترقية إلى مدير النظام العام.

**الدليل:**
- `app/Http/Controllers/App/UserController.php:29-34` يخفي مستخدمي `super_admin` من القائمة فقط.
- `app/Http/Controllers/App/UserController.php:48-50` يعيد كل أدوار المستأجر للنموذج، بما فيها `super_admin`.
- `app/Http/Controllers/App/UserController.php:57-82` يفوض الإنشاء عبر `UserPolicy::create`، ثم يربط أي `role_id` تم التحقق من وجوده دون منع دور `super_admin`.
- `app/Http/Controllers/App/UserController.php:90-128,159-164` يجيز update/delete عبر السياسة ثم ينفذ العمليات دون حظر الهدف إذا كان يحمل دور `super_admin`.
- `app/Policies/UserPolicy.php:44-69` يتحقق من صلاحيات USERS ومن تطابق المستأجر فقط؛ لا يستثني مستخدم `super_admin`.
- `StoreUserRequest` و`UpdateUserRequest` يتحققان من وجود `role_id` دون قاعدة تستبعد دور `super_admin`.

**الإجراء:** استبعد دور `super_admin` من أدوار التعيين، وارفضه server-side في الإنشاء والتعديل حتى مع طلب مباشر، وامنع تغيير/تعطيل/حذف أي مستخدم يحمل هذا الدور إلا عبر مسار استرداد إداري مضبوط. أضف اختبارات صلاحيات لمستخدم مفوض ذي `USERS_CREATE/UPDATE/DELETE` ولـsuper_admin المستهدف.


### 43) **عالٍ — محرر الأدوار يستطيع منح دوره الحالي صلاحيات أعلى ذاتياً**

**الأثر:** مستخدم يملك `USERS_UPDATE` يستطيع فتح دور المستخدم نفسه من صفحة الأدوار، ثم إضافة أي صلاحيات معروضة من `Permissions::all()` إلى الدور، بما في ذلك صلاحيات حذف المستخدمين أو اعتماد المعاملات أو إدارة الإعدادات. لا يقارن الخادم الصلاحيات الجديدة بامتيازات المنفذ ولا يمنع تعديل الدور المرتبط بحسابه. بذلك يستطيع المفوض تصعيد امتيازه إلى صلاحيات أوسع داخل المستأجر.

**الدليل:**
- `routes/web.php:648` يسجل مسار update لدور المستأجر.
- `app/Http/Controllers/App/RoleController.php:78-80` يطلب فقط صلاحية `USERS_UPDATE`.
- `app/Http/Controllers/App/RoleController.php:98-120` يقبل مصفوفة permissions من الطلب ثم ينفذ `syncPermissions()` دون سقف صلاحيات أو منع تعديل الدور الحالي للمستخدم.
- `resources/js/Pages/Settings/Roles/Index.vue` يعرض زر تعديل لكل دور، بما فيه الدور الذي يستخدمه المنفذ.
- `resources/js/Pages/Settings/Roles/RoleFormModal.vue` يعرض قائمة `groupedPermissions` كاملة ويرسل المختار منها؛ مصدرها `Permissions::byModule()` من المتحكم.
- حماية دور `super_admin` تمنع تعديل ذلك الدور بعينه فقط، ولا تمنع المستخدم المفوض من توسيع دوره الآخر.

**الإجراء:** امنع المستخدم من تعديل الدور المرتبط بحسابه، أو افرض سقفاً server-side لا يسمح بمنح صلاحيات أعلى من صلاحيات مفوض إدارة الأدوار المعيّنة له. الأفضل فصل صلاحية إدارة المستخدمين عن إدارة الأدوار، واعتماد تغييرات الامتيازات من صاحب صلاحية أعلى. أضف اختباراً يثبت أن محرر دور لا يستطيع منح نفسه صلاحية غير موجودة لديه أو تحرير دور `super_admin`.


### 44) **عالٍ — أي مستخدم tenant يستطيع إلغاء اشتراك المنشأة وتعليق حسابها**

**الأثر:** مجموعة الفوترة محمية بتسجيل الدخول والتحقق الثنائي، لكن عمليات الاشتراك لا تشترط صلاحية إدارة الفوترة أو كون المستخدم مالك/مدير المنشأة. أي مستخدم تطبيق داخل المستأجر يستطيع إرسال طلب الإلغاء لاشتراك نشط؛ وإذا لم يبق اشتراك نشط آخر، يغيّر المتحكم حالة المستأجر إلى `suspended`، ما قد يوقف وصول المنشأة كلها. المستخدم نفسه يستطيع تبديل التجديد التلقائي أو استئناف الاشتراك، وتغيير تفضيلات تحصيل المتأخرات، ورفع إيصال بنكي وإنشاء طلب اشتراك. عزل `tenant_id` يمنع استهداف مستأجر آخر لكنه لا يقيّد صلاحية الأعضاء داخل المستأجر.

**الدليل:**
- `routes/web.php:334` يضع مجموعة `/app` تحت `auth`, `verified`, `tenant.active`, `center.context`، و`EnsureTwoFactorEnabled`؛ مجموعة `/settings/billing` مسجلة داخلها (`:562-591`) ولا تضيف middleware صلاحية فوترة.
- `app/Http/Controllers/App/BillingController.php:49-51` يعرض صفحة الفوترة لمستخدم المجموعة بلا `authorize()`، ويرجع سجل الإيصالات والمدفوعات وبيانات التحويل البنكي في `:169-184,193-264`.
- `app/Http/Controllers/App/CompanySubscriptionController.php:551-576` لا يتحقق في الإلغاء إلا من `tenant_id` ثم يلغي الاشتراك ويحوّل المنشأة إلى `suspended` إذا لم يبق اشتراك نشط.
- `:582-623` يحمي تبديل التجديد والاستئناف بتطابق tenant فقط؛ `:715-748` ينشئ طلب اشتراك وإيصالاً بنكياً دون فحص دور/صلاحية، و`:967-985` يغير إعدادات التحصيل للمنشأة بالطريقة نفسها.
- `resources/js/Pages/Settings/Billing/Index.vue` يعرض إجراءات الاشتراك والإيصالات ضمن صفحة موحدة؛ إخفاء عناصر الواجهة حسب الدور وحده لن يحمي endpoints.

**الإجراء:** عرّف صلاحية `billing.manage` أو سياسة واضحة لمالك/مدير الفوترة، وطبّقها في كل عمليات القراءة والكتابة من الخادم. أبقِ callback بوابة الدفع على مسار تحقق مستقل لا يعتمد على جلسة مستخدم، مع إثبات التوقيع/المرجع من البوابة. أضف اختبارات عضو عادي مقابل مدير فوترة، وتأكد أن رفض الإلغاء لا يغيّر حالة الاشتراك أو tenant.


### 45) **متوسط — مسار إيصال التحويل يقبل دورة شهرية لكنه يحفظ اشتراكاً سنوياً**

**الأثر:** endpoint رفع إيصال التحويل يقبل `billing_cycle` بقيمتي `monthly` و`yearly`، لكن عند وصول `monthly` يحسب السعر من `price_yearly`، ويضيف سنة إلى تاريخ الانتهاء ويحفظ `billing_cycle = yearly`. بذلك قد تتحول محاولة شراء شهرية عبر هذا المسار إلى فاتورة واشتراك سنويين. الواجهة الحالية لشراء الاشتراك تعرض دورة سنوية فقط، لذا لم أرصد المسار عبر الاختيار الظاهر للمستخدم؛ يظل الخلل في عقد endpoint الذي يعلن قبول الشهري، ويظهر إذا استُدعي من عميل/تدفق يرسل القيمة الشهرية.

**الدليل:**
- `resources/js/Components/Subscription/BankTransferReceiptUpload.vue:274-282` يرسل `billing_cycle` القادمة من حمولة الإعداد إلى endpoint.
- `app/Http/Controllers/App/CompanySubscriptionController.php:719-722` يقبل `monthly` صراحةً.
- `:757-758` يستخدم `price_yearly` بصرف النظر عن القيمة.
- `:826-837` يضيف سنة ويحفظ `billing_cycle` ثابتة على `yearly`.
- `resources/js/Components/Subscription/SubscribeModal.vue:408-448` توضح أن الواجهة الحالية تعرض السنوي فقط؛ لذلك الأثر المباشر في الواجهة الحالية غير مثبت، لكنه تناقض مؤكد في endpoint.

**الإجراء:** إن كان الشهري مقصوداً فاختر السعر والتاريخ المحفوظ وفق الدورة المرسلة، وتحقق من اتساق المبلغ والإيصال مع السعر المعاد حسابه server-side. إن كان السنوي وحده مدعوماً هنا، فارفض `monthly` من التحقق ولا تقبله بصمت. أضف اختباراً endpoint يرسل `monthly` ويتحقق من السعر والدورة وتاريخ الانتهاء.


### 46) **حرج — callback يفعّل الاشتراك دون مطابقة دفعة البوابة بالمبلغ والمرجع المتوقعين**

**الأثر:** callback يأخذ أي `id`/معرّف مقبول من البوابة، يتحقق أن العملية هناك حالتها مدفوعة، ثم يمرر النتيجة إلى خدمة تفعيل الاشتراك. لا يقارن المتحكم المبلغ والعملة المعادين من البوابة بقيمة `SubscriptionPayment::amount/currency`، ولا يقارن معرّف البوابة بالمرجع `gateway_payment_id` المحفوظ عند بدء الدفع. كما أن خدمة الإكمال تحفظ معرّف البوابة الوارد ثم تفعّل الاشتراك وتعلّم الفاتورة مدفوعة بلا إعادة تحقق من هذه المطابقات. يمكن بالتالي استخدام معرّف عملية ناجحة أقل قيمة أو إعادة استخدام معرّف دفع سابق لتفعيل دفعة اشتراك معلقة بقيمة أعلى، إذا ظلّت البوابة تعتبر المعاملة الأصلية ناجحة.

**الدليل:**
- `routes/web.php:572` يقبل callback عبر GET أو POST مع model binding لـ`{payment}`.
- `app/Http/Controllers/App/CompanySubscriptionController.php:491-503` يرسل كامل بيانات الطلب إلى `PaymentManager::verify()` بحسب بوابة سجل الدفع، ثم يستدعي `completePayment()` عند `success`؛ لا يوجد تحقق من tenant المستخدم أو `gateway_payment_id` أو amount/currency.
- `:419-440` يحفظ مرجع البوابة في `gateway_payment_id` وقت إنشاء الدفعة، لكن callback لا يستخدم هذا المرجع للمطابقة.
- `app/Services/Payment/Gateways/MoyasarGateway.php:75-109` (والنظائر في Tap/Tabby/Tamara) يعيد `payment_id`, `amount`, و`currency` بعد جلب العملية من البوابة.
- `app/Services/Billing/PaymentCompletionService.php:50-65` يعلّم السجل مدفوعاً ويحفظ معرّف العملية الواردة؛ لا يقارن القيمة أو العملة ولا يمنع استخدام معرّف خارجي سبق ربطه بدفعة أخرى.

**الإجراء:** اربط callback برقم العملية المتوقع المسجل محلياً، وتحقق أن بوابة الدفع والحالة والمعرّف والمبلغ والعملة كلها تطابق سجل الدفع والفاتورة قبل إكمالها. اجعل معالجة callbacks idempotent على مستوى معرّف البوابة عالمياً، وارفض إعادة ربط معاملة ناجحة بسجل دفع ثانٍ. لا تعتمد على نجاح الاستعلام وحده. أضف اختبارات لإعادة الاستخدام، ولمبلغ/عملة/مرجع مختلف، ولـcallback على دفعة من مستأجر آخر.


### 47) **متوسط — مستخدم المركز العادي يستطيع تعديل كتالوج المركبات وفحوصها**

**الأثر:** متحكمات إعداد الماركات والموديلات والألوان وفئات/عناصر فحوص المركبات لا تستدعي policies ولا تتحقق من صلاحية إدارة كتالوج المركبات. بالتالي يستطيع أي مستخدم يصل إلى مجموعة التطبيق، بما فيه مستخدم تشغيلي بلا صلاحية إدارة الإعدادات، إضافة عناصر جديدة وتعديلها أو تعطيلها وحذفها. النطاق محصور بعناصر المركز الحالي بسبب global scope `CenterScoped`، والمتحكم يمنع تعديل/حذف بيانات `system`، لذا لا أعتبرها تجاوزاً بين المستأجرين أو على بيانات النظام؛ لكنها تسمح بتغيير قوائم المركبات والفحوص للمركز خلاف صلاحيات الدور. أزرار الصفحة نفسها لا تخفي أو تعطل الإجراءات حسب الصلاحية.

**الدليل:**
- `routes/web.php:650-669,678-688` يسجل CRUD وتبديل الحالة للمسارات دون middleware لصلاحية إدارة الكتالوج.
- `app/Http/Controllers/App/VehicleMakeController.php:31-46,54-100` ينفذ index/store/update/delete/toggle دون `authorize()`؛ ونمط مماثل في `VehicleModelController.php` و`VehicleColorController.php`.
- `app/Policies/VehicleMakePolicy.php`, `VehicleModelPolicy.php`, `VehicleColorPolicy.php` تشترط `VEHICLE_SETTINGS_MANAGE`، لكن المتحكمات لا تستدعي هذه السياسات.
- `app/Http/Controllers/App/VehicleConditionItemController.php` و`VehicleConditionCategoryController.php` ينفذان CRUD وتبديل الحالة بلا authorization؛ ويمنعان بيانات system فقط.
- `app/Models/Concerns/CenterScoped.php` يقيّد استعلامات هذه النماذج بالمستأجر والمركز الحالي ويملأ معرفيهما عند الإنشاء، وهو ما يحد نطاق هذه النتيجة.
- `resources/js/Pages/Settings/System/Index.vue` يعرض أفعال الإضافة والتعديل والحذف دون فحص صلاحية الإدارة.

**الإجراء:** استدعِ سياسات الإدارة في كل عمليات الكتابة، وطبّق صلاحية القراءة على endpoints القائمة إذا كانت مطلوبة كحماية مستقلة. اربط أزرار الواجهة بالصلاحية لتحسين التجربة فقط، وأضف اختبارات مستخدم عادي مقابل صلاحية الإدارة تغطي CRUD وتبديل الحالة في كتالوجات الماركات والموديلات والألوان وفئات/عناصر الفحوص.


### 48) **متوسط — أي عضو في المستأجر يستطيع تغيير تصنيفات المعاملات المالية**

**الأثر:** صفحة تصنيفات الدخل/المصروفات ومساراتها تسمح لأي عضو تطبيق بإنشاء الفئات وتغيير اسمها ونوعها وحالتها أو حذفها من دون صلاحية لإدارة الشؤون المالية. الفئة مرتبطة بالمعاملات عبر `income_category_id`؛ تغيير اسمها/نوعها يغيّر التصنيف المعروض للتاريخ المالي، وحذفها يستخدم soft delete فيخفي الفئة المرتبطة بدلاً من محو المعاملة نفسها. قيد المستأجر يمنع تعديل فئة مستأجر آخر، لكنه لا يقيد صلاحية الأعضاء داخل المستأجر.

**الدليل:**
- `routes/web.php:671-676` يسجل القائمة وعمليات CRUD وتبديل النشاط دون middleware صلاحية.
- `app/Http/Controllers/App/IncomeCategoryController.php:24-101` لا يحتوي `authorize()` أو فحصاً لصلاحية مالية في أي إجراء.
- `app/Models/IncomeCategory.php` يستخدم `TenantScoped` و`SoftDeletes`؛ لذلك النطاق tenant-level، والحذف ناعم.
- `database/migrations/2026_06_27_155003_create_company_transactions_table.php:21,37-43` يربط المعاملة بالفئة ويعرّف soft deletes للمعاملة أيضاً.
- `resources/js/Pages/Settings/System/Index.vue` يعرض أفعال فئات الحسابات في نموذج الإعداد المشترك دون تحقق من صلاحية الإدارة.

**الإجراء:** أضف صلاحية مستقلة لإدارة تصنيفات المعاملات وتحقق منها في routes أو المتحكم، مع حصر `IncomeCategory` tenant-scoped. لا تحذف فئة مستخدمة فعلياً؛ عطّلها أو امنع حذفها حتى لا تفقد المعاملات التاريخية تصنيفها. أضف اختباراً لعضو عادي ولمستخدم المالية مع وجود معاملة مرتبطة.


### 49) **عالٍ — موظف مستأجر يستطيع تغيير إعداد فحوص المركبات على مستوى النظام كله**

**الأثر:** زر إعداد «الفحوصات المنهجية» يستدعي endpoint يبدّل مفتاحاً عاماً من جدول `settings` غير مربوط بمستأجر أو مركز. لا يطلب المسار سوى تسجيل الدخول وسياق المركز، ولا يستدعي المتحكم policy أو يتحقق من صلاحية إدارة الخدمات. يغيّر ذلك ظهور خطوات الفحص في صفحة الخدمات وأوامر العمل لجميع المستأجرين، لا المستأجر الذي أرسل الطلب فقط.

**الدليل:**
- `routes/web.php:697` يسجل `POST /services/toggle-inspections-setting` دون middleware صلاحية.
- `app/Http/Controllers/App/ServiceController.php:156-168` ينفذ `firstOrCreate` ثم يعكس قيمة المفتاح ويحفظها دون `authorize()`.
- `app/Models/Setting.php` يصرح بأنه مخزن key/value عام للنظام، بلا tenant scope.
- `app/Http/Controllers/App/WorkOrders/WorkOrderController.php:440` يقرأ المفتاح نفسه عند عرض أمر العمل، و`app/Http/Controllers/App/ServiceController.php:141` يمرره إلى صفحة إعداد الخدمات.
- `resources/js/Pages/Services/Index.vue:734-743` يعرض مفتاح التبديل، بينما لا يثبت فحص الواجهة صلاحية الخادم.

**الإجراء:** حدّد هل الإعداد عام للنظام أم لكل مستأجر. إن كان عاماً، انقله إلى إعدادات النظام واحمِه بصلاحية system admin. وإن كان تفضيلاً للمنشأة، خزّنه مع `tenant_id` (أو إعدادات المستأجر) واقرأه/حدّثه ضمن نطاقه، مع صلاحية إدارة الخدمات. اختبر أن تغيير مستأجر لا يؤثر في آخر.


### 50) **متوسط — إضافة بند أمر العمل تقبل معرف خدمة/قسم من مستأجر آخر**

**الأثر:** endpoint إضافة بند إلى أمر العمل يتحقق من `service_id` و`department_id` بوجودهما في قاعدة البيانات فقط، لا بانتمائهما إلى مستأجر/مركز أمر العمل. ثم يكتب قيم المعرفات على `WorkOrderItem` المرتبط بالأمر الحالي. عند كون الخدمة من مستأجر آخر، فإن قراءة `Service::find()` المقيدة بـ`TenantScoped` تعيد `null`، لكن الكود يحتفظ بـ`service_id` المرسل ويخزن سطر الأمر بسعر يرسله العميل؛ ينتج مرجعاً عابراً للمستأجر لا يمكن تحميل خدمته من سياق هذا المستأجر، وقد يظهر السطر باسم عام/من دون تصنيف. ينطبق احتمال مشابه على `department_id` لأن `exists` غير مقيد. هذا فساد ارتباطات/عرض داخل أمر عمل، وليس دليلاً على قدرة قراءة أو تعديل سجل الخدمة الأجنبية.

**الدليل:**
- `app/Http/Controllers/App/WorkOrders/WorkOrderItemController.php:24-64` يصرح بتعديل أمر العمل، ثم يستخدم قواعد `exists:services,id` و`exists:departments,id` العامة، ويحمّل الخدمة بسياق TenantScoped فقط.
- `:66-75` يكتب `service_id` و`department_id` المقبولين إلى بند أمر العمل، مع استخدام سعر الطلب إذا لم تُحمّل الخدمة.
- `app/Models/Service.php` يستخدم `TenantScoped`، بينما `app/Models/Department.php` يستخدم `CenterScoped`؛ نطاقا النموذجين لا يعوضان قاعدة التحقق غير المقيدة قبل حفظ المعرف.
- `app/Models/WorkOrderItem.php:61-64,125-132` يستخرج القسم/الخدمة من العلاقة عند توفرها؛ إذا لم تُحل العلاقة بسبب نطاق tenant/center يظهر السطر دون المرجع/القسم المتوقع.

**الإجراء:** اربط التحقق بمستأجر ومركز أمر العمل: `Rule::exists(...)->where(tenant_id, $work_order->tenant_id)->where(center_id, $work_order->center_id)` لكل مرجع يتطلب تطابقاً محلياً، أو عرّف صراحةً موارد كتالوج مشتركة مسموحاً بها. أعد رفض الطلب قبل الإنشاء عند عدم التطابق، واختبر IDs من مستأجر ومركز مختلفين.


### 51) **عالٍ — تقرير المدفوعات وتصديره متاحان دون صلاحية قراءة مالية**

**الأثر:** صفحة التقرير ترجع مبالغ التحصيل والاسترجاع، بيانات العملاء وأرقام هواتفهم، أرقام الفواتير وأوامر العمل، طريقة الدفع، المستلم والملاحظات. يستطيع أي مستخدم يجتاز middleware المصادقة/2FA الوصول إلى صفحة التقرير وتنزيل CSV؛ controller لا يستدعي `PaymentPolicy::viewAny` أو يتحقق من `payments.view`. الاستعلام يقيّد البيانات بمستأجر المستخدم ومركزه الحالي (مع السجلات ذات `center_id = null`)، لذلك لا أثبت تجاوز مستأجر، لكن صلاحية قراءة المدفوعات المعرفة في النظام لا تُطبّق هنا.

**الدليل:**
- `routes/web.php:949-953` يسجل `hub`, `payments` و`payments/export` داخل مجموعة التطبيق دون middleware `payments.view`.
- `app/Http/Controllers/App/ReportsController.php:26-28,35-137` يعرض صفحة التقارير وبيانات المدفوعات دون `authorize()`.
- `:135-234` يصدر CSV يتضمن اسم الطرف وهاتفه وأرقام المستندات والمبلغ والمستلم والملاحظات.
- `:255-261` يقيد الاستعلام بـ`tenant_id` والمركز الحالي أو `center_id IS NULL`؛ هذا يحد النطاق لكنه لا يمثل تفويضاً وظيفياً.
- `app/Policies/PaymentPolicy.php:13-20` يعرّف `viewAny()` ويشترط `Permissions::PAYMENTS_VIEW`، لكن ReportsController لا يستخدمه.
- `resources/js/Pages/Reports/Payments.vue` يعرض زر التصدير مباشرة؛ إخفاؤه وحده لا يحمي route التصدير.

**الإجراء:** نفّذ `authorize('viewAny', Payment::class)` أو تحققاً مكافئاً من `PAYMENTS_VIEW` في صفحة التقرير والتصدير، واضبط المركز وفق سياسة الدفع المعتمدة بما في ذلك السجلات غير المرتبطة بمركز. أخفِ التقرير وزر التصدير من الواجهة عند غياب الصلاحية كتحسين عرض، واختبر منع مستخدم بلا `payments.view` من GET وCSV.


### 52) **متوسط — تصدير تقرير المدفوعات لا يحيّد صيغ CSV القادمة من المستخدم**

**الأثر:** يكتب تصدير المدفوعات قيم الملاحظات والمرجع وأسماء الأطراف وبقية الحقول إلى CSV مباشرة. يمكن لمستخدم مخول بتسجيل دفعة وضع نص يبدأ بمحرف صيغة مثل `=`, `+`, `-`, أو `@` في `notes` (أو حقل نصي آخر)، ثم عند فتح التقرير في برنامج جداول قد يُفسر النص كصيغة بدلاً من قيمة. يتطلب الأثر أن يفتح مستلم التقرير الملف في برنامج يفسر صيغ CSV؛ لا يعني ذلك تنفيذ كود على خادم التطبيق.

**الدليل:**
- `app/Http/Controllers/App/PaymentsController.php:27-34` يقبل `notes` كنص حتى 500 محرف دون تنقية خاصة بالتصدير.
- `app/Http/Controllers/App/ReportsController.php:135-234` يصدر سجلات الدفع باستخدام `fputcsv`، و`:213-225` يكتب الملاحظات والمرجع وأسماء الأطراف والحقول النصية كما هي.
- `fputcsv` يقتبس فواصل CSV لأجل بنية الملف، لكنه لا يحول محتوى الحقل إلى نص آمن من تفسير برامج الجداول للصيغ.

**الإجراء:** حيّد الحقول النصية التي قد تبدأ بمحارف الصيغ بعد إزالة المحارف البيضاء/التحكم السابقة، مثلاً بإضافة بادئة نصية آمنة، مع إبقاء الحقول الرقمية المعروفة رقمية. أضف اختباراً لمدخلات تبدأ بـ`=`, `+`, `-`, `@`, وtab/newline، وتحقق من قيمة الملف الناتج عند فتحه في أدوات الجداول المستهدفة.


### 53) **متوسط — معدل إلغاء الاشتراكات يقسم على عدد لا يمثل النشطين عند بداية الفترة**

**الأثر:** تحليلات النظام تعرض churn كنسبة إلغاء الفترة إلى الاشتراكات النشطة عند بدايتها، لكن المقام يستعلم عن الاشتراكات التي حالتها **الآن** `active` وأنشئت قبل بداية الفترة. الاشتراكات التي كانت نشطة في بداية الفترة ثم ألغيت خلالها أصبحت حالتها الحالية `cancelled` فتُستبعد من المقام، مع أنها تدخل في البسط؛ وقد ينتج معدل أعلى من الحقيقة، أو يتجاوز 100% إذا ألغيت اشتراكات جديدة خلال الفترة. كما لا يعيد الاستعلام الحالة التاريخية عند تاريخ البداية.

**الدليل:**
- `app/Http/Controllers/System/AnalyticsController.php:45-68` تستدعي `computeChurnRate()` للفترة الحالية والشهر السابق.
- `:106-110` يحسب `$activeAtStart` من `status = active` الحالي و`created_at < $from` فقط؛ لا يوجد شرط تغطية الاشتراك لتاريخ البداية أو سجل حالات تاريخي.
- `:116-123` يحسب البسط من `status = cancelled` الحالي وتاريخ `updated_at`، وليس حدث إلغاء مؤرخاً مستقلاً.
- `tests/Feature/System/AnalyticsAggregationTest.php` تغطي MRR والمستحقات وpayment mix وترتيب الباقات، ولا تحتوي اختبار churn يغيّر حالة الاشتراك بعد نقطة البداية.

**الإجراء:** احسب عدد الاشتراكات التي غطت بداية الفترة وفق `starts_at` و`ends_at` مع لقطة/سجل أحداث للحالات، ثم احسب أحداث الإلغاء الواقعة داخل الفترة على مستوى تعريف واضح (اشتراكات أو مستأجرين). لا تستنتج تاريخ الحالة من `status` الحالي. أضف حالات اشتراك نشط ثم ملغى داخل الفترة وتحقق من المعدل المتوقع.


### 54) **متوسط — مؤشرات الاحتفاظ وصحة المستأجر التاريخية تستخدم الحالة الراهنة**

**الأثر:** endpoint الاحتفاظ حسب شهر الانضمام وواجهة منحنى صحة المستأجر يحسبان الماضي من الحالة الحالية، لا من سجل زمني. عند حساب الأشهر الماضية، شرط الاحتفاظ يقبل الاشتراكات التي ما زالت `active` الآن فقط؛ الاشتراك الذي غطى الشهر المستهدف ثم انتهى أو ألغي لاحقاً يُحسب غير محتفظ به. كذلك `tenantHealthTrend()` يعيد بناء نقاط الماضي من حالة الفواتير والاشتراك والإيصالات الحالية؛ فإلغاء لاحق قد يجعل شهراً كان فيه الاشتراك نشطاً يظهر بلا اشتراك، وسداد فاتورة لاحقاً قد يمحو إشارة التأخير التاريخية. النتيجة رسم تاريخي يبدو دقيقاً لكنه لا يمثل الحالة كما كانت وقتها. لم أعثر على مستهلك Vue حالي لـ endpoint الـ cohort، بينما health trend مستخدم في تبويب المستأجر.

**الدليل:**
- `app/Http/Controllers/System/AnalyticsController.php:221-296` يحسب cohort retention من اشتراكات المستأجر الحالية ويشترط `$sub->status === 'active'` قبل فحص تداخل التواريخ.
- `:312-331` ينشئ نقاط health شهرية ماضية ثم يمرر كل نقطة إلى `computeHealthScoreAt()`.
- `:344-377` يعتمد في كل نقطة على status الحالي للفواتير والاشتراكات والإيصالات؛ التعليق يصف replay تاريخياً، لكن لا توجد جداول snapshot أو `status_changed_at`/سجل أحداث مستخدم في الحساب.
- `tests/Feature/System/AnalyticsAggregationTest.php` لا يغطي `cohortRetention` أو `tenantHealthTrend` أو سيناريو انتهاء اشتراك بعد شهر الاحتفاظ.

**الإجراء:** خزّن لقطات شهرية أو أحداث تغيير حالة مؤرخة، واحسب مؤشرات الماضي من تلك البيانات. إلى أن يتوفر تاريخ موثوق، سمِّها بوضوح كتقديرات حالية أو أوقف عرض نقاط الماضي. أضف اختبارات اشتراك يغطي شهراً سابقاً ثم ينتهي، وفاتورة تتأخر ثم تسدد، وإيصال يتغير من pending إلى rejected بعد الفترة.


### 55) **متوسط — MRR الظاهر لكل باقة لا يستخدم قيمة الاشتراكات الفعلية**

**الأثر:** بطاقة أفضل الباقات تعرض `SAR/mo` لكل باقة، لكن القيمة تحسب بضرب عدد الاشتراكات النشطة في `plan.price_monthly`. هذا يتجاهل الدورة السنوية وخصومات الاشتراك والسعر النهائي المخزن في كل `Subscription::price`. إذا كان السعر السنوي مخفضاً أو توجد خصومات، تختلف قيمة MRR للباقة عن الإيراد الشهري الفعلي الذي يحسبه KPI الرئيسي من الاشتراكات والأسعار الفعلية.

**الدليل:**
- `app/Http/Controllers/System/AnalyticsController.php:106-116` يحسب MRR الإجمالي من `Subscription::price` ويقسم الاشتراك السنوي على 12.
- `:140-156` يحسب حقل `topPlans[].mrr` كـ`active_subscribers * price_monthly`، ولا يجمع اشتراكات الباقة ولا يراعي `billing_cycle` أو `discount_amount`.
- `resources/js/Pages/System/Analytics/Kpis.vue:93-122` يعرض هذا الحقل للموظف بصيغة `SAR/mo`.
- `tests/Feature/System/AnalyticsAggregationTest.php` يختبر عدد المشتركين وترتيب الباقات فقط، ولا يتحقق من قيمة MRR المعروضة لكل باقة مع اشتراك سنوي مخفض.

**الإجراء:** احسب MRR الباقة من اشتراكاتها النشطة باستخدام `Subscription::price` والدورة الفعلية، مع سياسة موحدة للخصومات/الضريبة، أو سمّ الحقل «الإيراد النظري بالسعر الشهري المعلن» إذا كان المقصود عرض تقدير قائمة الأسعار. اختبر باقة سنوية مخفضة ومشتركاً بسعر نهائي مختلف عن سعر الخطة.


### 56) **عالٍ — أدوار الدعم/المسؤول التشغيلي تستطيع تنزيل تقارير النظام الشاملة دون صلاحية تقارير**

**الأثر:** مستخدم `AdminUser` بدور `support` أو `admin` يجتاز `system.admin` ويدخل مسارات تنزيل تقارير إيصالات التحويل البنكي، المستأجرين، والديون المتأخرة. لا تطلب المسارات `super_admin` ولا صلاحية مثل `invoices.view`/`subscriptions.view` أو إذناً مخصصاً للتصدير. الملفات تشمل أسماء المستأجرين وبريدهم، حاملي الحسابات البنكية ومراجع التحويل، مبالغ الفواتير والخطط وحالتها. وصف `EnsureSuperAdmin` نفسه يعدّ الدعم للقراءة المحدودة، لكن التصدير يتيح استخراج بيانات مجمعة حساسة دون تسجيل تفويض خاص.

**الدليل:**
- `routes/web.php:1014` يضع لوحة النظام تحت `auth:web,admin` و`system.admin` فقط؛ ومسارات `:1031-1057` تعرض التقارير وتنفذ التنزيلات داخل المجموعة نفسها، بلا middleware إضافي أو تحقق صلاحية.
- `app/Http/Middleware/EnsureSystemAdmin.php:52-54` يسمح لأي كائن `AdminUser` بالمرور بغض النظر عن دوره؛ `EnsureSuperAdmin` يذكر أن `admin` تشغيلي و`support` محدود القراءة.
- `app/Exports/BankTransferReceiptsExport.php:45-69` يصدر بيانات البنك، صاحب الحساب، المرجع، المبلغ والمراجع.
- `app/Exports/TenantsReportExport.php:46-65` يصدر اسم المستأجر وبريده وخطته والفاتورة؛ و`app/Exports/AgingReportExport.php:43-71` يصدر الفواتير المستحقة ومبالغها.
- `AdminUser::getAvailablePermissions()` يتضمن صلاحيات عرض للفواتير والاشتراكات، ولا يظهر إذن خاص بهذه التقارير؛ لكن مسارات التنزيل لا تستشير أي صلاحية.

**الإجراء:** اربط كل تقرير بصلاحية قراءة بياناته وتصدير مستقلة أو بقيد دور موثق، وسجّل عملية التنزيل وهوية المستخدم والفلاتر. أضف اختبارات رفض لـ`support`/`admin` عند غياب الإذن والسماح للحسابات المخولة فقط، مع فحص ألا تتجاوز حمولة التقرير الصلاحية الممنوحة.


### 57) **متوسط — إنشاء/تعديل/حذف إعدادات المخزون يتجاوز صلاحية إدارة الإعدادات**

**الأثر:** مستخدم التطبيق الذي لا يملك `inventory.settings.manage` يستطيع استدعاء بعض endpoints مباشرةً لتغيير بيانات وحدات وفئات المخزون. فحص الصلاحية موجود في صفحة العرض، إنشاء الوحدة، وتعديل الوحدة، لكنه غائب عن حذف الوحدة وجميع عمليات فئات المخزون (إنشاء/تعديل/حذف). لذلك لا يكفي إخفاء الصفحة أو أزرارها لمنع التغيير. التحديث والحذف مقيدان بـ`tenant_id`، فلا يظهر من هذا المسار تجاوز لمستأجر آخر؛ الأثر هو تخطي التفويض داخل المستأجر وتغيير تصنيفات تستخدمها سجلات القطع.

**الدليل:**
- `app/Http/Controllers/App/InventorySettingsController.php:45-57` يشترط `INVENTORY_SETTINGS_MANAGE` عند عرض الصفحة.
- `:61-79` يشترط الصلاحية عند إنشاء وحدة، و`:82-97` عند تعديلها؛ بينما `:99-116` يحذف الوحدة بتحقق tenant فقط.
- `:120-137` ينشئ فئة دون authorize، و`:139-153` يعدلها بتحقق tenant فقط، و`:155-171` يحذفها بتحقق tenant فقط.
- `routes/web.php:749-758` يسجل جميع مسارات الكتابة؛ لا يضيف middleware صلاحية لهذه المسارات.
- `resources/js/Pages/Inventory/Settings.vue:317-400` يرسل طلبات CRUD مباشرةً؛ حماية الواجهة لا تمنع استدعاء المسارات من عميل مصادق عليه.
- `app/Support/Permissions.php:311,509` يعرّف `inventory.settings.manage` كصلاحية مستقلة، ما يثبت أن النظام يميز هذا التفويض.

**الإجراء:** أضف فحص `INVENTORY_SETTINGS_MANAGE` لكل endpoint كتابة، قبل تعديل/حذف السجل، وأبقِ فحص `tenant_id` كذلك. أضف اختبارات مستخدم مصادق بلا الصلاحية على كل عملية، مع تحقق السماح للمستخدم المخول وحماية المراجع التابعة المستخدمة في القطع.


### 58) **متوسط — إنشاء موعد موظف قد يكتب الموعد في مركز غير المركز النشط**

**الأثر:** المستخدم المصادق والمخوّل بإدارة المواعيد في مركزه يستطيع إرسال `customer_id` لمستأجره لكن من مركز آخر. المتحكم يحمّل العميل من نطاق المستأجر فقط، ثم خدمة الجدولة تعتمد `customer.center_id` عندما لا يمرر المتحكم مركزاً بديلاً. بذلك يُنشأ الموعد للمركز الآخر، بينما قائمة المواعيد وسياسة العرض تقصران القراءة والتعديل على `current_center_id`. ينتج سجل لا يظهر للمستخدم في القائمة وقد يسبب التباساً في ملكية الموعد أو إرسال الرسائل للمركز الخطأ. لا يثبت هذا تجاوز مستأجر؛ إنه تجاوز لنطاق المركز داخل المستأجر.

**الدليل:**
- `app/Http/Controllers/App/AppointmentController.php:109-142` يفحص صلاحية إنشاء عامة، ويستخدم قواعد `exists` ثم `Customer::findOrFail()`؛ لا يقارن مركز العميل بالمركز النشط ولا يمرر `$user->current_center_id` إلى خدمة الجدولة.
- `app/Models/Concerns/TenantScoped.php:18-27` يقيّد `Customer` و`Vehicle` بـ`tenant_id` فقط، ولا يضيف قيداً على المركز.
- `app/Services/AppointmentSchedulingService.php:45-64,87-89,120-124` يوضح أن مسار الموظف الافتراضي يستخدم مركز العميل ويكتب `center_id` منه؛ والفحص `assertSameContext()` يسمح بسيارة عميل في فرع آخر ما دامت ضمن المستأجر نفسه (`:543-555`).
- `app/Http/Middleware/EnsureCenterContext.php:50-57` يثبت أن الطلب يعمل ضمن مركز نشط مرتبط بالمستخدم، بينما `app/Policies/AppointmentPolicy.php:16-24` يقصر رؤية الموعد على ذلك المركز.
- `tests/Feature/AppointmentTest.php:137-155` يغطي الإنشاء في المركز الحالي، لكن لا يوجد اختبار ظاهر لإنشاء موعد بعميل من مركز آخر لمستخدم محدود بمركزه.

**الإجراء:** مرّر المركز النشط صراحةً إلى خدمة الجدولة في مسار الموظف، واشترط أن يكون العميل/المركبة/الخدمات والموارد المحددة مسموحاً بها في ذلك المركز وفق قواعد مشاركة العملاء بين الفروع. إن كان إنشاء مواعيد لفرع آخر مقصوداً، فليكن عبر إذن صريح وتحديد مركز ظاهر للمستخدم. أضف اختبار مستخدم مربوط بمركز A يحاول إنشاء موعد لعميل مركز B.


### 59) **متوسط — زر «لم يحضر» يظهر بعد تسجيل الوصول ثم يفشل عند التنفيذ**

**الأثر:** بعد تسجيل موعد بحالة `checked_in`، تستمر صفحة المواعيد في عرض زر «لم يحضر»؛ لكن الخدمة تسمح بانتقال `no_show` من `scheduled` أو `confirmed` فقط. عند الضغط يرجع endpoint خطأ 422، ودالة Vue لا تعرض الخطأ للمستخدم؛ ينتهي مؤشر الانشغال وتبقى الصفحة كما هي، فيبدو الإجراء معطلاً.

**الدليل:**
- `resources/js/Pages/Appointments/Index.vue:223-234` يعرض زر no-show لكل حالة ليست `cancelled` أو `converted` أو `no_show`، فيشمل `checked_in`.
- `:592-600` تنفذ POST وتعيد تحميل الصفحة عند النجاح، لكن لا تحتوي `catch` لمعالجة رفض الخادم.
- `app/Services/AppointmentSchedulingService.php:318-336` يقصر حالات المصدر على `STATUS_SCHEDULED` و`STATUS_CONFIRMED` ويرمي `invalid_transition_no_show` للحالات الأخرى.
- `app/Http/Controllers/App/AppointmentController.php:220-236` يحول هذا الاستثناء إلى استجابة 422؛ واختبار `tests/Feature/AppointmentTest.php:335-367` يسجل الوصول لموعد ثم يختبر no-show على موعد مختلف، لذلك لا يكشف عدم التطابق للموعد نفسه.

**الإجراء:** اجعل شرط الظهور مطابقاً لقائمة الحالات المسموح بها (`scheduled`/`confirmed`) أو غيّر قواعد العمل إن كان تحويل `checked_in` إلى no-show مطلوباً. أضف اختباراً للحالة المسجلة وصولاً على مستوى الواجهة/endpoint، واعرض رسالة واضحة لكل أخطاء الطلب في الواجهة.


### 60) **منخفض — لوحة الورشة لا تستخدم تاريخ البدء لكسر تعادل موعد التسليم**

**الأثر:** عند وجود بطاقتين أو أكثر في العمود نفسه بموعد تسليم موحد، لا تُرتب البطاقات حسب الأقدم بدءاً كما يعد به تعليق المتحكم؛ يعتمد الترتيب على ترتيب غير محدد لنتيجة الاستعلام. لا يتغير محتوى البيانات أو صلاحياتها، لكن ترتيب الأولوية الذي يراه مشرف الورشة قد يتبدل بين التحميلات.

**الدليل:**
- `app/Http/Controllers/App/WorkshopBoardController.php:159-181` يذكر أن التعادل يُكسر بـ`started_at`، لكن comparator يقارن `_sort_due_at` فقط ويعيد `0` عند تساوي موعد التسليم.
- `:130-141` يضمّن `started_at` في البطاقة لكنه لا يضمّنه كقيمة فرز مساعدة.
- `tests/Feature/WorkshopBoardTest.php:434-464` يختبر ترتيب المواعيد والعناصر بلا موعد، ولا يختبر بطاقات متساوية الموعد وترتيبها حسب بدء العمل.

**الإجراء:** أضف قيمة فرز ثانية لـ`started_at` مع تعريف ترتيب واضح للبطاقات التي لم تبدأ، ثم أضف اختباراً ببطاقتين لهما موعد التسليم نفسه وبداية مختلفة.


### 61) **متوسط — فشل مزود رمز 2FA أثناء شاشة الدخول لا يُعرض كحالة قابلة للاستعادة**

**الأثر:** عندما يكون البريد أو SMS غير متاح أثناء التحدي، يستدعي GET شاشة التحدي إرسال الرمز مباشرةً بلا معالجة استثناء. فشل الإرسال يخرج كـ500 بدلاً من عرض صفحة تتيح رمز الاسترداد أو رسالة إعادة محاولة. كما تُكتب قيمة `2fa_login_{id}` في Cache قبل محاولة إرسال البريد أو SMS؛ فإذا أخفق المزود فقد يمنع طلب GET التالي من إعادة الإرسال تلقائياً، ويظهر التحدي لاحقاً برمز لم يصل. ينطبق ذلك فقط على حسابات 2FA المفعّلة عند تعطل قناة الإرسال؛ لا يثبت تعطل الدخول لبقية المستخدمين.

**الدليل:**
- `app/Http/Controllers/App/TwoFactorAuthenticatedSessionController.php:27-40` يرسل الرمز من `challenge()` عند غياب مفتاح Cache، ولا يحيط الإرسال بمعالجة خطأ، ثم لا يعرض الصفحة إلا بعد نجاحه.
- `app/Services/TwoFactorService.php:108-118` يخزن علامة `sms_sent` قبل استدعاء مزود SMS، أو يفوض للبريد.
- `:124-132` يخزن رمز البريد قبل `sendCodeWithTemplate()`؛ استثناء النقل لا يمسح المفتاح. و`:137-160` يرمي استثناء عند غياب رقم الهاتف/تكامل SMS أو فشل إعداده، بينما قد يفشل إرسال البريد في `:238`.
- `app/Http/Controllers/App/TwoFactorAuthenticatedSessionController.php:46-60` يعيد إرسال الرمز دون معالجة خطأ الإرسال أيضاً؛ ولا توجد شاشة بديلة ضمن تدفق GET عند الفشل.
- لم أعثر على اختبارات Feature تغطي فشل SMTP أو تكامل SMS في تدفق تحدي الدخول.

**الإجراء:** تعامل مع فشل التسليم داخل المتحكم/الخدمة: امسح مفتاح OTP إذا لم ينجح الإرسال، واعرض خطأ واضحاً في صفحة التحدي مع إعادة محاولة أو مسار استرداد. لا تعتبر الرمز مرسلاً قبل نجاح المزود. أضف اختبارات فشل SMTP/SMS ثم محاولة إعادة إرسال ناجحة، وتحقق من أن الفشل لا يترك المستخدم عالقاً ولا يكشف تفاصيل المزود.


### 62) **عالٍ — تسجيل دخول مدير النظام يتجاوز 2FA حتى عند تفعيله للحساب**

**الأثر:** توجد واجهة ملف شخصي تتيح لمدير النظام تفعيل TOTP ورموز استرداد، كما توجد شاشة ومسارات مستقلة لتحدي 2FA. لكن مسار تسجيل الدخول الخاص بـ`AdminUser` لا يتحقق من `two_factor_confirmed_at` ولا يضع معرف المستخدم في جلسة التحدي؛ بل يرجع مباشرةً إلى `/system` بعد نجاح كلمة المرور. بذلك لا يضيف تفعيل 2FA خطوة تحقق عند تسجيل الدخول، ويستطيع حامل كلمة المرور الدخول بالطريقة ذاتها سواء كان 2FA مفعلاً أم لا. لا ينطبق هذا على مسار حسابات tenant، الذي لديه تفرع 2FA منفصل.

**الدليل:**
- `app/Http/Requests/Auth/LoginRequest.php:61-73` يصادق مدير النظام عبر حارس `admin` ويضبط `is_admin_login` بعد كلمة المرور.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php:37-48` يجد علامة دخول المدير ثم يعيد التوجيه مباشرةً إلى `/system` قبل فرع tenant الذي يفحص `two_factor_confirmed_at` في `:62-68`.
- `resources/js/Pages/System/Profile/Index.vue:46-74,368-388` تعرض تفعيل/تعطيل 2FA لمدير النظام عبر `system.2fa.enable/disable`، والمتحكم `app/Http/Controllers/System/TwoFactorController.php:90-111` يحفظ `two_factor_confirmed_at` عند التفعيل.
- `routes/web.php:1277-1281,1289-1292` يسجل شاشة إعداد ومساري تحدي/تحقق مدير النظام؛ لكن لا يوجد توجيه من مسار login إلى `2fa.challenge` أو حفظ `2fa:user_id`/`2fa:user_type` لمدير النظام. بحثت عن `2fa:user_type` خارج متحكم التحقق ولم أجد مسار إنشاء للجلسة.
- وحتى إذا استُخدم endpoint التحقق يدوياً بعد إنشاء جلسة التحدي، فإن `app/Http/Controllers/System/TwoFactorController.php:219-226` ينفذ `auth()->login($user)` على الحارس الافتراضي بدلاً من `Auth::guard('admin')->login($user)`؛ لذلك لا يعيد مدير النظام إلى الحارس الذي تصادق عليه صفحة الدخول.
- لم أعثر على اختبار Feature يثبت أن مديراً مفعلاً لـ2FA لا يصل إلى `/system` قبل التحقق.

**الإجراء:** بعد نجاح كلمة مرور مدير النظام، لا تعتبر جلسة `admin` مكتملة إذا كان `two_factor_confirmed_at` مضبوطاً؛ أنشئ حالة تحدٍّ قصيرة العمر ومربوطة بنوع الحساب ومعرفه، ثم أزل حالة التحدي بعد TOTP/رمز استرداد ناجح، وأكمل المصادقة على حارس `admin` مع تدوير جلسة الدخول. أضف اختبارات دخول مدير 2FA مفعل/غير مفعل، ورفض الوصول للوحة قبل التحقق، واستخدام رمز الاسترداد مرة واحدة.


### 63) **متوسط — محاولة OTP للتسجيل تكتب الرمز ورقم الهاتف صراحةً في سجل التطبيق**

**الأثر:** endpoint التحقق من رقم الهاتف يرسل إلى سجل `info` رقم الهاتف والرمز الذي أدخله المستخدم وكامل استجابة مزود SMS لكل محاولة، سواء نجحت أم فشلت. من يستطيع قراءة سجلات التطبيق/نسخها الاحتياطية يحصل على PII ورموز تحقق صالحة زمنياً؛ وقد تسمح إعادة استخدام الرمز أو سباق زمني بتجاوز إثبات ملكية الهاتف قبل انتهاءه، بينما يبقى كشف البيانات مؤكداً بحد ذاته. المسار عام قبل التسجيل ومحدود بثلاث طلبات في الدقيقة لكل هاتف خام أو IP، وهذا يقلل الحجم ولا يمنع كشف ما سُجّل.

**الدليل:**
- `routes/web.php:285-287` يسجل إرسال/تحقق رقم الهاتف دون مصادقة، مع throttle فقط.
- `app/Http/Controllers/Auth/PhoneVerificationController.php:81-109` يتحقق من OTP عبر الخدمة، ثم ينفذ `Log::info('Phone verification attempt', ['phone' => $phone, 'otp' => $request->otp, 'result' => $result])` دون تنقية، لكل نتيجة.
- `app/Services/Sms/AuthenticaService.php:89-117` يعيد بيانات المزود ضمن `result.data`؛ المتحكم يسجل الاستجابة كاملة وقد تحتوي بيانات تشغيلية إضافية.
- لا يوجد اختبار يثبت غياب `otp`/`phone` من logs هذا التدفق.

**الإجراء:** احذف الرمز والاستجابة الخام من السجل، واستخدم معرف طلب/نتيجة مختصر وقيمة هاتف مقنّعة أو hash غير قابل للعكس. راجع سياسات الاحتفاظ والوصول لسجلات الإنتاج، وأضف اختباراً يراقب سجلات النجاح والفشل ويثبت عدم احتوائها OTP أو رقم الهاتف كاملاً.


### 64) **متوسط — نموذج إنشاء الحساب يقبل الرمز الترويجي ولا يطبقه**

**الأثر:** صفحة التسجيل تعرض حقل Promo Code وترسله مع بقية بيانات إنشاء الحساب. الخادم يتحقق من نوعه وطوله فقط، ثم يتجاهله؛ لا يتحقق من صلاحيته أو ينشئ استخداماً أو يربطه بالاشتراك التجريبي. لذلك لا يحصل العميل على العرض الذي أدخله، وقد يظن أن الخصم حُسب حين لم يحدث ذلك. يوجد endpoint مستقل للتحقق من الأكواد في إعدادات الاشتراك، لكنه غير مستدعى من نموذج التسجيل.

**الدليل:**
- `resources/js/Pages/Auth/Register.vue:12-22,312-323` يضمّن `promo_code` في نموذج إنشاء الحساب ويعرضه كحقل «الرمز الترويجي».
- `app/Http/Controllers/Auth/RegisteredUserController.php:48-57` يقبل قيمة نصية حتى 50 محرفاً بلا تحقق من سجل الرمز أو صلاحياته.
- `:92-107` ينشئ اشتراك التجربة بسعر صفر، و`:144-148` يحتوي TODO صريحاً للتطبيق ثم لا ينفذ شيئاً.
- `routes/web.php:565,604` يسجل مسارات validate-promo ضمن إعدادات الفوترة، ولا تظهر لها دعوة من `Register.vue` أو `RegisteredUserController`.

**الإجراء:** إما أزل الحقل من التسجيل إلى أن يدعم المنتج استخدامه هناك، أو نفّذ التحقق والتطبيق/تسجيل الاستخدام وربط أثر الخصم بالاشتراك وفق شروط الرمز، مع رد واضح بحالة الرمز. اختبر رمزاً صحيحاً ومنتهياً ومستخدماً إلى الحد الأقصى وتحقق من الأثر المالي المخزن.


### 65) **متوسط — إنشاء الموظف يكتب كامل الطلب في سجل التطبيق قبل التفويض والتحقق**

**الأثر:** يسجل endpoint إنشاء الموظف بيانات الطلب الخام قبل فحص صلاحية الإنشاء وقبل التحقق من المدخلات. يمكن أن يتضمن ذلك الاسم ورقم الهاتف والبريد وأي حقول حساسة يضيفها العميل؛ وبذلك تُنسخ بيانات شخصية إلى سجلات التطبيق حتى عندما يُرفض الطلب لغياب الصلاحية أو لفشل التحقق.

**الدليل:**
- `app/Http/Controllers/App/HR/EmployeeController.php:126-141` يستدعي `Log::info('Employee store initiated', $request->all())` في السطر 128، ثم `authorize()` في 129 والتحقق في 131 وما بعده.
- لا يوجد اختبار يثبت أن بيانات الطلب الحساسة لا تظهر في سجل هذا المسار.

**الإجراء:** انقل التفويض إلى بداية المسار، واحذف تسجيل جسم الطلب الخام. عند الحاجة للتشخيص، سجّل حقولاً مسموحة محددة مثل معرّف المستأجر ونتيجة العملية، مع حجب أي PII، واختبر مخرجات السجل للطلبات المرفوضة والناجحة.


### 66) **متوسط — مرشح العملاء في تقرير المدفوعات يكشف بيانات مراكز أخرى**

**الأثر:** مستخدم مركز لديه وصول إلى صفحة التقرير يحصل ضمن props على قائمة حتى 100 عميل من كامل المستأجر، تشمل الاسم ورقم الهاتف. استعلام سجل المدفوعات يقيد النتائج بمركز المستخدم، لكن استعلام العملاء لا يطبق قيد المركز؛ لذلك يمكن للمستخدم استعراض بيانات اتصال لعملاء مراكز أخرى بمجرد فتح مرشح العملاء، حتى إن لم تظهر لهم دفعات. يزداد الأثر لأن صفحة التقرير نفسها لا تطبق صلاحية `payments.view` كما هو موثق في النتيجة 51.

**الدليل:**
- `app/Http/Controllers/App/ReportsController.php:35-36,88-95` يحصل على `centerId`، ثم يحمّل العملاء بـ`tenant_id` فقط ويرسل `id`, `name`, `phone` إلى الصفحة.
- `buildPaymentsQuery()` يقيّد الدفعات بـ`center_id` الحالي في `:255-260`، ما يثبت أن نطاق المركز جزء من هذا التدفق بينما قائمة المرشح لا تتبعه.
- `app/Models/Customer.php:9-15` يستخدم `TenantScoped` فقط، وليس `CenterScoped`؛ لا يوجد نطاق عام يعالج الفرق تلقائياً.
- `resources/js/Pages/Reports/Payments.vue:935-944` يحول كل العملاء الواردة إلى خيارات تعرض الاسم والهاتف.

**الإجراء:** قيد خيارات العملاء بـ`center_id` الفعلي وفق سياسة الوصول (مع إدراج السجلات المشتركة فقط إذا كانت السياسة تسمح)، وطبّق صلاحية قراءة التقرير على الصفحة والتصدير. أضف اختباراً لمستخدم مركز يثبت غياب العملاء من المراكز الأخرى من props ومن نتائج البحث والتصدير.


### 67) **منخفض — فتح الإشعار قد يسبق اكتمال تعليمه كمقروء**

**الأثر:** عند الضغط على إشعار غير مقروء له `action_url`، تبدأ الصفحة طلب POST لتعليم الإشعار كمقروء، ثم تنفذ فوراً تنقلاً كاملاً إلى الرابط دون انتظار اكتمال الطلب. قد يغادر المتصفح الصفحة ويلغي طلب `fetch` الجاري؛ عندها يفتح المستخدم الوجهة ويبقى الإشعار غير مقروء ويستمر ظهوره في العدّاد. الحالة لا تمنح وصولاً إضافياً، لكنها تجعل علامة القراءة غير موثوقة.

**الدليل:**
- `resources/js/Pages/Notifications/Index.vue:153-170` يبدأ `fetch(.../read)` ويعالج نتيجته لاحقاً، ثم في السطر 174 ينتقل فوراً إلى `window.location.href` عند وجود `action_url`.
- `app/Http/Controllers/App/NotificationController.php:101-123` ينفذ الحفظ في POST ويرجع JSON عند ترويسة Accept المناسبة؛ لذلك يمكن للواجهة انتظار استجابة الخادم قبل الانتقال.
- صفحة الجرس تستخدم كذلك `action_url` لبعض الإشعارات، لكن هذا الفحص يخص تدفق صفحة القائمة الذي يرسل طلب mark-read من المتصفح.

**الإجراء:** اجعل معالجة الضغط async وانتظر استجابة mark-read قبل التنقل، مع التعامل مع فشل الطلب برسالة/إعادة محاولة أو السماح بالتنقل مع تسجيل فشل واضح. بديل مناسب هو إرسال التنقل إلى endpoint mark-read نفسه واتباع redirect بعد الحفظ.


### 68) **عالٍ — نقطة تبديل فحوص المركبات تعدّل إعداداً عاماً بلا تفويض**

**الأثر:** أي مستخدم يصل إلى مسارات التطبيق يستطيع إرسال POST إلى نقطة تبديل فحوص المركبات. المتحكم يقرأ سجل `Setting` بمفتاح ثابت على مستوى التطبيق ثم يقلب قيمته دون `authorize()` أو تحقق من صلاحية إدارة الخدمات. الإعداد نفسه يقرأه `WorkOrderController` عند تجهيز تدفق أمر العمل؛ لذا يمكن لمستخدم tenant واحد تفعيل/تعطيل الفحوص لكل النظام بدلاً من مركزه، والتأثير على سلوك إنشاء أو فحص أوامر العمل لدى المستأجرين الآخرين.

**الدليل:**
- `routes/web.php:695-697` يسجل نقطة التبديل داخل مجموعة التطبيق الموثق دون middleware صلاحية إضافية.
- `app/Http/Controllers/App/ServiceController.php:156-168` ينفذ `Setting::firstOrCreate(['key' => 'enable_systematic_inspections'])` ثم يقلب القيمة ويحفظها دون `authorize()`.
- `app/Models/Setting.php:8-10` يعرّف جدول إعدادات مفتاح/قيمة عاماً وغير مرتبط بمستأجر أو مركز.
- `app/Http/Controllers/App/WorkOrders/WorkOrderController.php:440` يقرأ المفتاح نفسه في تدفق أمر العمل.
- `resources/js/Pages/Services/Index.vue:727-752,1464-1472` يعرض زر التبديل وينفذ الطلب لأي مستخدم يفتح الصفحة؛ لا يظهر فحص permission للزر، مع أن صلاحيات واجهة CRUD للخدمات منفصلة.

**الإجراء:** حدد هل الإعداد عام فعلاً أم لكل مستأجر/مركز. إن كان عاماً فانقله إلى إدارة النظام واحصره في دور مخول؛ وإن كان إعداداً تشغيلياً للمركز فخزّنه بنطاق tenant/center وطبّق صلاحية إدارته. أضف اختباراً لمستخدم تطبيق عادي وآخر مخول يثبت النطاق وعدم تأثير مركز على غيره.


### 69) **متوسط — إنشاء/تعديل الخدمة يقبل مراجع أقسام وعناصر حزم من خارج النطاق**

**الأثر:** endpoint الخدمة يتحقق من وجود `department_id` وIDs عناصر الحزمة في قاعدة البيانات فقط، دون تقييدها بمستأجر أو مركز المستخدم. يمكن لمستخدم مخول بإدارة خدمات مركزه إرسال IDs لمراجع من مستأجر آخر؛ تُحفظ علاقة القسم الغريبة أو صفوف pivot إلى خدمات أجنبية. تظهر الخدمة/الحزمة وكأنها حُفظت، لكن علاقة القسم/العناصر الأجنبية قد تُخفى لاحقاً بسبب النطاقات العامة، فتختفي الخدمة من مجموعة القسم أو تظهر الحزمة ناقصة العناصر، كما يُنشأ ربط بين مستأجرين في قاعدة البيانات.

**الدليل:**
- `app/Http/Controllers/App/ServiceController.php:176-177,198` و`:242-243,267` يستخدم قواعد `exists:departments,id` و`exists:services,id` دون شروط `tenant_id`/`center_id`; التحديث يجيز تغيير هذه المراجع أيضاً.
- `store()` و`update()` يكتبان `department_id` ثم يربطان عناصر الحزمة عبر `$service->items()->sync(...)` (`:225-233,286-294`).
- `app/Models/Service.php:14` يستخدم `TenantScoped` فقط، بينما `Department` يستخدم `CenterScoped`؛ وعلاقات `department()` و`items()` لا تضيف تحقق ملكية على معرّفات الطلب.
- لا يوجد اختبار مباشر يرسل معرفات من مستأجر/مركز آخر في طلب إنشاء/تعديل خدمة أو حزمة.

**الإجراء:** قيّد `department_id` إلى قسم المستأجر/المركز الحالي، وقيّد عناصر الحزمة إلى خدمات المستأجر نفسه والمراكز التي تسمح بها سياسة نسخ الكتالوج. عند نسخ عنصر من فرع آخر، انسخ بياناته إلى خدمة محلية بدلاً من حفظ pivot عابر للمراكز. طبّق القيود على create/update واختبر حالات المركز الآخر والمستأجر الآخر.
